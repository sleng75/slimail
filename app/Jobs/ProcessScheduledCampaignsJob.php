<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledCampaignsJob implements ShouldQueue
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
    public function handle(): void
    {
        // Find all scheduled campaigns ready to be sent
        $campaigns = Campaign::readyToSend()->get();

        Log::info("Processing scheduled campaigns", [
            'count' => $campaigns->count(),
        ]);

        foreach ($campaigns as $campaign) {
            // Start sending
            $campaign->startSending();

            // Dispatch the sending job
            SendCampaignJob::dispatch($campaign);

            Log::info("Scheduled campaign started", [
                'campaign_id' => $campaign->id,
                'name' => $campaign->name,
                'scheduled_at' => $campaign->scheduled_at,
            ]);
        }
    }
}
