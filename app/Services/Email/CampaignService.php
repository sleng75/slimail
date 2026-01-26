<?php

namespace App\Services\Email;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Contracts\EmailServiceInterface;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    protected EmailService $emailService;
    protected EmailServiceInterface $sesService;

    public function __construct(EmailService $emailService, EmailServiceInterface $sesService)
    {
        $this->emailService = $emailService;
        $this->sesService = $sesService;
    }

    /**
     * Send a campaign email to a single contact.
     */
    public function sendToContact(Campaign $campaign, Contact $contact, ?CampaignVariant $variant = null): array
    {
        // Check if email was already sent to this contact for this campaign
        $alreadySent = SentEmail::where('campaign_id', $campaign->id)
            ->where('contact_id', $contact->id)
            ->exists();

        if ($alreadySent) {
            return ['success' => false, 'error' => 'Email already sent to this contact'];
        }

        // Determine content based on variant or campaign
        $subject = $variant?->subject ?? $campaign->subject;
        $fromName = $variant?->from_name ?? $campaign->from_name;
        $htmlContent = $variant?->html_content ?? $campaign->html_content;
        $previewText = $variant?->preview_text ?? $campaign->preview_text;

        // Process personalization
        $processedSubject = $this->processContent($subject, $contact);
        $processedHtml = $this->processContent($htmlContent, $contact, $campaign);

        // Create sent email record
        $sentEmail = SentEmail::create([
            'tenant_id' => $campaign->tenant_id,
            'contact_id' => $contact->id,
            'campaign_id' => $campaign->id,
            'from_email' => $campaign->from_email,
            'from_name' => $fromName,
            'to_email' => $contact->email,
            'to_name' => $contact->full_name,
            'reply_to' => $campaign->reply_to,
            'subject' => $processedSubject,
            'html_content' => $processedHtml,
            'text_content' => $campaign->text_content ? $this->processContent($campaign->text_content, $contact) : null,
            'type' => SentEmail::TYPE_CAMPAIGN,
            'status' => SentEmail::STATUS_QUEUED,
            'metadata' => $variant ? ['variant_id' => $variant->id] : null,
        ]);

        // Add tracking to HTML
        $htmlWithTracking = $this->addTracking(
            $processedHtml,
            $sentEmail,
            $campaign->track_opens,
            $campaign->track_clicks
        );

        // Send via SES
        $result = $this->sesService->sendEmail([
            'from_email' => $campaign->from_email,
            'from_name' => $fromName,
            'to_email' => $contact->email,
            'subject' => $processedSubject,
            'html_content' => $htmlWithTracking,
            'text_content' => $sentEmail->text_content,
            'reply_to' => $campaign->reply_to,
            'configuration_set' => config('services.ses.configuration_set'),
            'tags' => [
                'tenant_id' => (string) $campaign->tenant_id,
                'email_id' => (string) $sentEmail->id,
                'campaign_id' => (string) $campaign->id,
                'variant_id' => $variant ? (string) $variant->id : '',
                'type' => 'campaign',
            ],
        ]);

        if ($result['success']) {
            $sentEmail->markAsSent($result['message_id']);
            $campaign->incrementStat('sent');

            if ($variant) {
                $variant->increment('sent_count');
            }

            return ['success' => true, 'message_id' => $result['message_id'], 'sent_email_id' => $sentEmail->id];
        } else {
            $sentEmail->markAsFailed($result['error']);
            return ['success' => false, 'error' => $result['error'], 'sent_email_id' => $sentEmail->id];
        }
    }

    /**
     * Process content with personalization variables.
     */
    public function processContent(string $content, Contact $contact, ?Campaign $campaign = null): string
    {
        $variables = [
            // Contact variables
            '{{contact.email}}' => $contact->email,
            '{{contact.first_name}}' => $contact->first_name ?? '',
            '{{contact.last_name}}' => $contact->last_name ?? '',
            '{{contact.full_name}}' => $contact->full_name ?? $contact->email,
            '{{contact.company}}' => $contact->company ?? '',
            '{{contact.phone}}' => $contact->phone ?? '',
            '{{contact.city}}' => $contact->city ?? '',
            '{{contact.country}}' => $contact->country ?? '',

            // Date variables
            '{{current_date}}' => now()->format('d/m/Y'),
            '{{current_year}}' => (string) now()->year,

            // Legacy format support
            '{{email}}' => $contact->email,
            '{{first_name}}' => $contact->first_name ?? '',
            '{{last_name}}' => $contact->last_name ?? '',
            '{{full_name}}' => $contact->full_name ?? $contact->email,
            '{{company}}' => $contact->company ?? '',
        ];

        // Add unsubscribe link
        if ($campaign) {
            $unsubscribeUrl = $this->emailService->generateUnsubscribeLink($contact, $campaign);
            $variables['{{unsubscribe_url}}'] = $unsubscribeUrl;
        }

        // Replace all variables
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        // Handle contact custom fields
        if (!empty($contact->custom_fields)) {
            foreach ($contact->custom_fields as $field => $value) {
                $content = str_replace("{{contact.{$field}}}", $value ?? '', $content);
            }
        }

        return $content;
    }

    /**
     * Add tracking pixel and link tracking to HTML.
     */
    protected function addTracking(string $html, SentEmail $sentEmail, bool $trackOpens, bool $trackClicks): string
    {
        $baseUrl = config('app.url');

        // Add open tracking pixel
        if ($trackOpens) {
            $pixelUrl = "{$baseUrl}/track/open/{$sentEmail->id}";
            $pixel = '<img src="' . $pixelUrl . '" width="1" height="1" style="display:none;border:0;" alt="" />';

            // Insert before </body> if exists, otherwise append
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $pixel . '</body>', $html);
            } else {
                $html .= $pixel;
            }
        }

        // Add click tracking
        if ($trackClicks) {
            $html = preg_replace_callback(
                '/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
                function ($matches) use ($baseUrl, $sentEmail) {
                    $url = $matches[2];

                    // Don't track unsubscribe, mailto, or tel links
                    if (
                        str_contains($url, 'unsubscribe') ||
                        str_starts_with($url, 'mailto:') ||
                        str_starts_with($url, 'tel:') ||
                        str_starts_with($url, '#')
                    ) {
                        return $matches[0];
                    }

                    $trackedUrl = "{$baseUrl}/track/click/{$sentEmail->id}?url=" . urlencode($url);
                    return '<a ' . $matches[1] . $trackedUrl . $matches[3] . '>';
                },
                $html
            );
        }

        return $html;
    }

    /**
     * Get recipients for a campaign, optionally excluding already sent.
     */
    public function getRecipients(Campaign $campaign, bool $excludeSent = true)
    {
        $query = $campaign->getRecipients();

        if ($excludeSent) {
            $sentContactIds = SentEmail::where('campaign_id', $campaign->id)
                ->pluck('contact_id')
                ->toArray();

            if (!empty($sentContactIds)) {
                $query->whereNotIn('contacts.id', $sentContactIds);
            }
        }

        return $query;
    }

    /**
     * Get recipients count for a campaign.
     */
    public function getRecipientsCount(Campaign $campaign, bool $excludeSent = true): int
    {
        return $this->getRecipients($campaign, $excludeSent)->count();
    }

    /**
     * Assign variants to contacts for A/B testing.
     */
    public function assignVariant(Campaign $campaign, Contact $contact): ?CampaignVariant
    {
        if (!$campaign->isAbTest()) {
            return null;
        }

        // If winner is already determined, use the winning variant
        if ($campaign->winning_variant_id) {
            return CampaignVariant::find($campaign->winning_variant_id);
        }

        $variants = $campaign->variants()->orderBy('variant_key')->get();
        if ($variants->isEmpty()) {
            return null;
        }

        // Use contact ID for consistent assignment
        $hashValue = crc32($contact->id . $campaign->id);
        $percentage = $hashValue % 100;

        $cumulative = 0;
        foreach ($variants as $variant) {
            $cumulative += $variant->percentage;
            if ($percentage < $cumulative) {
                return $variant;
            }
        }

        return $variants->first();
    }

    /**
     * Get the winning variant for a campaign.
     */
    public function getWinningVariant(Campaign $campaign): ?CampaignVariant
    {
        if (!$campaign->winning_variant_id) {
            return null;
        }

        return CampaignVariant::find($campaign->winning_variant_id);
    }

    /**
     * Check if A/B test should determine winner.
     */
    public function shouldDetermineWinner(Campaign $campaign): bool
    {
        if (!$campaign->isAbTest() || $campaign->winning_variant_id) {
            return false;
        }

        $config = $campaign->ab_test_config;
        if (empty($config['test_duration_hours'])) {
            return false;
        }

        // Check if test duration has passed
        $testEndTime = $campaign->started_at?->addHours($config['test_duration_hours']);
        return $testEndTime && now()->gte($testEndTime);
    }

    /**
     * Determine the winning variant of an A/B test.
     */
    public function determineWinner(Campaign $campaign): ?CampaignVariant
    {
        if (!$campaign->isAbTest()) {
            return null;
        }

        $config = $campaign->ab_test_config;
        $criteria = $config['winning_criteria'] ?? 'opens';

        $variants = $campaign->variants;
        $winner = null;
        $highestScore = -1;

        foreach ($variants as $variant) {
            if ($variant->sent_count === 0) {
                continue;
            }

            $score = match ($criteria) {
                'clicks' => $variant->click_rate,
                default => $variant->open_rate,
            };

            if ($score > $highestScore) {
                $highestScore = $score;
                $winner = $variant;
            }
        }

        if ($winner) {
            $winner->markAsWinner();
            Log::info("A/B test winner determined", [
                'campaign_id' => $campaign->id,
                'winner_id' => $winner->id,
                'criteria' => $criteria,
                'score' => $highestScore,
            ]);
        }

        return $winner;
    }

    /**
     * Calculate progress for a sending campaign.
     */
    public function getProgress(Campaign $campaign): array
    {
        $total = $campaign->recipients_count;
        $sent = $campaign->sent_count;
        $remaining = max(0, $total - $sent);
        $percentage = $total > 0 ? round(($sent / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'sent' => $sent,
            'remaining' => $remaining,
            'percentage' => $percentage,
        ];
    }

    /**
     * Check if campaign sending is complete.
     */
    public function isSendingComplete(Campaign $campaign): bool
    {
        $remaining = $this->getRecipientsCount($campaign, true);
        return $remaining === 0;
    }

    /**
     * Finalize campaign after all emails are sent.
     */
    public function finalizeCampaign(Campaign $campaign): void
    {
        // Refresh stats
        $this->refreshStats($campaign);

        // Mark as sent
        $campaign->markAsSent();

        Log::info("Campaign completed", [
            'campaign_id' => $campaign->id,
            'sent_count' => $campaign->sent_count,
            'delivered_count' => $campaign->delivered_count,
        ]);
    }

    /**
     * Refresh campaign statistics from sent emails.
     */
    public function refreshStats(Campaign $campaign): void
    {
        $stats = SentEmail::where('campaign_id', $campaign->id)
            ->selectRaw("
                COUNT(*) as sent_count,
                SUM(CASE WHEN status = 'delivered' OR status = 'opened' OR status = 'clicked' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked_count,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced_count,
                SUM(CASE WHEN status = 'complained' THEN 1 ELSE 0 END) as complained_count
            ")
            ->first();

        $campaign->update([
            'sent_count' => $stats->sent_count ?? 0,
            'delivered_count' => $stats->delivered_count ?? 0,
            'opened_count' => $stats->opened_count ?? 0,
            'clicked_count' => $stats->clicked_count ?? 0,
            'bounced_count' => $stats->bounced_count ?? 0,
            'complained_count' => $stats->complained_count ?? 0,
        ]);
    }
}
