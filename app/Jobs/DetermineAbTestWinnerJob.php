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

class DetermineAbTestWinnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

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

        // Check if already determined
        if ($this->campaign->winning_variant_id) {
            Log::info("A/B test winner already determined", [
                'campaign_id' => $this->campaign->id,
            ]);
            return;
        }

        // Check if it's the right time
        if (!$campaignService->shouldDetermineWinner($this->campaign)) {
            Log::info("A/B test not ready for winner determination", [
                'campaign_id' => $this->campaign->id,
            ]);
            return;
        }

        // Determine winner
        $winner = $campaignService->determineWinner($this->campaign);

        if (!$winner) {
            Log::warning("Could not determine A/B test winner", [
                'campaign_id' => $this->campaign->id,
            ]);
            return;
        }

        Log::info("A/B test winner determined", [
            'campaign_id' => $this->campaign->id,
            'winner_id' => $winner->id,
            'winner_key' => $winner->variant_key,
            'open_rate' => $winner->open_rate,
            'click_rate' => $winner->click_rate,
        ]);

        // Send remaining emails with winning variant
        $config = $this->campaign->ab_test_config;
        $testPercentage = $config['test_percentage'] ?? 100;

        if ($testPercentage < 100) {
            // There are remaining contacts to send to
            $this->sendToRemainingContacts($campaignService, $winner);
        }
    }

    /**
     * Send winning variant to remaining contacts.
     */
    protected function sendToRemainingContacts(CampaignService $campaignService, $winner): void
    {
        $remaining = $campaignService->getRecipientsCount($this->campaign, true);

        if ($remaining > 0) {
            Log::info("Sending winning variant to remaining contacts", [
                'campaign_id' => $this->campaign->id,
                'remaining' => $remaining,
                'winner_id' => $winner->id,
            ]);

            // Continue sending with the winning variant
            // The campaign sending job will pick up the remaining contacts
            if ($this->campaign->status === Campaign::STATUS_PAUSED) {
                $this->campaign->resume();
            }

            SendCampaignJob::dispatch($this->campaign);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DetermineAbTestWinnerJob failed", [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
