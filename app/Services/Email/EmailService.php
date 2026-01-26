<?php

namespace App\Services\Email;

use App\Models\ApiKey;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\Tenant;
use App\Contracts\EmailServiceInterface;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected EmailServiceInterface $sesService;

    public function __construct(EmailServiceInterface $sesService)
    {
        $this->sesService = $sesService;
    }

    /**
     * Send a transactional email via API.
     */
    public function sendTransactional(array $params, Tenant $tenant, ?ApiKey $apiKey = null): array
    {
        // Create sent email record
        $sentEmail = SentEmail::create([
            'tenant_id' => $tenant->id,
            'api_key_id' => $apiKey?->id,
            'from_email' => $params['from_email'],
            'from_name' => $params['from_name'] ?? null,
            'to_email' => $params['to_email'],
            'to_name' => $params['to_name'] ?? null,
            'reply_to' => $params['reply_to'] ?? null,
            'subject' => $params['subject'],
            'html_content' => $params['html_content'] ?? null,
            'text_content' => $params['text_content'] ?? null,
            'type' => SentEmail::TYPE_TRANSACTIONAL,
            'status' => SentEmail::STATUS_QUEUED,
            'metadata' => $params['metadata'] ?? null,
        ]);

        // Find contact if exists
        $contact = Contact::where('tenant_id', $tenant->id)
            ->where('email', $params['to_email'])
            ->first();

        if ($contact) {
            $sentEmail->update(['contact_id' => $contact->id]);
        }

        // Prepare SES parameters
        $sesParams = [
            'from_email' => $params['from_email'],
            'from_name' => $params['from_name'] ?? null,
            'to_email' => $params['to_email'],
            'subject' => $params['subject'],
            'html_content' => $this->processContent($params['html_content'] ?? '', $contact, $sentEmail),
            'text_content' => $params['text_content'] ?? null,
            'reply_to' => $params['reply_to'] ?? null,
            'configuration_set' => config('services.ses.configuration_set'),
            'tags' => [
                'tenant_id' => (string) $tenant->id,
                'email_id' => (string) $sentEmail->id,
                'type' => 'transactional',
            ],
        ];

        // Check for attachments
        if (!empty($params['attachments'])) {
            $result = $this->sesService->sendRawEmail($sesParams + ['attachments' => $params['attachments']]);
        } else {
            $result = $this->sesService->sendEmail($sesParams);
        }

        if ($result['success']) {
            $sentEmail->markAsSent($result['message_id']);

            // Record event
            EmailEvent::create([
                'tenant_id' => $tenant->id,
                'sent_email_id' => $sentEmail->id,
                'contact_id' => $contact?->id,
                'event_type' => EmailEvent::TYPE_SEND,
                'message_id' => $result['message_id'],
                'event_at' => now(),
            ]);

            return [
                'success' => true,
                'message_id' => $result['message_id'],
                'email_id' => $sentEmail->id,
            ];
        } else {
            $sentEmail->markAsFailed($result['error']);

            return [
                'success' => false,
                'error' => $result['error'],
                'email_id' => $sentEmail->id,
            ];
        }
    }

    /**
     * Send a campaign email.
     */
    public function sendCampaignEmail(Campaign $campaign, Contact $contact): array
    {
        // Create sent email record
        $sentEmail = SentEmail::create([
            'tenant_id' => $campaign->tenant_id,
            'contact_id' => $contact->id,
            'campaign_id' => $campaign->id,
            'from_email' => $campaign->from_email,
            'from_name' => $campaign->from_name,
            'to_email' => $contact->email,
            'to_name' => $contact->full_name,
            'reply_to' => $campaign->reply_to,
            'subject' => $this->processContent($campaign->subject, $contact),
            'html_content' => $this->processContent($campaign->html_content, $contact),
            'text_content' => $campaign->text_content ? $this->processContent($campaign->text_content, $contact) : null,
            'type' => SentEmail::TYPE_CAMPAIGN,
            'status' => SentEmail::STATUS_QUEUED,
        ]);

        // Build HTML with tracking
        $html = $this->addTracking(
            $sentEmail->html_content,
            $sentEmail,
            $campaign->track_opens,
            $campaign->track_clicks
        );

        // Send via SES
        $result = $this->sesService->sendEmail([
            'from_email' => $sentEmail->from_email,
            'from_name' => $sentEmail->from_name,
            'to_email' => $sentEmail->to_email,
            'subject' => $sentEmail->subject,
            'html_content' => $html,
            'text_content' => $sentEmail->text_content,
            'reply_to' => $sentEmail->reply_to,
            'configuration_set' => config('services.ses.configuration_set'),
            'tags' => [
                'tenant_id' => (string) $campaign->tenant_id,
                'email_id' => (string) $sentEmail->id,
                'campaign_id' => (string) $campaign->id,
                'type' => 'campaign',
            ],
        ]);

        if ($result['success']) {
            $sentEmail->markAsSent($result['message_id']);
            $campaign->incrementStat('sent');

            return ['success' => true, 'message_id' => $result['message_id']];
        } else {
            $sentEmail->markAsFailed($result['error']);

            return ['success' => false, 'error' => $result['error']];
        }
    }

    /**
     * Process content with personalization variables.
     */
    protected function processContent(string $content, ?Contact $contact = null, ?SentEmail $sentEmail = null): string
    {
        $variables = [
            'date' => now()->format('d/m/Y'),
            'year' => now()->year,
        ];

        if ($contact) {
            $variables = array_merge($variables, [
                'email' => $contact->email,
                'first_name' => $contact->first_name ?? '',
                'last_name' => $contact->last_name ?? '',
                'full_name' => $contact->full_name ?? $contact->email,
                'company' => $contact->company ?? '',
                'phone' => $contact->phone ?? '',
                'city' => $contact->city ?? '',
                'country' => $contact->country ?? '',
            ]);
        }

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Add tracking pixel and link tracking.
     */
    protected function addTracking(string $html, SentEmail $sentEmail, bool $trackOpens, bool $trackClicks): string
    {
        $baseUrl = config('app.url');

        // Add open tracking pixel
        if ($trackOpens) {
            $pixel = '<img src="' . $baseUrl . '/track/open/' . $sentEmail->id . '" width="1" height="1" style="display:none;" alt="" />';
            $html = str_replace('</body>', $pixel . '</body>', $html);
        }

        // Add click tracking
        if ($trackClicks) {
            $html = preg_replace_callback(
                '/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
                function ($matches) use ($baseUrl, $sentEmail) {
                    $url = $matches[2];

                    // Don't track unsubscribe links or mailto
                    if (str_contains($url, 'unsubscribe') || str_starts_with($url, 'mailto:')) {
                        return $matches[0];
                    }

                    $trackedUrl = $baseUrl . '/track/click/' . $sentEmail->id . '?url=' . urlencode($url);
                    return '<a ' . $matches[1] . $trackedUrl . $matches[3] . '>';
                },
                $html
            );
        }

        return $html;
    }

    /**
     * Generate unsubscribe link.
     */
    public function generateUnsubscribeLink(Contact $contact, ?Campaign $campaign = null): string
    {
        $token = hash_hmac('sha256', $contact->id . $contact->email, config('app.key'));
        $params = ['c' => $contact->id, 't' => $token];

        if ($campaign) {
            $params['campaign'] = $campaign->id;
        }

        return config('app.url') . '/unsubscribe?' . http_build_query($params);
    }
}
