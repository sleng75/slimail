@extends('emails.layout')

@section('title', 'Bienvenue sur SliMail')

@section('content')
    <h2>Bienvenue sur SliMail ! 🎉</h2>

    <p>Bonjour {{ $user->name }},</p>

    <p>Nous sommes ravis de vous accueillir sur <strong>SliMail</strong>, votre nouvelle plateforme d'email marketing.</p>

    <p>Votre compte <strong>{{ $tenant->name }}</strong> a été créé avec succès et est prêt à être utilisé.</p>

    @if($subscription && $subscription->onTrial())
        <div class="info-box info-box-success">
            <p><strong>🎁 Période d'essai activée !</strong></p>
            <p>Vous bénéficiez d'un essai gratuit de {{ $subscription->plan->trial_days }} jours pour découvrir toutes les fonctionnalités du forfait <strong>{{ $subscription->plan->name }}</strong>.</p>
            <p>Votre essai se termine le <strong>{{ $subscription->trial_ends_at->format('d/m/Y') }}</strong>.</p>
        </div>
    @endif

    <div style="text-align: center;">
        <a href="{{ route('dashboard') }}" class="button">
            Accéder à mon tableau de bord
        </a>
    </div>

    <div class="divider"></div>

    <h3 style="color: #374151;">Pour bien commencer</h3>

    <ol style="color: #4b5563; padding-left: 20px;">
        <li style="margin-bottom: 12px;">
            <strong>Importez vos contacts</strong><br>
            <span class="text-muted">Ajoutez vos contacts via CSV ou manuellement</span>
        </li>
        <li style="margin-bottom: 12px;">
            <strong>Créez votre première liste</strong><br>
            <span class="text-muted">Organisez vos contacts par centres d'intérêt</span>
        </li>
        <li style="margin-bottom: 12px;">
            <strong>Choisissez un template</strong><br>
            <span class="text-muted">Utilisez nos modèles professionnels ou créez le vôtre</span>
        </li>
        <li style="margin-bottom: 12px;">
            <strong>Lancez votre première campagne</strong><br>
            <span class="text-muted">Envoyez votre premier email en quelques clics</span>
        </li>
    </ol>

    <div class="info-box">
        <p><strong>💡 Astuce :</strong> Commencez par envoyer un email de test à vous-même pour vérifier que tout fonctionne correctement.</p>
    </div>

    <div class="divider"></div>

    <h3 style="color: #374151;">Besoin d'aide ?</h3>

    <ul style="color: #4b5563;">
        <li><a href="{{ config('app.url') }}/api/docs">Documentation API</a> - Intégrez SliMail à vos applications</li>
        <li><a href="mailto:support@slimail.com">Support</a> - Notre équipe est là pour vous aider</li>
    </ul>

    <p style="margin-top: 30px;">
        Merci de nous faire confiance !<br>
        <strong>L'équipe SliMail</strong>
    </p>
@endsection
