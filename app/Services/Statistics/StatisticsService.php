<?php

namespace App\Services\Statistics;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Check if we're using SQLite.
     */
    protected function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /**
     * Get date format SQL expression compatible with both MySQL and SQLite.
     */
    protected function getDateFormatExpression(string $column, string $format): string
    {
        if ($this->isSqlite()) {
            // Convert MySQL format to SQLite strftime format
            $sqliteFormat = str_replace(
                ['%Y', '%m', '%d', '%W', '%H', '%i', '%s'],
                ['%Y', '%m', '%d', '%W', '%H', '%M', '%S'],
                $format
            );
            return "strftime('{$sqliteFormat}', {$column})";
        }

        return "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Get hour extraction SQL expression compatible with both MySQL and SQLite.
     */
    protected function getHourExpression(string $column): string
    {
        if ($this->isSqlite()) {
            return "CAST(strftime('%H', {$column}) AS INTEGER)";
        }

        return "HOUR({$column})";
    }

    /**
     * Get hour difference SQL expression compatible with both MySQL and SQLite.
     */
    protected function getHourDifferenceExpression(string $column, Carbon $referenceTime): string
    {
        if ($this->isSqlite()) {
            // Calculate hours since reference time
            $refHour = $referenceTime->hour;
            $refDate = $referenceTime->format('Y-m-d');
            return "CAST((julianday({$column}) - julianday('{$refDate}')) * 24 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(HOUR, '{$referenceTime->format('Y-m-d H:i:s')}', {$column})";
    }

    /**
     * Get global statistics overview for a tenant.
     */
    public function getOverview(Tenant $tenant, string $period = '30d'): array
    {
        $dates = $this->getPeriodDates($period);
        $previousDates = $this->getPreviousPeriodDates($period);

        // Current period stats
        $currentStats = $this->getPeriodStats($tenant, $dates['start'], $dates['end']);

        // Previous period stats for comparison
        $previousStats = $this->getPeriodStats($tenant, $previousDates['start'], $previousDates['end']);

        // Calculate changes
        $changes = $this->calculateChanges($currentStats, $previousStats);

        return [
            'period' => $period,
            'start_date' => $dates['start']->toDateString(),
            'end_date' => $dates['end']->toDateString(),
            'stats' => $currentStats,
            'previous' => $previousStats,
            'changes' => $changes,
        ];
    }

    /**
     * Get period statistics.
     */
    protected function getPeriodStats(Tenant $tenant, Carbon $start, Carbon $end): array
    {
        $sentEmails = SentEmail::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$start, $end]);

        $total = $sentEmails->count();
        $delivered = (clone $sentEmails)->where('status', '!=', 'bounced')
            ->where('status', '!=', 'failed')
            ->where('status', '!=', 'rejected')
            ->whereNotNull('delivered_at')
            ->count();
        $opened = (clone $sentEmails)->whereNotNull('opened_at')->count();
        $clicked = (clone $sentEmails)->whereNotNull('clicked_at')->count();
        $bounced = (clone $sentEmails)->where('status', 'bounced')->count();
        $complained = (clone $sentEmails)->where('status', 'complained')->count();

        return [
            'sent' => $total,
            'delivered' => $delivered,
            'opened' => $opened,
            'clicked' => $clicked,
            'bounced' => $bounced,
            'complained' => $complained,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0,
            'bounce_rate' => $total > 0 ? round(($bounced / $total) * 100, 2) : 0,
            'complaint_rate' => $total > 0 ? round(($complained / $total) * 100, 4) : 0,
        ];
    }

    /**
     * Calculate percentage changes between periods.
     */
    protected function calculateChanges(array $current, array $previous): array
    {
        $changes = [];

        foreach (['sent', 'delivered', 'opened', 'clicked', 'bounced'] as $metric) {
            $prevValue = $previous[$metric] ?? 0;
            $currValue = $current[$metric] ?? 0;

            if ($prevValue > 0) {
                $changes[$metric] = round((($currValue - $prevValue) / $prevValue) * 100, 1);
            } else {
                $changes[$metric] = $currValue > 0 ? 100 : 0;
            }
        }

        // Rate changes are absolute differences
        foreach (['delivery_rate', 'open_rate', 'click_rate', 'bounce_rate'] as $rate) {
            $changes[$rate] = round(($current[$rate] ?? 0) - ($previous[$rate] ?? 0), 2);
        }

        return $changes;
    }

    /**
     * Get time series data for charts.
     */
    public function getTimeSeries(Tenant $tenant, string $period = '30d', string $metric = 'sent'): array
    {
        $dates = $this->getPeriodDates($period);
        $groupBy = $this->getGroupByFormat($period);

        $query = SentEmail::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$dates['start'], $dates['end']]);

        // Select the appropriate column based on metric
        $dateColumn = match($metric) {
            'opened' => 'opened_at',
            'clicked' => 'clicked_at',
            'delivered' => 'delivered_at',
            'bounced' => 'bounced_at',
            default => 'created_at',
        };

        // Apply filter for specific metrics
        if ($metric === 'opened') {
            $query->whereNotNull('opened_at');
        } elseif ($metric === 'clicked') {
            $query->whereNotNull('clicked_at');
        } elseif ($metric === 'delivered') {
            $query->whereNotNull('delivered_at');
        } elseif ($metric === 'bounced') {
            $query->where('status', 'bounced');
        }

        $dateFormatExpr = $this->getDateFormatExpression($dateColumn, $groupBy);

        $data = $query
            ->select(DB::raw("{$dateFormatExpr} as date"), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing dates with zeros
        $filledData = $this->fillMissingDates($data, $dates['start'], $dates['end'], $period);

        return [
            'labels' => array_keys($filledData),
            'data' => array_values($filledData),
        ];
    }

    /**
     * Get multiple metrics time series.
     */
    public function getMultipleTimeSeries(Tenant $tenant, string $period = '30d'): array
    {
        return [
            'sent' => $this->getTimeSeries($tenant, $period, 'sent'),
            'opened' => $this->getTimeSeries($tenant, $period, 'opened'),
            'clicked' => $this->getTimeSeries($tenant, $period, 'clicked'),
        ];
    }

    /**
     * Get top performing campaigns.
     */
    public function getTopCampaigns(Tenant $tenant, int $limit = 5, string $orderBy = 'open_rate'): Collection
    {
        // Get campaigns and sort in PHP since open_rate/click_rate are computed attributes
        $campaigns = Campaign::where('tenant_id', $tenant->id)
            ->where('status', Campaign::STATUS_SENT)
            ->where('sent_count', '>', 0)
            ->get();

        // Sort by the computed attribute
        $sorted = $campaigns->sortByDesc(function ($campaign) use ($orderBy) {
            return match ($orderBy) {
                'click_rate' => $campaign->click_rate,
                'sent_count' => $campaign->sent_count,
                'opened_count' => $campaign->opened_count,
                default => $campaign->open_rate,
            };
        });

        return $sorted->take($limit)->values()->map(function ($campaign) {
            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'sent_count' => $campaign->sent_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'sent_at' => $campaign->sent_at?->format('Y-m-d H:i'),
            ];
        });
    }

    /**
     * Get device breakdown.
     */
    public function getDeviceBreakdown(Tenant $tenant, string $period = '30d'): array
    {
        $dates = $this->getPeriodDates($period);

        $devices = EmailEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'open')
            ->whereBetween('event_at', [$dates['start'], $dates['end']])
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        $total = array_sum($devices);

        return [
            'desktop' => [
                'count' => $devices['desktop'] ?? 0,
                'percentage' => $total > 0 ? round((($devices['desktop'] ?? 0) / $total) * 100, 1) : 0,
            ],
            'mobile' => [
                'count' => $devices['mobile'] ?? 0,
                'percentage' => $total > 0 ? round((($devices['mobile'] ?? 0) / $total) * 100, 1) : 0,
            ],
            'tablet' => [
                'count' => $devices['tablet'] ?? 0,
                'percentage' => $total > 0 ? round((($devices['tablet'] ?? 0) / $total) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Get email client breakdown.
     */
    public function getEmailClientBreakdown(Tenant $tenant, string $period = '30d'): array
    {
        $dates = $this->getPeriodDates($period);

        return EmailEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'open')
            ->whereBetween('event_at', [$dates['start'], $dates['end']])
            ->whereNotNull('client_name')
            ->select('client_name', DB::raw('COUNT(*) as count'))
            ->groupBy('client_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->client_name,
                    'count' => $row->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get link click breakdown for a campaign.
     */
    public function getLinkClickBreakdown(Campaign $campaign): array
    {
        return EmailEvent::where('event_type', 'click')
            ->whereHas('sentEmail', function ($q) use ($campaign) {
                $q->where('campaign_id', $campaign->id);
            })
            ->whereNotNull('link_url')
            ->select('link_url', DB::raw('COUNT(*) as clicks'), DB::raw('COUNT(DISTINCT contact_id) as unique_clicks'))
            ->groupBy('link_url')
            ->orderByDesc('clicks')
            ->get()
            ->map(function ($row) {
                return [
                    'url' => $row->link_url,
                    'clicks' => $row->clicks,
                    'unique_clicks' => $row->unique_clicks,
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly distribution of opens.
     */
    public function getHourlyDistribution(Tenant $tenant, string $period = '30d'): array
    {
        $dates = $this->getPeriodDates($period);

        $hourExpr = $this->getHourExpression('event_at');

        $data = EmailEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'open')
            ->whereBetween('event_at', [$dates['start'], $dates['end']])
            ->select(DB::raw("{$hourExpr} as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill all 24 hours
        $filled = [];
        for ($i = 0; $i < 24; $i++) {
            $filled[$i] = $data[$i] ?? 0;
        }

        return [
            'labels' => array_map(fn($h) => sprintf('%02d:00', $h), array_keys($filled)),
            'data' => array_values($filled),
        ];
    }

    /**
     * Get campaign statistics.
     */
    public function getCampaignStats(Campaign $campaign): array
    {
        $sentEmails = SentEmail::where('campaign_id', $campaign->id);

        $total = $sentEmails->count();
        $stats = [
            'total_recipients' => $total,
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->delivered_count,
            'opened' => $campaign->opened_count,
            'clicked' => $campaign->clicked_count,
            'bounced' => $campaign->bounced_count,
            'complained' => $campaign->complained_count,
            'unsubscribed' => $campaign->unsubscribed_count,
            'delivery_rate' => $campaign->delivery_rate,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'bounce_rate' => $campaign->bounce_rate,
        ];

        // Get timeline of events
        $timeline = $this->getCampaignTimeline($campaign);

        // Get link breakdown
        $links = $this->getLinkClickBreakdown($campaign);

        // Get device breakdown for this campaign
        $devices = $this->getCampaignDeviceBreakdown($campaign);

        return [
            'stats' => $stats,
            'timeline' => $timeline,
            'links' => $links,
            'devices' => $devices,
        ];
    }

    /**
     * Get campaign event timeline.
     */
    protected function getCampaignTimeline(Campaign $campaign): array
    {
        if (!$campaign->sent_at) {
            return ['labels' => [], 'opens' => [], 'clicks' => []];
        }

        // Get data for 24 hours after send
        $start = $campaign->sent_at;
        $end = $campaign->sent_at->copy()->addHours(24);

        $hourDiffExpr = $this->getHourDifferenceExpression('event_at', $start);

        $opens = EmailEvent::whereHas('sentEmail', fn($q) => $q->where('campaign_id', $campaign->id))
            ->where('event_type', 'open')
            ->whereBetween('event_at', [$start, $end])
            ->select(DB::raw("{$hourDiffExpr} as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        $clicks = EmailEvent::whereHas('sentEmail', fn($q) => $q->where('campaign_id', $campaign->id))
            ->where('event_type', 'click')
            ->whereBetween('event_at', [$start, $end])
            ->select(DB::raw("{$hourDiffExpr} as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        $labels = [];
        $opensData = [];
        $clicksData = [];

        for ($i = 0; $i < 24; $i++) {
            $labels[] = "+{$i}h";
            $opensData[] = $opens[$i] ?? 0;
            $clicksData[] = $clicks[$i] ?? 0;
        }

        return [
            'labels' => $labels,
            'opens' => $opensData,
            'clicks' => $clicksData,
        ];
    }

    /**
     * Get device breakdown for a campaign.
     */
    protected function getCampaignDeviceBreakdown(Campaign $campaign): array
    {
        $devices = EmailEvent::whereHas('sentEmail', fn($q) => $q->where('campaign_id', $campaign->id))
            ->where('event_type', 'open')
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        $total = array_sum($devices);

        return [
            'desktop' => $total > 0 ? round((($devices['desktop'] ?? 0) / $total) * 100, 1) : 0,
            'mobile' => $total > 0 ? round((($devices['mobile'] ?? 0) / $total) * 100, 1) : 0,
            'tablet' => $total > 0 ? round((($devices['tablet'] ?? 0) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get bounce alerts if rate is too high.
     */
    public function getBounceAlerts(Tenant $tenant): array
    {
        $alerts = [];

        // Check recent bounce rate
        $recentStats = $this->getPeriodStats($tenant, now()->subDays(7), now());

        if ($recentStats['bounce_rate'] > 5) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Taux de rebond élevé',
                'message' => "Votre taux de rebond des 7 derniers jours est de {$recentStats['bounce_rate']}%. Cela peut affecter votre réputation d'expéditeur.",
                'action' => 'Nettoyez votre liste de contacts',
            ];
        } elseif ($recentStats['bounce_rate'] > 2) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Taux de rebond à surveiller',
                'message' => "Votre taux de rebond est de {$recentStats['bounce_rate']}%. Surveillez cette métrique.",
            ];
        }

        // Check complaint rate
        if ($recentStats['complaint_rate'] > 0.1) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Taux de plaintes élevé',
                'message' => "Vous avez reçu {$recentStats['complained']} plaintes spam. Revoyez vos pratiques d'envoi.",
            ];
        }

        return $alerts;
    }

    /**
     * Get period dates.
     */
    protected function getPeriodDates(string $period): array
    {
        $end = now();

        $start = match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '12m' => now()->subMonths(12),
            default => now()->subDays(30),
        };

        return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
    }

    /**
     * Get previous period dates for comparison.
     */
    protected function getPreviousPeriodDates(string $period): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '12m' => 365,
            default => 30,
        };

        return [
            'start' => now()->subDays($days * 2)->startOfDay(),
            'end' => now()->subDays($days)->endOfDay(),
        ];
    }

    /**
     * Get group by format based on period.
     */
    protected function getGroupByFormat(string $period): string
    {
        return match($period) {
            '7d' => '%Y-%m-%d',
            '30d' => '%Y-%m-%d',
            '90d' => '%Y-%W', // Week
            '12m' => '%Y-%m', // Month
            default => '%Y-%m-%d',
        };
    }

    /**
     * Fill missing dates with zeros.
     */
    protected function fillMissingDates(array $data, Carbon $start, Carbon $end, string $period): array
    {
        $filled = [];
        $current = $start->copy();
        $format = match($period) {
            '7d', '30d' => 'Y-m-d',
            '90d' => 'Y-W',
            '12m' => 'Y-m',
            default => 'Y-m-d',
        };

        $interval = match($period) {
            '7d', '30d' => 'day',
            '90d' => 'week',
            '12m' => 'month',
            default => 'day',
        };

        while ($current <= $end) {
            $key = $current->format($format);
            $filled[$key] = $data[$key] ?? 0;

            $current = match($interval) {
                'day' => $current->addDay(),
                'week' => $current->addWeek(),
                'month' => $current->addMonth(),
            };
        }

        return $filled;
    }
}
