<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutomationEnrollmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('automations');
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService): void
    {
        $startTime = microtime(true);

        $processed = $automationService->processEnrollments();

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($processed > 0) {
            Log::info('Automation enrollments processed', [
                'processed' => $processed,
                'duration_ms' => $duration,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAutomationEnrollmentsJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
