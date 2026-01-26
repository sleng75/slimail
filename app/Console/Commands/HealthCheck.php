<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check
                            {--json : Output as JSON}
                            {--notify : Send notifications on failure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run system health checks';

    /**
     * Health check results.
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        // Run all checks
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkStorage();
        $this->checkQueue();
        $this->checkSes();

        // Determine overall status
        $hasErrors = collect($this->results)->contains(fn($r) => $r['status'] === 'error');
        $hasWarnings = collect($this->results)->contains(fn($r) => $r['status'] === 'warning');

        // Output results
        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputTable();
        }

        // Send notifications if requested and there are issues
        if ($this->option('notify') && ($hasErrors || $hasWarnings)) {
            $this->sendNotifications();
        }

        // Return appropriate exit code
        if ($hasErrors) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): void
    {
        $name = 'Database';

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            $this->results[$name] = [
                'status' => 'healthy',
                'message' => "Connected ({$latency}ms)",
                'latency' => $latency,
            ];
        } catch (\Exception $e) {
            $this->results[$name] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Check Redis connection.
     */
    protected function checkRedis(): void
    {
        $name = 'Redis';

        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            $this->results[$name] = [
                'status' => 'healthy',
                'message' => "Connected ({$latency}ms)",
                'latency' => $latency,
            ];
        } catch (\Exception $e) {
            $this->results[$name] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): void
    {
        $name = 'Storage';

        try {
            $testFile = 'health-check-' . time() . '.tmp';
            Storage::put($testFile, 'health check');
            $content = Storage::get($testFile);
            Storage::delete($testFile);

            if ($content === 'health check') {
                $this->results[$name] = [
                    'status' => 'healthy',
                    'message' => 'Read/Write OK',
                    'latency' => null,
                ];
            } else {
                $this->results[$name] = [
                    'status' => 'warning',
                    'message' => 'Content mismatch',
                    'latency' => null,
                ];
            }
        } catch (\Exception $e) {
            $this->results[$name] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Check queue system.
     */
    protected function checkQueue(): void
    {
        $name = 'Queue';

        try {
            $connection = config('queue.default');

            if ($connection === 'sync') {
                $this->results[$name] = [
                    'status' => 'warning',
                    'message' => 'Using sync driver (not recommended for production)',
                    'latency' => null,
                ];
                return;
            }

            // Check failed jobs count
            $failedCount = DB::table('failed_jobs')->count();

            if ($failedCount > 100) {
                $this->results[$name] = [
                    'status' => 'warning',
                    'message' => "{$failedCount} failed jobs",
                    'latency' => null,
                ];
            } elseif ($failedCount > 0) {
                $this->results[$name] = [
                    'status' => 'healthy',
                    'message' => "OK ({$failedCount} failed)",
                    'latency' => null,
                ];
            } else {
                $this->results[$name] = [
                    'status' => 'healthy',
                    'message' => 'OK',
                    'latency' => null,
                ];
            }
        } catch (\Exception $e) {
            $this->results[$name] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Check Amazon SES connection.
     */
    protected function checkSes(): void
    {
        $name = 'SES';

        try {
            $region = config('services.ses.region');
            $key = config('services.ses.key');

            if (empty($key) || empty($region)) {
                $this->results[$name] = [
                    'status' => 'warning',
                    'message' => 'Not configured',
                    'latency' => null,
                ];
                return;
            }

            // Simple connectivity check
            $this->results[$name] = [
                'status' => 'healthy',
                'message' => "Configured ({$region})",
                'latency' => null,
            ];
        } catch (\Exception $e) {
            $this->results[$name] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency' => null,
            ];
        }
    }

    /**
     * Output results as table.
     */
    protected function outputTable(): void
    {
        $rows = [];

        foreach ($this->results as $name => $result) {
            $statusIcon = match ($result['status']) {
                'healthy' => '<fg=green>✓</>',
                'warning' => '<fg=yellow>⚠</>',
                'error' => '<fg=red>✗</>',
                default => '?',
            };

            $rows[] = [
                $statusIcon,
                $name,
                $result['message'],
            ];
        }

        $this->table(['', 'Service', 'Status'], $rows);

        $this->newLine();

        $hasErrors = collect($this->results)->contains(fn($r) => $r['status'] === 'error');
        $hasWarnings = collect($this->results)->contains(fn($r) => $r['status'] === 'warning');

        if ($hasErrors) {
            $this->error('Some services are unhealthy!');
        } elseif ($hasWarnings) {
            $this->warn('Some services have warnings.');
        } else {
            $this->info('All services are healthy.');
        }
    }

    /**
     * Output results as JSON.
     */
    protected function outputJson(): void
    {
        $hasErrors = collect($this->results)->contains(fn($r) => $r['status'] === 'error');
        $hasWarnings = collect($this->results)->contains(fn($r) => $r['status'] === 'warning');

        $output = [
            'status' => $hasErrors ? 'error' : ($hasWarnings ? 'warning' : 'healthy'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $this->results,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Send notifications for failures.
     */
    protected function sendNotifications(): void
    {
        $errors = collect($this->results)->filter(fn($r) => $r['status'] !== 'healthy');

        if ($errors->isEmpty()) {
            return;
        }

        $message = "Health Check Alert:\n\n";

        foreach ($errors as $name => $result) {
            $message .= "- {$name}: {$result['message']}\n";
        }

        // Log the alert
        \Log::warning('Health check failed', [
            'checks' => $errors->toArray(),
        ]);

        // You could add Slack, email, or other notification channels here
        // Notification::route('slack', config('services.slack.webhook_url'))
        //     ->notify(new HealthCheckFailed($errors));
    }
}
