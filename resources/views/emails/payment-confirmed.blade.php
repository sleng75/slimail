@extends('emails.layout')

@section('title', 'Paiement confirmé')

@section('content')
    <h2>Paiement reçu avec succès ! ✅</h2>

    <p>Bonjour {{ $tenant->name }},</p>

    <p>Nous avons bien reçu votre paiement. Merci pour votre confiance !</p>

    <div class="info-box info-box-success">
        <table class="details" style="margin: 0;">
            <tr>
                <td>Montant payé</td>
                <td><strong class="text-success">{{ $payment->getFormattedAmount() }}</strong></td>
            </tr>
            <tr>
                <td>Référence</td>
                <td>{{ $payment->transaction_id }}</td>
            </tr>
            <tr>
                <td>Méthode</td>
                <td>{{ $payment->getPaymentMethodLabel() }}</td>
            </tr>
            <tr>
                <td>Date</td>
                <td>{{ $payment->completed_at->format('d/m/Y à H:i') }}</td>
            </tr>
        </table>
    </div>

    @if($invoice)
        <p>Ce paiement concerne la facture <strong>{{ $invoice->number }}</strong>.</p>
    @endif

    @if($subscription)
        <div class="info-box">
            <p><strong>Votre abonnement</strong></p>
            <table class="details" style="margin: 10px 0 0 0;">
                <tr>
                    <td>Forfait</td>
                    <td><strong>{{ $subscription->plan->name }}</strong></td>
                </tr>
                <tr>
                    <td>Statut</td>
                    <td><span class="text-success">Actif</span></td>
                </tr>
                <tr>
                    <td>Prochaine échéance</td>
                    <td>{{ $subscription->ends_at->format('d/m/Y') }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div style="text-align: center;">
        <a href="{{ route('billing.invoices.show', $invoice) }}" class="button">
            Voir ma facture
        </a>
        <br><br>
        <a href="{{ route('billing.invoices.download', $invoice) }}" style="color: #3B82F6; text-decoration: none;">
            📥 Télécharger le PDF
        </a>
    </div>

    <p class="text-muted" style="margin-top: 30px;">
        Une question sur votre paiement ? Contactez-nous à <a href="mailto:billing@slimail.com">billing@slimail.com</a>
    </p>
@endsection
