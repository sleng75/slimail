<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenant = $user->tenant;

        // Get overview statistics
        $overview = $this->statisticsService->getOverview($tenant, '30d');
        $stats = $overview['stats'];
        $changes = $overview['changes'];

        // Get recent campaigns (with tenant scope)
        $recentCampaigns = Campaign::where('tenant_id', $tenant->id)
            ->where('status', Campaign::STATUS_SENT)
            ->orderByDesc('completed_at')
            ->take(5)
            ->get()
            ->map(fn($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'sent_count' => $campaign->sent_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'completed_at' => $campaign->completed_at?->format('d/m/Y H:i'),
            ]);

        // Get email activity for chart (last 7 days)
        $emailActivity = $this->statisticsService->getMultipleTimeSeries($tenant, '7d');

        // Prepare dashboard data
        $dashboardStats = [
            'emailsSent' => $stats['sent'],
            'emailsDelivered' => $stats['delivered'],
            'emailsOpened' => $stats['opened'],
            'emailsClicked' => $stats['clicked'],
            'contacts' => Contact::where('tenant_id', $tenant->id)->subscribed()->count(),
            'campaigns' => Campaign::where('tenant_id', $tenant->id)->sent()->count(),
            'openRate' => round($stats['open_rate'], 1),
            'clickRate' => round($stats['click_rate'], 1),
            'deliveryRate' => round($stats['delivery_rate'], 1),
            'bounceRate' => round($stats['bounce_rate'], 1),
            'changes' => [
                'sent' => $changes['sent'] ?? 0,
                'delivered' => $changes['delivered'] ?? 0,
                'opened' => $changes['opened'] ?? 0,
                'clicked' => $changes['clicked'] ?? 0,
            ],
        ];

        return Inertia::render('Dashboard', [
            'stats' => $dashboardStats,
            'recentCampaigns' => $recentCampaigns,
            'emailActivity' => $emailActivity,
            'period' => '30d',
        ]);
    }
}
