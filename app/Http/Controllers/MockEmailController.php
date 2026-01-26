<?php

namespace App\Http\Controllers;

use App\Contracts\EmailServiceInterface;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Services\Email\CampaignService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MockEmailController extends Controller
{
    protected EmailServiceInterface $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show mock email status and testing interface.
     */
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        // Get recent sent emails
        $recentEmails = SentEmail::where('tenant_id', $tenant->id)
            ->with(['contact', 'campaign'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(function ($email) {
                return [
                    'id' => $email->id,
                    'to' => $email->contact?->email ?? $email->to_email ?? 'N/A',
                    'subject' => $email->subject,
                    'campaign' => $email->campaign?->name ?? 'Transactionnel',
                    'status' => $email->status,
                    'message_id' => $email->message_id,
                    'sent_at' => $email->created_at->format('d/m/Y H:i:s'),
                    'opened_at' => $email->opened_at?->format('d/m/Y H:i:s'),
                    'clicked_at' => $email->clicked_at?->format('d/m/Y H:i:s'),
                ];
            });

        // Get SES status
        $sesStatus = [
            'is_mock_mode' => $this->emailService->isMockMode(),
            'is_configured' => $this->emailService->isConfigured(),
            'quota' => $this->emailService->getSendQuota(),
        ];

        return Inertia::render('Dev/MockEmails', [
            'recentEmails' => $recentEmails,
            'sesStatus' => $sesStatus,
        ]);
    }

    /**
     * Send a test email.
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'to_email' => 'required|email',
            'subject' => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        $tenant = $request->user()->tenant;

        $result = $this->emailService->sendEmail([
            'from_email' => $tenant->default_from_email ?? 'test@slimail.local',
            'from_name' => $tenant->default_from_name ?? 'SliMail Test',
            'to_email' => $validated['to_email'],
            'subject' => $validated['subject'],
            'html_content' => $validated['content'],
            'text_content' => strip_tags($validated['content']),
        ]);

        if ($result['success']) {
            // Create a sent_email record for tracking
            SentEmail::create([
                'tenant_id' => $tenant->id,
                'message_id' => $result['message_id'],
                'from_email' => $tenant->default_from_email ?? 'test@slimail.local',
                'from_name' => $tenant->default_from_name ?? 'SliMail Test',
                'to_email' => $validated['to_email'],
                'subject' => $validated['subject'],
                'html_content' => $validated['content'],
                'text_content' => strip_tags($validated['content']),
                'type' => SentEmail::TYPE_TRANSACTIONAL,
                'status' => SentEmail::STATUS_SENT,
                'sent_at' => now(),
            ]);

            return back()->with('success', "Email de test envoyé! Message ID: {$result['message_id']}");
        }

        return back()->with('error', "Erreur d'envoi: {$result['error']}");
    }

    /**
     * Simulate a webhook event (for testing).
     */
    public function simulateEvent(Request $request, SentEmail $sentEmail)
    {
        $validated = $request->validate([
            'event_type' => 'required|in:delivery,bounce,complaint,open,click',
        ]);

        if (!$this->emailService->isMockMode()) {
            return back()->with('error', 'Les événements simulés ne sont disponibles qu\'en mode mock.');
        }

        // Simulate the event
        $eventType = $validated['event_type'];

        switch ($eventType) {
            case 'delivery':
                $sentEmail->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);
                if ($sentEmail->campaign) {
                    $sentEmail->campaign->increment('delivered_count');
                }
                break;

            case 'bounce':
                $sentEmail->update([
                    'status' => 'bounced',
                    'bounced_at' => now(),
                ]);
                if ($sentEmail->campaign) {
                    $sentEmail->campaign->increment('bounced_count');
                }
                if ($sentEmail->contact) {
                    $sentEmail->contact->update(['status' => Contact::STATUS_BOUNCED]);
                }
                break;

            case 'complaint':
                $sentEmail->update([
                    'status' => 'complained',
                    'complained_at' => now(),
                ]);
                if ($sentEmail->campaign) {
                    $sentEmail->campaign->increment('complained_count');
                }
                if ($sentEmail->contact) {
                    $sentEmail->contact->update(['status' => Contact::STATUS_COMPLAINED]);
                }
                break;

            case 'open':
                if (!$sentEmail->opened_at) {
                    $sentEmail->update(['opened_at' => now()]);
                    if ($sentEmail->campaign) {
                        $sentEmail->campaign->increment('opened_count');
                    }
                }
                $sentEmail->increment('opens_count');
                break;

            case 'click':
                if (!$sentEmail->clicked_at) {
                    $sentEmail->update(['clicked_at' => now()]);
                    if ($sentEmail->campaign) {
                        $sentEmail->campaign->increment('clicked_count');
                    }
                }
                $sentEmail->increment('clicks_count');
                break;
        }

        return back()->with('success', "Événement '{$eventType}' simulé avec succès!");
    }

    /**
     * Get the current SES mode status (for API/AJAX).
     */
    public function status()
    {
        return response()->json([
            'mock_mode' => $this->emailService->isMockMode(),
            'configured' => $this->emailService->isConfigured(),
            'quota' => $this->emailService->getSendQuota(),
        ]);
    }
}
