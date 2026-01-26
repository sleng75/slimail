@extends('emails.layout')

@section('title', 'Nouvelle facture')

@section('content')
    <h2>Nouvelle facture disponible</h2>

    <p>Bonjour {{ $tenant->name }},</p>

    <p>Une nouvelle facture a été générée pour votre compte SliMail.</p>

    <div class="info-box">
        <table class="details" style="margin: 0;">
            <tr>
                <td>Numéro de facture</td>
                <td><strong>{{ $invoice->number }}</strong></td>
            </tr>
            <tr>
                <td>Date d'émission</td>
                <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Date d'échéance</td>
                <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Montant total</td>
                <td><span class="highlight">{{ $invoice->getFormattedTotal() }}</span></td>
            </tr>
        </table>
    </div>

    @if($invoice->line_items)
        <h3 style="color: #374151; margin-bottom: 10px;">Détails</h3>
        <table class="details">
            @foreach($invoice->line_items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td>{{ number_format($item['total'], 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div style="text-align: center;">
        <a href="{{ route('billing.invoices.show', $invoice) }}" class="button">
            Voir la facture
        </a>
    </div>

    @if(!$invoice->isPaid())
        <div class="info-box info-box-warning">
            <p><strong>Rappel :</strong> Cette facture est à régler avant le {{ $invoice->due_date->format('d/m/Y') }}.</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ route('billing.invoices.pay', $invoice) }}" class="button button-secondary">
                Payer maintenant
            </a>
        </div>
    @endif

    <p class="text-muted" style="margin-top: 30px;">
        Si vous avez des questions concernant cette facture, n'hésitez pas à nous contacter.
    </p>
@endsection
