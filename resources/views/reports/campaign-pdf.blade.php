<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Campagne - {{ $campaign->name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #1f2937;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            margin-bottom: 32px;
            border-bottom: 2px solid #dc2626;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #dc2626;
            letter-spacing: -0.5px;
        }

        .logo-subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        .report-meta {
            text-align: right;
        }

        .report-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .report-date {
            font-size: 11px;
            color: #6b7280;
        }

        /* Campaign Info Box */
        .campaign-info {
            background: linear-gradient(135deg, #f9fafb 0%, #fff 100%);
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .campaign-name {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge.sent {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.draft {
            background: #f3f4f6;
            color: #374151;
        }

        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .campaign-subject {
            color: #6b7280;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .campaign-date {
            font-size: 11px;
            color: #9ca3af;
        }

        /* Section Headers */
        h2 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin: 32px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        h2::before {
            content: '';
            width: 4px;
            height: 20px;
            background: #dc2626;
            border-radius: 2px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 20px 0;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.blue::before { background: #3b82f6; }
        .stat-card.emerald::before { background: #10b981; }
        .stat-card.violet::before { background: #8b5cf6; }
        .stat-card.amber::before { background: #f59e0b; }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .stat-rate {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            border-radius: 20px;
        }

        /* Funnel */
        .funnel {
            margin: 24px 0;
        }

        .funnel-step {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .funnel-step:last-child {
            border-bottom: none;
        }

        .funnel-rank {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .funnel-rank.blue { background: #3b82f6; }
        .funnel-rank.emerald { background: #10b981; }
        .funnel-rank.violet { background: #8b5cf6; }
        .funnel-rank.amber { background: #f59e0b; }

        .funnel-info {
            flex: 1;
        }

        .funnel-label {
            font-weight: 600;
            color: #374151;
            font-size: 13px;
        }

        .funnel-bar-container {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 6px;
            overflow: hidden;
        }

        .funnel-bar {
            height: 100%;
            border-radius: 4px;
        }

        .funnel-bar.blue { background: #3b82f6; }
        .funnel-bar.emerald { background: #10b981; }
        .funnel-bar.violet { background: #8b5cf6; }
        .funnel-bar.amber { background: #f59e0b; }

        .funnel-values {
            text-align: right;
            min-width: 120px;
        }

        .funnel-count {
            font-weight: 700;
            color: #111827;
            font-size: 16px;
        }

        .funnel-percent {
            font-size: 11px;
            color: #6b7280;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 16px 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Link URL */
        .link-url {
            max-width: 350px;
            word-break: break-all;
            font-size: 11px;
            color: #3b82f6;
        }

        .link-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
        }

        /* Two Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin: 20px 0;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
        }

        .card-title {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title::before {
            content: '';
            width: 3px;
            height: 14px;
            background: #dc2626;
            border-radius: 2px;
        }

        /* Device Bars */
        .device-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .device-item:last-child {
            border-bottom: none;
        }

        .device-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .device-dot.desktop { background: #3b82f6; }
        .device-dot.mobile { background: #8b5cf6; }
        .device-dot.tablet { background: #f59e0b; }

        .device-name {
            flex: 1;
            font-size: 12px;
            color: #374151;
        }

        .device-percent {
            font-weight: 700;
            color: #111827;
            font-size: 13px;
        }

        /* Additional Stats */
        .additional-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .additional-stat:last-child {
            border-bottom: none;
        }

        .additional-stat-label {
            color: #6b7280;
            font-size: 12px;
        }

        .additional-stat-value {
            font-weight: 700;
            color: #111827;
            font-size: 14px;
        }

        .additional-stat-value.danger {
            color: #dc2626;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .footer-text {
            font-size: 10px;
            color: #9ca3af;
        }

        .footer-logo {
            font-weight: 700;
            color: #dc2626;
        }

        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                padding: 20px;
            }

            .stats-grid {
                break-inside: avoid;
            }

            table {
                break-inside: avoid;
            }

            h2 {
                break-after: avoid;
            }
        }

        @page {
            margin: 1cm;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <div class="logo">SliMail</div>
                <div class="logo-subtitle">Plateforme d'emailing professionnelle</div>
            </div>
            <div class="report-meta">
                <div class="report-title">Rapport de campagne</div>
                <div class="report-date">{{ $generatedAt }}</div>
            </div>
        </div>

        <!-- Campaign Info -->
        <div class="campaign-info">
            <div class="campaign-name">
                {{ $campaign->name }}
                <span class="status-badge {{ $campaign->status === 'sent' ? 'sent' : 'draft' }}">
                    <span class="dot"></span>
                    {{ $campaign->status === 'sent' ? 'Envoyée' : ucfirst($campaign->status) }}
                </span>
            </div>
            <div class="campaign-subject">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Objet : {{ $campaign->subject }}
            </div>
            @if($campaign->sent_at)
            <div class="campaign-date">Envoyée le {{ $campaign->sent_at->format('d/m/Y H:i') }}</div>
            @endif
        </div>

        <!-- Main Stats -->
        <h2>Performance de la campagne</h2>
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value">{{ number_format($stats['total_recipients']) }}</div>
                <div class="stat-label">Destinataires</div>
            </div>
            <div class="stat-card emerald">
                <div class="stat-value">{{ number_format($stats['delivered']) }}</div>
                <div class="stat-label">Délivrés</div>
                <div class="stat-rate">{{ number_format($stats['delivery_rate'], 1) }}%</div>
            </div>
            <div class="stat-card violet">
                <div class="stat-value">{{ number_format($stats['opened']) }}</div>
                <div class="stat-label">Ouverts</div>
                <div class="stat-rate">{{ number_format($stats['open_rate'], 1) }}%</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-value">{{ number_format($stats['clicked']) }}</div>
                <div class="stat-label">Cliqués</div>
                <div class="stat-rate">{{ number_format($stats['click_rate'], 1) }}%</div>
            </div>
        </div>

        <!-- Funnel -->
        <h2>Entonnoir de conversion</h2>
        <div class="funnel">
            @php
                $sent = $stats['sent'];
                $funnelSteps = [
                    ['label' => 'Envoyés', 'value' => $stats['sent'], 'percent' => 100, 'color' => 'blue'],
                    ['label' => 'Délivrés', 'value' => $stats['delivered'], 'percent' => $sent > 0 ? ($stats['delivered'] / $sent) * 100 : 0, 'color' => 'emerald'],
                    ['label' => 'Ouverts', 'value' => $stats['opened'], 'percent' => $sent > 0 ? ($stats['opened'] / $sent) * 100 : 0, 'color' => 'violet'],
                    ['label' => 'Cliqués', 'value' => $stats['clicked'], 'percent' => $sent > 0 ? ($stats['clicked'] / $sent) * 100 : 0, 'color' => 'amber'],
                ];
            @endphp

            @foreach($funnelSteps as $index => $step)
            <div class="funnel-step">
                <div class="funnel-rank {{ $step['color'] }}">{{ $index + 1 }}</div>
                <div class="funnel-info">
                    <div class="funnel-label">{{ $step['label'] }}</div>
                    <div class="funnel-bar-container">
                        <div class="funnel-bar {{ $step['color'] }}" style="width: {{ $step['percent'] }}%"></div>
                    </div>
                </div>
                <div class="funnel-values">
                    <div class="funnel-count">{{ number_format($step['value']) }}</div>
                    <div class="funnel-percent">{{ number_format($step['percent'], 1) }}%</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="two-col">
            <!-- Additional Stats -->
            <div class="card">
                <div class="card-title">Autres métriques</div>
                <div class="additional-stat">
                    <span class="additional-stat-label">Rebonds</span>
                    <span class="additional-stat-value danger">{{ number_format($stats['bounced']) }} ({{ number_format($stats['bounce_rate'], 1) }}%)</span>
                </div>
                <div class="additional-stat">
                    <span class="additional-stat-label">Plaintes spam</span>
                    <span class="additional-stat-value">{{ number_format($stats['complained']) }}</span>
                </div>
                <div class="additional-stat">
                    <span class="additional-stat-label">Désabonnements</span>
                    <span class="additional-stat-value">{{ number_format($stats['unsubscribed']) }}</span>
                </div>
            </div>

            <!-- Device Breakdown -->
            @if($devices)
            <div class="card">
                <div class="card-title">Appareils</div>
                <div class="device-item">
                    <div class="device-dot desktop"></div>
                    <span class="device-name">Desktop</span>
                    <span class="device-percent">{{ $devices['desktop'] ?? 0 }}%</span>
                </div>
                <div class="device-item">
                    <div class="device-dot mobile"></div>
                    <span class="device-name">Mobile</span>
                    <span class="device-percent">{{ $devices['mobile'] ?? 0 }}%</span>
                </div>
                <div class="device-item">
                    <div class="device-dot tablet"></div>
                    <span class="device-name">Tablette</span>
                    <span class="device-percent">{{ $devices['tablet'] ?? 0 }}%</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Link Clicks -->
        @if(count($links) > 0)
        <h2>Liens cliqués</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>URL</th>
                    <th class="text-center">Clics totaux</th>
                    <th class="text-center">Clics uniques</th>
                </tr>
            </thead>
            <tbody>
                @foreach($links as $index => $link)
                <tr>
                    <td><span class="link-rank">{{ $index + 1 }}</span></td>
                    <td><span class="link-url">{{ $link['url'] }}</span></td>
                    <td class="text-center"><strong>{{ number_format($link['clicks']) }}</strong></td>
                    <td class="text-center"><strong>{{ number_format($link['unique_clicks']) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Rapport généré automatiquement par <span class="footer-logo">SliMail</span> le {{ $generatedAt }}
            </p>
        </div>
    </div>
</body>
</html>
