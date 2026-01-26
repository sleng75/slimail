<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\Email\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;
    public int $batchSize;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, int $batchSize = 100)
    {
        $this->campaign = $campaign;
        $this->batchSize = $batchSize;
        $this->onQueue('campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        // Refresh campaign status
        $this->campaign->refresh();

        // Check if campaign should continue
        if (!$this->shouldContinue()) {
            Log::info("Campaign sending stopped", [
                'campaign_id' => $this->campaign->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        // Get remaining recipients
        $recipients = $campaignService->getRecipients($this->campaign, true)
            ->limit($this->batchSize)
            ->get();

        if ($recipients->isEmpty()) {
            // All emails sent, finalize campaign
            $this->finalizeCampaign($campaignService);
            return;
        }

        Log::info("Processing campaign batch", [
            'campaign_id' => $this->campaign->id,
            'batch_size' => $recipients->count(),
        ]);

        // Dispatch batch job
        SendCampaignBatchJob::dispatch($this->campaign, $recipients->pluck('id')->toArray());

        // Check if there are more recipients to send
        $remainingCount = $campaignService->getRecipientsCount($this->campaign, true) - $recipients->count();

        if ($remainingCount > 0) {
            // Dispatch next batch with a small delay
            self::dispatch($this->campaign, $this->batchSize)->delay(now()->addSeconds(5));
        } else {
            // Schedule finalization after last batch completes
            FinalizeCampaignJob::dispatch($this->campaign)->delay(now()->addMinutes(2));
        }
    }

    /**
     * Check if campaign should continue sending.
     */
    protected function shouldContinue(): bool
    {
        return in_array($this->campaign->status, [
            Campaign::STATUS_SENDING,
            Campaign::STATUS_SCHEDULED,
        ]);
    }

    /**
     * Finalize the campaign.
     */
    protected function finalizeCampaign(CampaignService $campaignService): void
    {
        $campaignService->finalizeCampaign($this->campaign);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendCampaignJob failed", [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        // Pause the campaign on failure
        if ($this->campaign->status === Campaign::STATUS_SENDING) {
            $this->campaign->pause();
        }
    }
}
