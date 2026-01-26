@extends('emails.layout')

@section('title', 'Votre abonnement expire bientôt')

@section('content')
    <h2>Votre abonnement expire bientôt</h2>

    <p>Bonjour {{ $tenant->name }},</p>

    <p>Nous vous informons que votre abonnement SliMail expire dans <strong>{{ $daysRemaining }} jours</strong>, le <strong>{{ $subscription->ends_at->format('d/m/Y') }}</strong>.</p>

    <div class="info-box">
        <table class="details" style="margin: 0;">
            <tr>
                <td>Forfait actuel</td>
                <td><strong>{{ $subscription->plan->name }}</strong></td>
            </tr>
            <tr>
                <td>Date d'expiration</td>
                <td class="text-warning">{{ $subscription->ends_at->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Cycle de facturation</td>
                <td>{{ $subscription->billing_cycle === 'yearly' ? 'Annuel' : 'Mensuel' }}</td>
            </tr>
        </table>
    </div>

    <p>Pour continuer à bénéficier de toutes les fonctionnalités de SliMail, pensez à renouveler votre abonnement avant l'expiration.</p>

    <div style="text-align: center;">
        <a href="{{ route('billing.plans') }}" class="button">
            Renouveler mon abonnement
        </a>
    </div>

    <div class="divider"></div>

    <h3 style="color: #374151;">Ce que vous risquez de perdre</h3>

    <ul>
        <li>L'envoi de campagnes email</li>
        <li>L'accès aux statistiques détaillées</li>
        <li>Les automatisations actives</li>
        <li>L'accès à l'API transactionnelle</li>
    </ul>

    <div class="info-box info-box-warning">
        <p><strong>Note :</strong> Vos contacts et données seront conservés pendant 30 jours après l'expiration. Passé ce délai, elles pourront être supprimées.</p>
    </div>

    <p class="text-muted">
        Des questions ? Contactez notre support à <a href="mailto:support@slimail.com">support@slimail.com</a>
    </p>
@endsection
