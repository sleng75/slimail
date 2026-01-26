<?php

namespace App\Services\Export;

use App\Models\Campaign;
use App\Models\Tenant;
use App\Services\Statistics\StatisticsService;
use Illuminate\Support\Facades\View;

class ReportExportService
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Export global statistics as CSV.
     */
    public function exportGlobalCsv(Tenant $tenant, string $period = '30d'): array
    {
        $overview = $this->statisticsService->getOverview($tenant, $period);

        $filename = 'statistiques_' . $period . '_' . now()->format('Y-m-d') . '.csv';

        $rows = [
            ['Rapport de statistiques - SliMail'],
            ['Période', $this->getPeriodLabel($period)],
            ['Date du rapport', now()->format('d/m/Y H:i')],
            [],
            ['Métriques principales'],
            ['Métrique', 'Valeur', 'Variation'],
            ['Emails envoyés', $overview['stats']['sent'], $overview['changes']['sent'] . '%'],
            ['Emails délivrés', $overview['stats']['delivered'], $overview['changes']['delivered'] . '%'],
            ['Emails ouverts', $overview['stats']['opened'], $overview['changes']['opened'] . '%'],
            ['Clics', $overview['stats']['clicked'], $overview['changes']['clicked'] . '%'],
            ['Rebonds', $overview['stats']['bounced'], $overview['changes']['bounced'] . '%'],
            ['Plaintes', $overview['stats']['complained'], '-'],
            [],
            ['Taux de performance'],
            ['Métrique', 'Taux', 'Variation'],
            ['Taux de délivrabilité', $overview['stats']['delivery_rate'] . '%', $overview['changes']['delivery_rate'] . ' pts'],
            ['Taux d\'ouverture', $overview['stats']['open_rate'] . '%', $overview['changes']['open_rate'] . ' pts'],
            ['Taux de clic', $overview['stats']['click_rate'] . '%', $overview['changes']['click_rate'] . ' pts'],
            ['Taux de rebond', $overview['stats']['bounce_rate'] . '%', $overview['changes']['bounce_rate'] . ' pts'],
        ];

        return [
            'filename' => $filename,
            'rows' => $rows,
        ];
    }

    /**
     * Export campaign statistics as CSV.
     */
    public function exportCampaignCsv(Campaign $campaign): array
    {
        $stats = $this->statisticsService->getCampaignStats($campaign);

        $filename = 'campagne_' . $campaign->id . '_stats_' . now()->format('Y-m-d') . '.csv';

        $rows = [
            ['Rapport de campagne - SliMail'],
            ['Campagne', $campaign->name],
            ['Objet', $campaign->subject],
            ['Date d\'envoi', $campaign->sent_at?->format('d/m/Y H:i') ?? 'Non envoyée'],
            ['Date du rapport', now()->format('d/m/Y H:i')],
            [],
            ['Statistiques'],
            ['Métrique', 'Valeur', 'Taux'],
            ['Total destinataires', $stats['stats']['total_recipients'], '-'],
            ['Envoyés', $stats['stats']['sent'], '-'],
            ['Délivrés', $stats['stats']['delivered'], $stats['stats']['delivery_rate'] . '%'],
            ['Ouverts', $stats['stats']['opened'], $stats['stats']['open_rate'] . '%'],
            ['Cliqués', $stats['stats']['clicked'], $stats['stats']['click_rate'] . '%'],
            ['Rebonds', $stats['stats']['bounced'], $stats['stats']['bounce_rate'] . '%'],
            ['Plaintes', $stats['stats']['complained'], '-'],
            ['Désabonnements', $stats['stats']['unsubscribed'], '-'],
        ];

        // Add links section
        if (!empty($stats['links'])) {
            $rows[] = [];
            $rows[] = ['Liens cliqués'];
            $rows[] = ['URL', 'Clics totaux', 'Clics uniques'];

            foreach ($stats['links'] as $link) {
                $rows[] = [$link['url'], $link['clicks'], $link['unique_clicks']];
            }
        }

        return [
            'filename' => $filename,
            'rows' => $rows,
        ];
    }

    /**
     * Export global statistics as Excel (using CSV with Excel-compatible format).
     */
    public function exportGlobalExcel(Tenant $tenant, string $period = '30d'): array
    {
        // For now, we use CSV format which Excel can open
        // A proper Excel implementation would require phpspreadsheet
        $data = $this->exportGlobalCsv($tenant, $period);
        $data['filename'] = str_replace('.csv', '.xlsx.csv', $data['filename']);

        return $data;
    }

    /**
     * Generate PDF report HTML.
     */
    public function generatePdfHtml(Tenant $tenant, string $period = '30d'): string
    {
        $overview = $this->statisticsService->getOverview($tenant, $period);
        $topCampaigns = $this->statisticsService->getTopCampaigns($tenant);
        $devices = $this->statisticsService->getDeviceBreakdown($tenant, $period);

        return view('reports.statistics-pdf', [
            'tenant' => $tenant,
            'period' => $period,
            'periodLabel' => $this->getPeriodLabel($period),
            'overview' => $overview,
            'topCampaigns' => $topCampaigns,
            'devices' => $devices,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->render();
    }

    /**
     * Generate campaign PDF report HTML.
     */
    public function generateCampaignPdfHtml(Campaign $campaign): string
    {
        $stats = $this->statisticsService->getCampaignStats($campaign);

        return view('reports.campaign-pdf', [
            'campaign' => $campaign,
            'stats' => $stats['stats'],
            'links' => $stats['links'],
            'devices' => $stats['devices'],
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->render();
    }

    /**
     * Get period label.
     */
    protected function getPeriodLabel(string $period): string
    {
        return match($period) {
            '7d' => '7 derniers jours',
            '30d' => '30 derniers jours',
            '90d' => '90 derniers jours',
            '12m' => '12 derniers mois',
            default => $period,
        };
    }

    /**
     * Stream CSV response.
     */
    public function streamCsv(array $rows): \Closure
    {
        return function () use ($rows) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };
    }
}
