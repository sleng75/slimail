<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SentEmail;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;

class SystemMonitoringController extends Controller
{
    /**
     * Display the monitoring dashboard.
     */
    public function index(): Response
    {
        // System health checks
        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'ses' => $this->checkSES(),
        ];

        // Email delivery stats (last 7 days)
        $emailStats = SentEmail::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
            DB::raw('SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced'),
            DB::raw('SUM(CASE WHEN status = "complained" THEN 1 ELSE 0 END) as complained'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // SES reputation
        $sesReputation = $this->getSESReputation();

        // Queue stats
        $queueStats = $this->getQueueStats();

        // Top senders (by email volume)
        $topSenders = Tenant::select('tenants.id', 'tenants.name')
            ->withCount(['sentEmails' => function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderByDesc('sent_emails_count')
            ->limit(10)
            ->get();

        // Error logs (last 24 hours)
        $recentErrors = $this->getRecentErrors();

        return Inertia::render('Admin/Monitoring/Index', [
            'health' => $health,
            'emailStats' => $emailStats,
            'sesReputation' => $sesReputation,
            'queueStats' => $queueStats,
            'topSenders' => $topSenders,
            'recentErrors' => $recentErrors,
        ]);
    }

    /**
     * SES Monitoring page.
     */
    public function ses(): Response
    {
        // Detailed SES statistics
        $deliveryRates = SentEmail::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('AVG(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) * 100 as delivery_rate'),
            DB::raw('AVG(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) * 100 as bounce_rate'),
            DB::raw('AVG(CASE WHEN status = "complained" THEN 1 ELSE 0 END) * 100 as complaint_rate')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Bounce breakdown
        $bounceBreakdown = SentEmail::where('status', 'bounced')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('bounce_type', DB::raw('COUNT(*) as count'))
            ->groupBy('bounce_type')
            ->get();

        // Top bouncing domains
        $topBouncingDomains = SentEmail::where('status', 'bounced')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('SUBSTRING_INDEX(recipient_email, "@", -1) as domain'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('domain')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Sending limits
        $sendingLimits = $this->getSendingLimits();

        return Inertia::render('Admin/Monitoring/SES', [
            'deliveryRates' => $deliveryRates,
            'bounceBreakdown' => $bounceBreakdown,
            'topBouncingDomains' => $topBouncingDomains,
            'sendingLimits' => $sendingLimits,
            'reputation' => $this->getSESReputation(),
        ]);
    }

    /**
     * Queue monitoring page.
     */
    public function queues(): Response
    {
        $queueStats = $this->getDetailedQueueStats();

        $failedJobs = DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(50)
            ->get()
            ->map(fn($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'payload' => json_decode($job->payload, true),
                'exception' => \Str::limit($job->exception, 500),
                'failed_at' => $job->failed_at,
            ]);

        return Inertia::render('Admin/Monitoring/Queues', [
            'queueStats' => $queueStats,
            'failedJobs' => $failedJobs,
        ]);
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(string $uuid)
    {
        \Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Job remis en file d\'attente.');
    }

    /**
     * Delete a failed job.
     */
    public function deleteJob(string $uuid)
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', 'Job supprimé.');
    }

    /**
     * Flush all failed jobs.
     */
    public function flushFailedJobs()
    {
        \Artisan::call('queue:flush');

        return back()->with('success', 'Tous les jobs échoués ont été supprimés.');
    }

    /**
     * Clear application cache.
     */
    public function clearCache()
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');

        return back()->with('success', 'Cache vidé avec succès.');
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $latency = $this->measureLatency(fn() => DB::select('SELECT 1'));

            return [
                'status' => 'healthy',
                'latency' => $latency,
                'message' => 'Connexion OK',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'latency' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection.
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $result = Cache::get($key);
            Cache::forget($key);

            $latency = $this->measureLatency(fn() => Cache::get('test_key'));

            return [
                'status' => $result ? 'healthy' : 'warning',
                'latency' => $latency,
                'message' => $result ? 'Cache OK' : 'Cache non fonctionnel',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'latency' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status.
     */
    protected function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            if ($failedJobs > 100) $status = 'warning';
            if ($failedJobs > 500) $status = 'error';

            return [
                'status' => $status,
                'pending' => $pendingJobs,
                'failed' => $failedJobs,
                'message' => "{$pendingJobs} en attente, {$failedJobs} échoués",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'pending' => null,
                'failed' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage.
     */
    protected function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            $status = 'healthy';
            if ($usedPercent > 80) $status = 'warning';
            if ($usedPercent > 95) $status = 'error';

            return [
                'status' => $status,
                'free' => $this->formatBytes($freeSpace),
                'total' => $this->formatBytes($totalSpace),
                'used_percent' => round($usedPercent, 1),
                'message' => round($usedPercent, 1) . '% utilisé',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check SES status.
     */
    protected function checkSES(): array
    {
        // In production, this would call AWS SES API
        // For now, we'll check based on recent email stats
        try {
            $recentStats = SentEmail::where('created_at', '>=', now()->subHours(1))
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced')
                ->first();

            $bounceRate = $recentStats->total > 0
                ? ($recentStats->bounced / $recentStats->total) * 100
                : 0;

            $status = 'healthy';
            if ($bounceRate > 5) $status = 'warning';
            if ($bounceRate > 10) $status = 'error';

            return [
                'status' => $status,
                'emails_last_hour' => $recentStats->total,
                'bounce_rate' => round($bounceRate, 2),
                'message' => "Taux de rebond: {$bounceRate}%",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SES reputation metrics.
     */
    protected function getSESReputation(): array
    {
        // This would typically come from AWS SES API
        $last30Days = SentEmail::where('created_at', '>=', now()->subDays(30));

        $total = $last30Days->count();
        $bounced = (clone $last30Days)->where('status', 'bounced')->count();
        $complained = (clone $last30Days)->where('status', 'complained')->count();

        return [
            'bounce_rate' => $total > 0 ? round(($bounced / $total) * 100, 2) : 0,
            'complaint_rate' => $total > 0 ? round(($complained / $total) * 100, 4) : 0,
            'reputation_status' => $this->getReputationStatus($bounced, $complained, $total),
            'sending_quota' => 50000, // Would come from AWS
            'max_send_rate' => 14, // emails per second, would come from AWS
        ];
    }

    /**
     * Get reputation status based on rates.
     */
    protected function getReputationStatus(int $bounced, int $complained, int $total): string
    {
        if ($total === 0) return 'unknown';

        $bounceRate = ($bounced / $total) * 100;
        $complaintRate = ($complained / $total) * 100;

        if ($bounceRate > 10 || $complaintRate > 0.5) return 'at_risk';
        if ($bounceRate > 5 || $complaintRate > 0.1) return 'warning';
        return 'healthy';
    }

    /**
     * Get queue statistics.
     */
    protected function getQueueStats(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'processed_today' => 0, // Would need job tracking
        ];
    }

    /**
     * Get detailed queue statistics.
     */
    protected function getDetailedQueueStats(): array
    {
        $queues = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->get();

        return [
            'by_queue' => $queues,
            'total_pending' => $queues->sum('count'),
            'total_failed' => DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * Get sending limits.
     */
    protected function getSendingLimits(): array
    {
        // Would come from AWS SES API
        return [
            'max_24_hour_send' => 50000,
            'max_send_rate' => 14,
            'sent_last_24_hours' => SentEmail::where('created_at', '>=', now()->subHours(24))->count(),
        ];
    }

    /**
     * Get recent errors from logs.
     */
    protected function getRecentErrors(): array
    {
        // In a real app, this would read from log files or a logging service
        return [];
    }

    /**
     * Measure latency of a callback.
     */
    protected function measureLatency(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
