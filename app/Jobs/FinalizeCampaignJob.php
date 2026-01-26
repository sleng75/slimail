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

class FinalizeCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->onQueue('campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        $this->campaign->refresh();

        // Only finalize if still sending
        if ($this->campaign->status !== Campaign::STATUS_SENDING) {
            Log::info("Campaign finalization skipped - not in sending status", [
                'campaign_id' => $this->campaign->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        // Check if all emails have been sent
        $remaining = $campaignService->getRecipientsCount($this->campaign, true);

        if ($remaining > 0) {
            // Still have pending recipients, reschedule
            Log::info("Campaign finalization delayed - pending recipients", [
                'campaign_id' => $this->campaign->id,
                'remaining' => $remaining,
            ]);

            // Reschedule for later
            self::dispatch($this->campaign)->delay(now()->addMinutes(5));
            return;
        }

        // Determine A/B test winner if applicable
        if ($campaignService->shouldDetermineWinner($this->campaign)) {
            $winner = $campaignService->determineWinner($this->campaign);
            if ($winner) {
                Log::info("A/B test winner determined", [
                    'campaign_id' => $this->campaign->id,
                    'winner_variant_id' => $winner->id,
                    'winner_key' => $winner->variant_key,
                ]);
            }
        }

        // Finalize the campaign
        $campaignService->finalizeCampaign($this->campaign);

        Log::info("Campaign finalized", [
            'campaign_id' => $this->campaign->id,
            'sent_count' => $this->campaign->sent_count,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("FinalizeCampaignJob failed", [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
