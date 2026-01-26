<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Statistiques - {{ $periodLabel }}</title>
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

        .report-period {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background: #fef2f2;
            color: #dc2626;
            font-size: 11px;
            font-weight: 600;
            border-radius: 20px;
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
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #f9fafb 0%, #fff 100%);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-card.primary {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            color: #fff;
        }

        .stat-card.primary .stat-value,
        .stat-card.primary .stat-label {
            color: #fff;
        }

        .stat-card.primary .stat-change {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .stat-change {
            display: inline-block;
            margin-top: 8px;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }

        .stat-change.positive {
            background: #d1fae5;
            color: #065f46;
        }

        .stat-change.negative {
            background: #fee2e2;
            color: #991b1b;
        }

        .stat-change.neutral {
            background: #f3f4f6;
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

        tr:hover td {
            background: #fafafa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Campaign Rank */
        .rank-badge {
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

        /* Rate Badge */
        .rate-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .rate-badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .rate-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .rate-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Device Bars */
        .device-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .device-item:last-child {
            border-bottom: none;
        }

        .device-icon {
            width: 36px;
            height: 36px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .device-info {
            flex: 1;
        }

        .device-name {
            font-weight: 600;
            color: #374151;
            font-size: 12px;
        }

        .device-bar-container {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }

        .device-bar {
            height: 100%;
            border-radius: 3px;
        }

        .device-bar.desktop { background: #3b82f6; }
        .device-bar.mobile { background: #8b5cf6; }
        .device-bar.tablet { background: #f59e0b; }

        .device-percent {
            font-weight: 700;
            color: #111827;
            font-size: 14px;
            min-width: 50px;
            text-align: right;
        }

        /* Two Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 2fr 1fr;
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
                <div class="report-title">Rapport de statistiques</div>
                <div class="report-date">{{ $generatedAt }}</div>
                <div class="report-period">{{ $periodLabel }}</div>
            </div>
        </div>

        <!-- Tenant Info -->
        @if($tenant)
        <p style="margin-bottom: 24px; color: #6b7280;">
            <strong style="color: #111827;">{{ $tenant->company_name }}</strong>
        </p>
        @endif

        <!-- Main Stats -->
        <h2>Vue d'ensemble</h2>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value">{{ number_format($overview['stats']['sent']) }}</div>
                <div class="stat-label">Emails envoyés</div>
                @if($overview['changes']['sent'] != 0)
                <div class="stat-change {{ $overview['changes']['sent'] > 0 ? 'positive' : 'negative' }}">
                    {{ $overview['changes']['sent'] > 0 ? '+' : '' }}{{ $overview['changes']['sent'] }}%
                </div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($overview['stats']['delivery_rate'], 1) }}%</div>
                <div class="stat-label">Délivrabilité</div>
                @if($overview['changes']['delivery_rate'] != 0)
                <div class="stat-change {{ $overview['changes']['delivery_rate'] > 0 ? 'positive' : 'negative' }}">
                    {{ $overview['changes']['delivery_rate'] > 0 ? '+' : '' }}{{ $overview['changes']['delivery_rate'] }} pts
                </div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($overview['stats']['open_rate'], 1) }}%</div>
                <div class="stat-label">Taux d'ouverture</div>
                @if($overview['changes']['open_rate'] != 0)
                <div class="stat-change {{ $overview['changes']['open_rate'] > 0 ? 'positive' : 'negative' }}">
                    {{ $overview['changes']['open_rate'] > 0 ? '+' : '' }}{{ $overview['changes']['open_rate'] }} pts
                </div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($overview['stats']['click_rate'], 1) }}%</div>
                <div class="stat-label">Taux de clic</div>
                @if($overview['changes']['click_rate'] != 0)
                <div class="stat-change {{ $overview['changes']['click_rate'] > 0 ? 'positive' : 'negative' }}">
                    {{ $overview['changes']['click_rate'] > 0 ? '+' : '' }}{{ $overview['changes']['click_rate'] }} pts
                </div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($overview['stats']['bounce_rate'], 1) }}%</div>
                <div class="stat-label">Taux de rebond</div>
                @if($overview['changes']['bounce_rate'] != 0)
                <div class="stat-change {{ $overview['changes']['bounce_rate'] < 0 ? 'positive' : 'negative' }}">
                    {{ $overview['changes']['bounce_rate'] > 0 ? '+' : '' }}{{ $overview['changes']['bounce_rate'] }} pts
                </div>
                @endif
            </div>
        </div>

        <!-- Detailed Stats Table -->
        <h2>Statistiques détaillées</h2>
        <table>
            <thead>
                <tr>
                    <th>Métrique</th>
                    <th class="text-right">Valeur</th>
                    <th class="text-right">Variation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Emails envoyés</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['sent']) }}</td>
                    <td class="text-right">
                        <span class="rate-badge {{ $overview['changes']['sent'] >= 0 ? 'success' : 'danger' }}">
                            {{ $overview['changes']['sent'] > 0 ? '+' : '' }}{{ $overview['changes']['sent'] }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Emails délivrés</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['delivered']) }}</td>
                    <td class="text-right">
                        <span class="rate-badge {{ $overview['changes']['delivered'] >= 0 ? 'success' : 'danger' }}">
                            {{ $overview['changes']['delivered'] > 0 ? '+' : '' }}{{ $overview['changes']['delivered'] }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Emails ouverts</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['opened']) }}</td>
                    <td class="text-right">
                        <span class="rate-badge {{ $overview['changes']['opened'] >= 0 ? 'success' : 'danger' }}">
                            {{ $overview['changes']['opened'] > 0 ? '+' : '' }}{{ $overview['changes']['opened'] }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Clics</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['clicked']) }}</td>
                    <td class="text-right">
                        <span class="rate-badge {{ $overview['changes']['clicked'] >= 0 ? 'success' : 'danger' }}">
                            {{ $overview['changes']['clicked'] > 0 ? '+' : '' }}{{ $overview['changes']['clicked'] }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Rebonds</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['bounced']) }}</td>
                    <td class="text-right">
                        <span class="rate-badge {{ $overview['changes']['bounced'] <= 0 ? 'success' : 'danger' }}">
                            {{ $overview['changes']['bounced'] > 0 ? '+' : '' }}{{ $overview['changes']['bounced'] }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Plaintes spam</strong></td>
                    <td class="text-right">{{ number_format($overview['stats']['complained']) }}</td>
                    <td class="text-right">-</td>
                </tr>
            </tbody>
        </table>

        <div class="two-col">
            <!-- Top Campaigns -->
            <div class="card">
                <div class="card-title">Meilleures campagnes</div>
                @if(count($topCampaigns) > 0)
                <table style="margin: 0; border: none;">
                    <thead>
                        <tr>
                            <th style="border-radius: 8px 0 0 0;">#</th>
                            <th>Campagne</th>
                            <th class="text-center">Envois</th>
                            <th class="text-center">Ouverture</th>
                            <th class="text-center" style="border-radius: 0 8px 0 0;">Clic</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCampaigns as $index => $campaign)
                        <tr>
                            <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                            <td><strong>{{ Str::limit($campaign['name'], 30) }}</strong></td>
                            <td class="text-center">{{ number_format($campaign['sent_count']) }}</td>
                            <td class="text-center">
                                <span class="rate-badge success">{{ number_format($campaign['open_rate'], 1) }}%</span>
                            </td>
                            <td class="text-center">
                                <span class="rate-badge warning">{{ number_format($campaign['click_rate'], 1) }}%</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p style="color: #6b7280; text-align: center; padding: 20px;">Aucune campagne envoyée</p>
                @endif
            </div>

            <!-- Device Breakdown -->
            <div class="card">
                <div class="card-title">Répartition par appareil</div>
                <div class="device-item">
                    <div class="device-icon">
                        <svg width="18" height="18" fill="none" stroke="#3b82f6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="device-info">
                        <div class="device-name">Desktop</div>
                        <div class="device-bar-container">
                            <div class="device-bar desktop" style="width: {{ $devices['desktop']['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div class="device-percent">{{ $devices['desktop']['percentage'] ?? 0 }}%</div>
                </div>
                <div class="device-item">
                    <div class="device-icon">
                        <svg width="18" height="18" fill="none" stroke="#8b5cf6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="device-info">
                        <div class="device-name">Mobile</div>
                        <div class="device-bar-container">
                            <div class="device-bar mobile" style="width: {{ $devices['mobile']['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div class="device-percent">{{ $devices['mobile']['percentage'] ?? 0 }}%</div>
                </div>
                <div class="device-item">
                    <div class="device-icon">
                        <svg width="18" height="18" fill="none" stroke="#f59e0b" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="device-info">
                        <div class="device-name">Tablette</div>
                        <div class="device-bar-container">
                            <div class="device-bar tablet" style="width: {{ $devices['tablet']['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div class="device-percent">{{ $devices['tablet']['percentage'] ?? 0 }}%</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Rapport généré automatiquement par <span class="footer-logo">SliMail</span> le {{ $generatedAt }}
            </p>
        </div>
    </div>
</body>
</html>
