<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Services\Email\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSingleEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;
    public Contact $contact;
    public ?CampaignVariant $variant;

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
    public function __construct(Campaign $campaign, Contact $contact, ?CampaignVariant $variant = null)
    {
        $this->campaign = $campaign;
        $this->contact = $contact;
        $this->variant = $variant;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        // Refresh models
        $this->campaign->refresh();
        $this->contact->refresh();

        // Check if campaign is still active
        if ($this->campaign->status !== Campaign::STATUS_SENDING) {
            Log::info("Single email skipped - campaign not active", [
                'campaign_id' => $this->campaign->id,
                'contact_id' => $this->contact->id,
            ]);
            return;
        }

        // Check if contact is still eligible
        if ($this->contact->status !== Contact::STATUS_SUBSCRIBED) {
            Log::info("Single email skipped - contact not subscribed", [
                'campaign_id' => $this->campaign->id,
                'contact_id' => $this->contact->id,
            ]);
            return;
        }

        // Send email
        $result = $campaignService->sendToContact($this->campaign, $this->contact, $this->variant);

        if (!$result['success']) {
            Log::warning("Single email send failed", [
                'campaign_id' => $this->campaign->id,
                'contact_id' => $this->contact->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            // Re-throw to trigger retry
            if ($this->attempts() < $this->tries) {
                throw new \Exception($result['error'] ?? 'Email send failed');
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendSingleEmailJob failed permanently", [
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contact->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
