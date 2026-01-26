<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Services\Email\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;

class SendCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public Campaign $campaign;
    public array $contactIds;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, array $contactIds)
    {
        $this->campaign = $campaign;
        $this->contactIds = $contactIds;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        // Refresh campaign status
        $this->campaign->refresh();

        // Check if campaign is still active
        if (!$this->isActive()) {
            Log::info("Campaign batch skipped - campaign not active", [
                'campaign_id' => $this->campaign->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        $contacts = Contact::whereIn('id', $this->contactIds)->get();
        $successCount = 0;
        $failCount = 0;

        foreach ($contacts as $contact) {
            // Check campaign status between each send (in case of pause)
            $this->campaign->refresh();
            if (!$this->isActive()) {
                Log::info("Campaign batch interrupted", [
                    'campaign_id' => $this->campaign->id,
                    'processed' => $successCount + $failCount,
                    'total' => count($this->contactIds),
                ]);
                break;
            }

            // Skip if contact is not eligible
            if (!$this->isContactEligible($contact)) {
                continue;
            }

            // Assign variant for A/B testing
            $variant = $campaignService->assignVariant($this->campaign, $contact);

            // Send email
            try {
                $result = $campaignService->sendToContact($this->campaign, $contact, $variant);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    Log::warning("Email send failed", [
                        'campaign_id' => $this->campaign->id,
                        'contact_id' => $contact->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Email send exception", [
                    'campaign_id' => $this->campaign->id,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Small delay to avoid rate limiting
            usleep(50000); // 50ms
        }

        Log::info("Campaign batch completed", [
            'campaign_id' => $this->campaign->id,
            'success' => $successCount,
            'failed' => $failCount,
        ]);
    }

    /**
     * Check if campaign is active and can send.
     */
    protected function isActive(): bool
    {
        return $this->campaign->status === Campaign::STATUS_SENDING;
    }

    /**
     * Check if contact is eligible to receive email.
     */
    protected function isContactEligible(Contact $contact): bool
    {
        // Must be subscribed
        if ($contact->status !== Contact::STATUS_SUBSCRIBED) {
            return false;
        }

        // Must have valid email
        if (empty($contact->email) || !filter_var($contact->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendCampaignBatchJob failed", [
            'campaign_id' => $this->campaign->id,
            'contact_ids' => $this->contactIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
