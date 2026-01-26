@extends('emails.layout')

@section('title', 'Rappel de paiement')

@section('content')
    <h2>Rappel : Facture en attente de paiement</h2>

    <p>Bonjour {{ $tenant->name }},</p>

    @if($invoice->isOverdue())
        <div class="info-box info-box-error">
            <p><strong>Attention :</strong> Votre facture <strong>{{ $invoice->number }}</strong> est en retard de paiement depuis le {{ $invoice->due_date->format('d/m/Y') }}.</p>
        </div>
    @else
        <p>Nous vous rappelons que votre facture <strong>{{ $invoice->number }}</strong> arrive à échéance le <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>.</p>
    @endif

    <div class="info-box">
        <table class="details" style="margin: 0;">
            <tr>
                <td>Numéro de facture</td>
                <td><strong>{{ $invoice->number }}</strong></td>
            </tr>
            <tr>
                <td>Date d'échéance</td>
                <td class="{{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                    {{ $invoice->due_date->format('d/m/Y') }}
                    @if($invoice->isOverdue())
                        (en retard)
                    @endif
                </td>
            </tr>
            <tr>
                <td>Montant à payer</td>
                <td><span class="highlight">{{ $invoice->getFormattedBalanceDue() }}</span></td>
            </tr>
        </table>
    </div>

    <div style="text-align: center;">
        <a href="{{ route('billing.invoices.pay', $invoice) }}" class="button">
            Payer maintenant
        </a>
    </div>

    <div class="divider"></div>

    <p><strong>Modes de paiement acceptés :</strong></p>
    <ul>
        <li>Mobile Money (Orange Money, Moov Money, MTN Money, Wave)</li>
        <li>Carte bancaire (Visa, Mastercard)</li>
    </ul>

    @if($invoice->isOverdue())
        <div class="info-box info-box-warning">
            <p><strong>Important :</strong> Un retard prolongé de paiement peut entraîner la suspension de votre compte. Veuillez régulariser votre situation dans les plus brefs délais.</p>
        </div>
    @endif

    <p class="text-muted">
        Si vous avez déjà effectué ce paiement, veuillez ignorer cet email. Le délai de traitement peut prendre jusqu'à 24 heures.
    </p>
@endsection
