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

class ProcessAbTestWinnersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignService $campaignService): void
    {
        // Find A/B test campaigns that are ready for winner determination
        $campaigns = Campaign::where('type', Campaign::TYPE_AB_TEST)
            ->whereIn('status', [Campaign::STATUS_SENDING, Campaign::STATUS_PAUSED])
            ->whereNull('winning_variant_id')
            ->whereNotNull('ab_test_config')
            ->get();

        foreach ($campaigns as $campaign) {
            if ($campaignService->shouldDetermineWinner($campaign)) {
                Log::info("Processing A/B test winner determination", [
                    'campaign_id' => $campaign->id,
                    'name' => $campaign->name,
                ]);

                DetermineAbTestWinnerJob::dispatch($campaign);
            }
        }
    }
}
