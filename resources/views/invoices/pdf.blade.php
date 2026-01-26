<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 14px;
            color: #6b7280;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Addresses */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .address-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .address-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .address-content {
            color: #374151;
        }

        .address-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        /* Dates */
        .dates {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            padding: 15px 20px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .date-item {
            display: table-cell;
            width: 33.33%;
        }

        .date-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .date-value {
            font-weight: 600;
            color: #1f2937;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background: #f3f4f6;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table td:nth-child(2),
        .items-table td:nth-child(3) {
            text-align: center;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        .item-description {
            font-weight: 500;
            color: #1f2937;
        }

        /* Totals */
        .totals {
            float: right;
            width: 280px;
        }

        .totals-table {
            width: 100%;
        }

        .totals-table td {
            padding: 8px 0;
        }

        .totals-table td:last-child {
            text-align: right;
        }

        .totals-subtotal {
            color: #6b7280;
        }

        .totals-discount {
            color: #059669;
        }

        .totals-total {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            border-top: 2px solid #e5e7eb;
            padding-top: 12px !important;
        }

        .totals-due {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
        }

        .totals-paid {
            color: #059669;
        }

        /* Footer */
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .notes {
            margin-bottom: 20px;
        }

        .notes-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .notes-content {
            color: #6b7280;
            font-style: italic;
        }

        .company-info {
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }

        .payment-info {
            background: #eff6ff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .payment-info-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .payment-info-content {
            color: #3b82f6;
            font-size: 11px;
        }

        /* Clear floats */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">SliMail</div>
            <div class="invoice-title">
                <h1>FACTURE</h1>
                <div class="invoice-number">{{ $invoice->number }}</div>
                @php
                    $statusClass = match($invoice->status) {
                        'paid' => 'status-paid',
                        'pending' => 'status-pending',
                        'overdue' => 'status-overdue',
                        default => 'status-pending',
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">{{ $invoice->getStatusLabel() }}</span>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-block">
                <div class="address-label">De</div>
                <div class="address-content">
                    <div class="address-name">SLIMAT SARL</div>
                    <div>Abidjan, Côte d'Ivoire</div>
                    <div>contact@slimail.com</div>
                    <div>+225 00 00 00 00 00</div>
                </div>
            </div>
            <div class="address-block">
                <div class="address-label">Facturé à</div>
                <div class="address-content">
                    <div class="address-name">{{ $invoice->billing_name ?? $invoice->tenant->name }}</div>
                    @if($invoice->billing_address)
                        <div>{{ $invoice->billing_address }}</div>
                    @endif
                    @if($invoice->billing_city || $invoice->billing_country)
                        <div>{{ collect([$invoice->billing_city, $invoice->billing_country])->filter()->join(', ') }}</div>
                    @endif
                    @if($invoice->billing_email)
                        <div>{{ $invoice->billing_email }}</div>
                    @endif
                    @if($invoice->billing_phone)
                        <div>{{ $invoice->billing_phone }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="dates">
            <div class="date-item">
                <div class="date-label">Date d'émission</div>
                <div class="date-value">{{ $invoice->issue_date->format('d/m/Y') }}</div>
            </div>
            <div class="date-item">
                <div class="date-label">Date d'échéance</div>
                <div class="date-value">{{ $invoice->due_date->format('d/m/Y') }}</div>
            </div>
            @if($invoice->paid_at)
            <div class="date-item">
                <div class="date-label">Date de paiement</div>
                <div class="date-value" style="color: #059669;">{{ $invoice->paid_at->format('d/m/Y') }}</div>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->line_items ?? [] as $item)
                <tr>
                    <td class="item-description">{{ $item['description'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['unit_price'], 0, ',', ' ') }} {{ $invoice->currency }}</td>
                    <td>{{ number_format($item['total'], 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table class="totals-table">
                <tr class="totals-subtotal">
                    <td>Sous-total</td>
                    <td>{{ number_format($invoice->subtotal, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr class="totals-discount">
                    <td>Remise</td>
                    <td>-{{ number_format($invoice->discount_amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
                @if($invoice->tax_amount > 0)
                <tr>
                    <td>TVA ({{ $invoice->tax_rate }}%)</td>
                    <td>{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
                <tr class="totals-total">
                    <td>Total</td>
                    <td>{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @if($invoice->amount_paid > 0)
                <tr class="totals-paid">
                    <td>Payé</td>
                    <td>{{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
                @if($invoice->amount_due > 0)
                <tr class="totals-due">
                    <td>Reste à payer</td>
                    <td>{{ number_format($invoice->amount_due, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="clearfix"></div>

        <!-- Footer -->
        <div class="footer">
            @if($invoice->status !== 'paid')
            <div class="payment-info">
                <div class="payment-info-title">Informations de paiement</div>
                <div class="payment-info-content">
                    Paiement accepté via Mobile Money (Orange Money, Moov Money, MTN Money, Wave) ou carte bancaire.<br>
                    Connectez-vous à votre espace client sur slimail.com pour effectuer le paiement en ligne.
                </div>
            </div>
            @endif

            @if($invoice->notes)
            <div class="notes">
                <div class="notes-label">Notes</div>
                <div class="notes-content">{{ $invoice->notes }}</div>
            </div>
            @endif

            <div class="company-info">
                <p>SLIMAT SARL - Abidjan, Côte d'Ivoire</p>
                <p>RCCM: CI-ABJ-XXXX-X-XXXXX | contact@slimail.com | www.slimail.com</p>
            </div>
        </div>
    </div>
</body>
</html>
