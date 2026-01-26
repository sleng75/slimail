<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\Statistics\StatisticsService;
use App\Services\Export\ReportExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    protected StatisticsService $statisticsService;
    protected ReportExportService $reportExportService;

    public function __construct(
        StatisticsService $statisticsService,
        ReportExportService $reportExportService
    ) {
        $this->statisticsService = $statisticsService;
        $this->reportExportService = $reportExportService;
    }

    /**
     * Display the global statistics dashboard.
     */
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $period = $request->get('period', '30d');

        // Get overview statistics
        $overview = $this->statisticsService->getOverview($tenant, $period);

        // Get time series data
        $timeSeries = $this->statisticsService->getMultipleTimeSeries($tenant, $period);

        // Get top campaigns
        $topCampaigns = $this->statisticsService->getTopCampaigns($tenant);

        // Get device breakdown
        $devices = $this->statisticsService->getDeviceBreakdown($tenant, $period);

        // Get email client breakdown
        $emailClients = $this->statisticsService->getEmailClientBreakdown($tenant, $period);

        // Get hourly distribution
        $hourlyDistribution = $this->statisticsService->getHourlyDistribution($tenant, $period);

        // Get alerts
        $alerts = $this->statisticsService->getBounceAlerts($tenant);

        return Inertia::render('Statistics/Index', [
            'overview' => $overview,
            'timeSeries' => $timeSeries,
            'topCampaigns' => $topCampaigns,
            'devices' => $devices,
            'emailClients' => $emailClients,
            'hourlyDistribution' => $hourlyDistribution,
            'alerts' => $alerts,
            'period' => $period,
            'periods' => [
                ['value' => '7d', 'label' => '7 derniers jours'],
                ['value' => '30d', 'label' => '30 derniers jours'],
                ['value' => '90d', 'label' => '90 derniers jours'],
                ['value' => '12m', 'label' => '12 derniers mois'],
            ],
        ]);
    }

    /**
     * Get statistics data via AJAX (for period changes).
     */
    public function getData(Request $request)
    {
        $tenant = $request->user()->tenant;
        $period = $request->get('period', '30d');

        return response()->json([
            'overview' => $this->statisticsService->getOverview($tenant, $period),
            'timeSeries' => $this->statisticsService->getMultipleTimeSeries($tenant, $period),
            'devices' => $this->statisticsService->getDeviceBreakdown($tenant, $period),
            'emailClients' => $this->statisticsService->getEmailClientBreakdown($tenant, $period),
            'hourlyDistribution' => $this->statisticsService->getHourlyDistribution($tenant, $period),
        ]);
    }

    /**
     * Display campaign-specific statistics.
     */
    public function campaign(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $stats = $this->statisticsService->getCampaignStats($campaign);

        return Inertia::render('Statistics/Campaign', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'sent_at' => $campaign->sent_at?->format('d/m/Y H:i'),
                'created_at' => $campaign->created_at->format('d/m/Y H:i'),
            ],
            'stats' => $stats['stats'],
            'timeline' => $stats['timeline'],
            'links' => $stats['links'],
            'devices' => $stats['devices'],
        ]);
    }

    /**
     * Export statistics as CSV.
     */
    public function exportCsv(Request $request)
    {
        $tenant = $request->user()->tenant;
        $period = $request->get('period', '30d');

        $overview = $this->statisticsService->getOverview($tenant, $period);

        $filename = 'statistiques_' . $period . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($overview) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, ['Métrique', 'Valeur', 'Variation']);

            // Data
            $metrics = [
                ['Emails envoyés', $overview['stats']['sent'], $overview['changes']['sent'] . '%'],
                ['Emails délivrés', $overview['stats']['delivered'], $overview['changes']['delivered'] . '%'],
                ['Emails ouverts', $overview['stats']['opened'], $overview['changes']['opened'] . '%'],
                ['Clics', $overview['stats']['clicked'], $overview['changes']['clicked'] . '%'],
                ['Rebonds', $overview['stats']['bounced'], $overview['changes']['bounced'] . '%'],
                ['Plaintes', $overview['stats']['complained'], '-'],
                ['Taux de délivrabilité', $overview['stats']['delivery_rate'] . '%', $overview['changes']['delivery_rate'] . ' pts'],
                ['Taux d\'ouverture', $overview['stats']['open_rate'] . '%', $overview['changes']['open_rate'] . ' pts'],
                ['Taux de clic', $overview['stats']['click_rate'] . '%', $overview['changes']['click_rate'] . ' pts'],
                ['Taux de rebond', $overview['stats']['bounce_rate'] . '%', $overview['changes']['bounce_rate'] . ' pts'],
            ];

            foreach ($metrics as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export campaign statistics as CSV.
     */
    public function exportCampaignCsv(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $stats = $this->statisticsService->getCampaignStats($campaign);

        $filename = 'campagne_' . $campaign->id . '_stats_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($campaign, $stats) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Campaign info
            fputcsv($file, ['Campagne', $campaign->name]);
            fputcsv($file, ['Objet', $campaign->subject]);
            fputcsv($file, ['Date d\'envoi', $campaign->sent_at?->format('d/m/Y H:i') ?? 'Non envoyée']);
            fputcsv($file, []);

            // Statistics
            fputcsv($file, ['Statistiques']);
            fputcsv($file, ['Métrique', 'Valeur', 'Taux']);

            $rows = [
                ['Total destinataires', $stats['stats']['total_recipients'], '-'],
                ['Envoyés', $stats['stats']['sent'], '-'],
                ['Délivrés', $stats['stats']['delivered'], $stats['stats']['delivery_rate'] . '%'],
                ['Ouverts', $stats['stats']['opened'], $stats['stats']['open_rate'] . '%'],
                ['Cliqués', $stats['stats']['clicked'], $stats['stats']['click_rate'] . '%'],
                ['Rebonds', $stats['stats']['bounced'], $stats['stats']['bounce_rate'] . '%'],
                ['Plaintes', $stats['stats']['complained'], '-'],
                ['Désabonnements', $stats['stats']['unsubscribed'], '-'],
            ];

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            // Links
            if (!empty($stats['links'])) {
                fputcsv($file, []);
                fputcsv($file, ['Liens cliqués']);
                fputcsv($file, ['URL', 'Clics totaux', 'Clics uniques']);

                foreach ($stats['links'] as $link) {
                    fputcsv($file, [$link['url'], $link['clicks'], $link['unique_clicks']]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export statistics as printable PDF view.
     */
    public function exportPdf(Request $request)
    {
        $tenant = $request->user()->tenant;
        $period = $request->get('period', '30d');

        $overview = $this->statisticsService->getOverview($tenant, $period);
        $topCampaigns = $this->statisticsService->getTopCampaigns($tenant);
        $devices = $this->statisticsService->getDeviceBreakdown($tenant, $period);

        $periodLabels = [
            '7d' => '7 derniers jours',
            '30d' => '30 derniers jours',
            '90d' => '90 derniers jours',
            '12m' => '12 derniers mois',
        ];

        return view('reports.statistics-pdf', [
            'tenant' => $tenant,
            'period' => $period,
            'periodLabel' => $periodLabels[$period] ?? $period,
            'overview' => $overview,
            'topCampaigns' => $topCampaigns,
            'devices' => $devices,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Export campaign statistics as printable PDF view.
     */
    public function exportCampaignPdf(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $stats = $this->statisticsService->getCampaignStats($campaign);

        return view('reports.campaign-pdf', [
            'campaign' => $campaign,
            'stats' => $stats['stats'],
            'links' => $stats['links'],
            'devices' => $stats['devices'],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}
