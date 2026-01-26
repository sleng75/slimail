<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Jobs\SendCampaignJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request): Response
    {
        $query = Campaign::query()
            ->with(['creator', 'template']);

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $campaigns = $query->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => Campaign::count(),
            'draft' => Campaign::draft()->count(),
            'scheduled' => Campaign::scheduled()->count(),
            'sent' => Campaign::sent()->count(),
        ];

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'type', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(Request $request): Response
    {
        $lists = ContactList::select('id', 'name', 'color', 'contacts_count')->get();
        $templates = EmailTemplate::active()
            ->select('id', 'name', 'category', 'thumbnail', 'default_subject', 'default_from_name', 'default_from_email')
            ->get();

        // Get tenant settings for default sender info
        $tenant = auth()->user()->tenant;

        return Inertia::render('Campaigns/Create', [
            'lists' => $lists,
            'templates' => $templates,
            'defaultFromName' => $tenant?->settings['default_from_name'] ?? '',
            'defaultFromEmail' => $tenant?->settings['default_from_email'] ?? '',
            'step' => $request->get('step', 1),
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:regular,ab_test',
        ]);

        $campaign = Campaign::create([
            'tenant_id' => auth()->user()->tenant_id,
            'created_by' => auth()->id(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => Campaign::STATUS_DRAFT,
            'track_opens' => true,
            'track_clicks' => true,
            'timezone' => 'Africa/Abidjan',
        ]);

        // If A/B test, create initial variants
        if ($campaign->type === Campaign::TYPE_AB_TEST) {
            CampaignVariant::create([
                'campaign_id' => $campaign->id,
                'variant_key' => 'A',
                'name' => 'Variante A',
                'percentage' => 50,
            ]);
            CampaignVariant::create([
                'campaign_id' => $campaign->id,
                'variant_key' => 'B',
                'name' => 'Variante B',
                'percentage' => 50,
            ]);
        }

        return redirect()->route('campaigns.edit', ['campaign' => $campaign->id, 'step' => 1]);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): Response
    {
        $campaign->load(['creator', 'template', 'variants', 'sentEmails' => function ($query) {
            $query->latest()->limit(100);
        }]);

        // Get lists info
        $listIds = $campaign->list_ids ?? [];
        $lists = ContactList::whereIn('id', $listIds)->get();

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'lists' => $lists,
            'variants' => $campaign->variants,
        ]);
    }

    /**
     * Show the form for editing the specified campaign (wizard).
     */
    public function edit(Request $request, Campaign $campaign): Response
    {
        if (!$campaign->isEditable()) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $campaign->load(['template', 'variants']);

        $lists = ContactList::select('id', 'name', 'color', 'contacts_count')->get();
        $templates = EmailTemplate::active()
            ->select('id', 'name', 'category', 'thumbnail', 'html_content', 'design_json', 'default_subject', 'default_from_name', 'default_from_email')
            ->get();

        $tenant = auth()->user()->tenant;

        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign,
            'lists' => $lists,
            'templates' => $templates,
            'defaultFromName' => $tenant?->settings['default_from_name'] ?? '',
            'defaultFromEmail' => $tenant?->settings['default_from_email'] ?? '',
            'step' => (int) $request->get('step', 1),
            'variants' => $campaign->variants,
        ]);
    }

    /**
     * Update step 1: Configuration (name, subject, sender).
     */
    public function updateConfig(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isEditable()) {
            return redirect()->back()->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.edit', ['campaign' => $campaign->id, 'step' => 2])
            ->with('success', 'Configuration enregistrée.');
    }

    /**
     * Update step 2: Recipients (lists, segments).
     */
    public function updateRecipients(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isEditable()) {
            return redirect()->back()->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'list_ids' => 'required|array|min:1',
            'list_ids.*' => 'exists:contact_lists,id',
            'excluded_list_ids' => 'nullable|array',
            'excluded_list_ids.*' => 'exists:contact_lists,id',
        ]);

        $campaign->update([
            'list_ids' => $validated['list_ids'],
            'excluded_list_ids' => $validated['excluded_list_ids'] ?? [],
        ]);

        // Update recipients count
        $campaign->updateRecipientsCount();

        return redirect()->route('campaigns.edit', ['campaign' => $campaign->id, 'step' => 3])
            ->with('success', 'Destinataires enregistrés. ' . $campaign->recipients_count . ' contacts ciblés.');
    }

    /**
     * Update step 3: Content (template, html).
     */
    public function updateContent(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isEditable()) {
            return redirect()->back()->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'template_id' => 'nullable|exists:email_templates,id',
            'html_content' => 'required_without:template_id|nullable|string',
            'design_json' => 'nullable|string',
        ]);

        // If using a template, copy its content
        if (!empty($validated['template_id'])) {
            $template = EmailTemplate::find($validated['template_id']);
            $campaign->update([
                'template_id' => $template->id,
                'html_content' => $template->html_content,
            ]);
        } else {
            $campaign->update([
                'template_id' => null,
                'html_content' => $validated['html_content'],
            ]);
        }

        return redirect()->route('campaigns.edit', ['campaign' => $campaign->id, 'step' => 4])
            ->with('success', 'Contenu enregistré.');
    }

    /**
     * Update A/B test variants.
     */
    public function updateVariants(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isEditable() || !$campaign->isAbTest()) {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $validated = $request->validate([
            'variants' => 'required|array|min:2|max:4',
            'variants.*.id' => 'nullable|exists:campaign_variants,id',
            'variants.*.variant_key' => 'required|string|max:1',
            'variants.*.name' => 'nullable|string|max:255',
            'variants.*.subject' => 'nullable|string|max:255',
            'variants.*.from_name' => 'nullable|string|max:255',
            'variants.*.html_content' => 'nullable|string',
            'variants.*.percentage' => 'required|integer|min:1|max:100',
            'test_percentage' => 'required|integer|min:10|max:100',
            'winning_criteria' => 'required|in:opens,clicks',
            'test_duration_hours' => 'required|integer|min:1|max:72',
        ]);

        // Validate total percentage equals 100
        $totalPercentage = array_sum(array_column($validated['variants'], 'percentage'));
        if ($totalPercentage !== 100) {
            return redirect()->back()->withErrors(['variants' => 'Les pourcentages doivent totaliser 100%.']);
        }

        // Update or create variants
        foreach ($validated['variants'] as $variantData) {
            if (!empty($variantData['id'])) {
                $variant = CampaignVariant::find($variantData['id']);
                $variant->update($variantData);
            } else {
                CampaignVariant::create([
                    'campaign_id' => $campaign->id,
                    ...$variantData,
                ]);
            }
        }

        // Save A/B test config
        $campaign->update([
            'ab_test_config' => [
                'test_percentage' => $validated['test_percentage'],
                'winning_criteria' => $validated['winning_criteria'],
                'test_duration_hours' => $validated['test_duration_hours'],
            ],
        ]);

        return redirect()->back()->with('success', 'Variantes enregistrées.');
    }

    /**
     * Send a test email.
     */
    public function sendTest(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Build email content
        $html = $campaign->html_content;

        // Replace variables with test data
        $testVariables = [
            '{{contact.first_name}}' => 'Jean',
            '{{contact.last_name}}' => 'Dupont',
            '{{contact.email}}' => $validated['email'],
            '{{contact.company}}' => 'Entreprise Test',
            '{{unsubscribe_url}}' => url('/unsubscribe/test'),
            '{{current_year}}' => date('Y'),
        ];

        foreach ($testVariables as $var => $value) {
            $html = str_replace($var, $value, $html);
        }

        // Send test email
        try {
            Mail::html($html, function ($message) use ($campaign, $validated) {
                $message->to($validated['email'])
                    ->subject('[TEST] ' . $campaign->subject)
                    ->from($campaign->from_email, $campaign->from_name);

                if ($campaign->reply_to) {
                    $message->replyTo($campaign->reply_to);
                }
            });

            return redirect()->back()->with('success', 'Email de test envoyé à ' . $validated['email']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    /**
     * Schedule the campaign for later.
     */
    public function schedule(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->canBeSent()) {
            return redirect()->back()->with('error', 'La campagne n\'est pas prête à être envoyée.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'timezone' => 'nullable|string|max:50',
        ]);

        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at'], $validated['timezone'] ?? 'Africa/Abidjan')
            ->setTimezone('UTC');

        $campaign->schedule($scheduledAt);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campagne programmée pour le ' . $campaign->scheduled_at->format('d/m/Y à H:i'));
    }

    /**
     * Send the campaign immediately.
     */
    public function send(Campaign $campaign): RedirectResponse
    {
        if (!$campaign->canBeSent()) {
            return redirect()->back()->with('error', 'La campagne n\'est pas prête à être envoyée.');
        }

        // Start sending
        $campaign->startSending();

        // Dispatch sending job
        SendCampaignJob::dispatch($campaign);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Envoi de la campagne démarré.');
    }

    /**
     * Pause a sending campaign.
     */
    public function pause(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== Campaign::STATUS_SENDING) {
            return redirect()->back()->with('error', 'Cette campagne n\'est pas en cours d\'envoi.');
        }

        $campaign->pause();

        return redirect()->back()->with('success', 'Campagne mise en pause.');
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== Campaign::STATUS_PAUSED) {
            return redirect()->back()->with('error', 'Cette campagne n\'est pas en pause.');
        }

        $campaign->resume();

        // Resume sending
        SendCampaignJob::dispatch($campaign);

        return redirect()->back()->with('success', 'Campagne reprise.');
    }

    /**
     * Cancel a campaign.
     */
    public function cancel(Campaign $campaign): RedirectResponse
    {
        if (!in_array($campaign->status, [Campaign::STATUS_SCHEDULED, Campaign::STATUS_SENDING, Campaign::STATUS_PAUSED])) {
            return redirect()->back()->with('error', 'Cette campagne ne peut pas être annulée.');
        }

        $campaign->cancel();

        return redirect()->back()->with('success', 'Campagne annulée.');
    }

    /**
     * Duplicate a campaign.
     */
    public function duplicate(Campaign $campaign): RedirectResponse
    {
        $newCampaign = $campaign->duplicate();

        return redirect()->route('campaigns.edit', $newCampaign)
            ->with('success', 'Campagne dupliquée avec succès.');
    }

    /**
     * Delete a campaign.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status === Campaign::STATUS_SENDING) {
            return redirect()->back()->with('error', 'Impossible de supprimer une campagne en cours d\'envoi.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campagne supprimée.');
    }

    /**
     * Preview campaign content.
     */
    public function preview(Campaign $campaign): \Illuminate\Http\Response
    {
        $html = $campaign->html_content ?? '';

        // Replace variables with sample data
        $sampleVariables = [
            '{{contact.first_name}}' => 'Jean',
            '{{contact.last_name}}' => 'Dupont',
            '{{contact.email}}' => 'jean.dupont@example.com',
            '{{contact.company}}' => 'Mon Entreprise',
            '{{unsubscribe_url}}' => '#',
            '{{current_year}}' => date('Y'),
        ];

        foreach ($sampleVariables as $var => $value) {
            $html = str_replace($var, $value, $html);
        }

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Get campaign statistics (for AJAX).
     */
    public function stats(Campaign $campaign): \Illuminate\Http\JsonResponse
    {
        $campaign->refresh();

        return response()->json([
            'sent_count' => $campaign->sent_count,
            'delivered_count' => $campaign->delivered_count,
            'opened_count' => $campaign->opened_count,
            'clicked_count' => $campaign->clicked_count,
            'bounced_count' => $campaign->bounced_count,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'delivery_rate' => $campaign->delivery_rate,
            'bounce_rate' => $campaign->bounce_rate,
        ]);
    }

    /**
     * Update A/B test configuration.
     */
    public function updateAbTest(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isEditable()) {
            return redirect()->back()->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'config' => 'nullable|array',
            'config.test_type' => 'required_if:enabled,true|in:subject,from_name,content',
            'config.test_percentage' => 'required_if:enabled,true|integer|min:10|max:50',
            'config.winning_criteria' => 'required_if:enabled,true|in:opens,clicks',
            'config.wait_hours' => 'required_if:enabled,true|integer|min:1|max:48',
            'config.auto_send_winner' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.key' => 'required|string|max:1',
            'variants.*.subject' => 'nullable|string|max:255',
            'variants.*.from_name' => 'nullable|string|max:255',
            'variants.*.html_content' => 'nullable|string',
            'variants.*.percentage' => 'nullable|integer|min:5|max:50',
        ]);

        if ($validated['enabled']) {
            // Update campaign type and config
            $campaign->update([
                'type' => Campaign::TYPE_AB_TEST,
                'ab_test_config' => $validated['config'],
            ]);

            // Update or create variants
            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $variantData) {
                    CampaignVariant::updateOrCreate(
                        [
                            'campaign_id' => $campaign->id,
                            'variant_key' => $variantData['key'],
                        ],
                        [
                            'name' => 'Variante ' . $variantData['key'],
                            'subject' => $variantData['subject'] ?? null,
                            'from_name' => $variantData['from_name'] ?? null,
                            'html_content' => $variantData['html_content'] ?? null,
                            'percentage' => $variantData['percentage'] ?? 50,
                        ]
                    );
                }
            }
        } else {
            // Disable A/B test
            $campaign->update([
                'type' => Campaign::TYPE_REGULAR,
                'ab_test_config' => null,
            ]);
        }

        return redirect()->route('campaigns.edit', ['campaign' => $campaign->id, 'step' => 5])
            ->with('success', $validated['enabled'] ? 'Test A/B configuré.' : 'Test A/B désactivé.');
    }

    /**
     * Select a winner for A/B test.
     */
    public function selectWinner(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isAbTest()) {
            return redirect()->back()->with('error', 'Cette campagne n\'est pas un test A/B.');
        }

        $validated = $request->validate([
            'variant_key' => 'required|string|max:1',
        ]);

        $variant = $campaign->variants()->where('variant_key', $validated['variant_key'])->first();

        if (!$variant) {
            return redirect()->back()->with('error', 'Variante non trouvée.');
        }

        $variant->markAsWinner();

        return redirect()->back()->with('success', 'Variante ' . $validated['variant_key'] . ' sélectionnée comme gagnante.');
    }

    /**
     * Send the winning variant to remaining recipients.
     */
    public function sendToRemaining(Request $request, Campaign $campaign): RedirectResponse
    {
        if (!$campaign->isAbTest()) {
            return redirect()->back()->with('error', 'Cette campagne n\'est pas un test A/B.');
        }

        $validated = $request->validate([
            'variant_key' => 'required|string|max:1',
        ]);

        $variant = $campaign->variants()->where('variant_key', $validated['variant_key'])->first();

        if (!$variant) {
            return redirect()->back()->with('error', 'Variante non trouvée.');
        }

        // Mark as winner if not already
        if (!$variant->is_winner) {
            $variant->markAsWinner();
        }

        // Update campaign with winning variant content
        $campaign->update([
            'subject' => $variant->subject ?? $campaign->subject,
            'from_name' => $variant->from_name ?? $campaign->from_name,
            'html_content' => $variant->html_content ?? $campaign->html_content,
        ]);

        // Dispatch job to send to remaining recipients
        SendCampaignJob::dispatch($campaign, true); // true = send to remaining only

        return redirect()->back()->with('success', 'Envoi de la variante gagnante aux destinataires restants démarré.');
    }
}
