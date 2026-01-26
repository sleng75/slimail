<?php

namespace App\Http\Controllers;

use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Services\Tracking\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }
    /**
     * Track email open via pixel.
     *
     * GET /track/open/{sentEmailId}
     */
    public function trackOpen(Request $request, int $sentEmailId): Response
    {
        $sentEmail = SentEmail::find($sentEmailId);

        if ($sentEmail) {
            // Record first open on sent_email
            $isFirstOpen = !$sentEmail->opened_at;
            if ($isFirstOpen) {
                $sentEmail->markAsOpened();

                // Update campaign stats
                if ($sentEmail->campaign_id) {
                    $sentEmail->campaign?->incrementStat('opened');
                }

                // Update variant stats for A/B testing
                if (!empty($sentEmail->metadata['variant_id'])) {
                    \App\Models\CampaignVariant::where('id', $sentEmail->metadata['variant_id'])
                        ->increment('opened_count');
                }
            } else {
                // Just increment counter for repeat opens
                $sentEmail->increment('opens_count');
            }

            // Record event with enriched data (device info, etc.)
            $this->trackingService->recordEvent(
                $sentEmail,
                EmailEvent::TYPE_OPEN,
                $request
            );
        }

        // Return 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }

    /**
     * Track link click and redirect.
     *
     * GET /track/click/{sentEmailId}
     */
    public function trackClick(Request $request, int $sentEmailId)
    {
        $url = $request->query('url');

        if (!$url) {
            abort(400, 'Missing URL parameter');
        }

        $url = urldecode($url);

        // Validate URL to prevent open redirects
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid URL');
        }

        $sentEmail = SentEmail::find($sentEmailId);

        if ($sentEmail) {
            // Record first click on sent_email
            $isFirstClick = !$sentEmail->clicked_at;
            if ($isFirstClick) {
                $sentEmail->markAsClicked();

                // Update campaign stats
                if ($sentEmail->campaign_id) {
                    $sentEmail->campaign?->incrementStat('clicked');
                }

                // Update variant stats for A/B testing
                if (!empty($sentEmail->metadata['variant_id'])) {
                    \App\Models\CampaignVariant::where('id', $sentEmail->metadata['variant_id'])
                        ->increment('clicked_count');
                }
            } else {
                // Just increment counter for repeat clicks
                $sentEmail->increment('clicks_count');
            }

            // Record event with enriched data (device info, etc.)
            $this->trackingService->recordEvent(
                $sentEmail,
                EmailEvent::TYPE_CLICK,
                $request,
                ['link_url' => $url]
            );
        }

        return redirect()->away($url);
    }

    /**
     * Handle unsubscribe request.
     *
     * GET /unsubscribe
     */
    public function unsubscribe(Request $request)
    {
        $contactId = $request->query('c');
        $token = $request->query('t');
        $campaignId = $request->query('campaign');

        if (!$contactId || !$token) {
            return view('emails.unsubscribe-error', [
                'message' => 'Lien de désabonnement invalide.',
            ]);
        }

        // Find contact
        $contact = \App\Models\Contact::find($contactId);

        if (!$contact) {
            return view('emails.unsubscribe-error', [
                'message' => 'Contact non trouvé.',
            ]);
        }

        // Verify token
        $expectedToken = hash_hmac('sha256', $contact->id . $contact->email, config('app.key'));

        if (!hash_equals($expectedToken, $token)) {
            return view('emails.unsubscribe-error', [
                'message' => 'Lien de désabonnement invalide.',
            ]);
        }

        // If POST request, process unsubscribe
        if ($request->isMethod('post')) {
            $contact->update([
                'status' => \App\Models\Contact::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);

            // Record event if campaign specified
            if ($campaignId) {
                $campaign = \App\Models\Campaign::find($campaignId);
                if ($campaign) {
                    $campaign->incrementStat('unsubscribed');

                    // Find sent email and record event
                    $sentEmail = SentEmail::where('campaign_id', $campaignId)
                        ->where('contact_id', $contactId)
                        ->first();

                    if ($sentEmail) {
                        $sentEmail->update(['unsubscribed_at' => now()]);

                        $this->trackingService->recordEvent(
                            $sentEmail,
                            EmailEvent::TYPE_UNSUBSCRIBE,
                            $request
                        );
                    }
                }
            }

            return view('emails.unsubscribe-success', [
                'email' => $contact->email,
            ]);
        }

        // Show confirmation form
        return view('emails.unsubscribe-confirm', [
            'email' => $contact->email,
            'contactId' => $contactId,
            'token' => $token,
            'campaignId' => $campaignId,
        ]);
    }
}
