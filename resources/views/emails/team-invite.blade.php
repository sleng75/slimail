@extends('emails.layout')

@section('title', 'Invitation à rejoindre l\'équipe')

@section('content')
    <h2>Vous avez été invité !</h2>

    <p>Bonjour,</p>

    <p><strong>{{ $inviter->name }}</strong> vous a invité à rejoindre l'équipe <strong>{{ $tenant->name }}</strong> sur SliMail.</p>

    <div class="info-box">
        <table class="details" style="margin: 0;">
            <tr>
                <td>Organisation</td>
                <td><strong>{{ $tenant->name }}</strong></td>
            </tr>
            <tr>
                <td>Invité par</td>
                <td>{{ $inviter->name }} ({{ $inviter->email }})</td>
            </tr>
            <tr>
                <td>Votre rôle</td>
                <td><span class="highlight">{{ ucfirst($role) }}</span></td>
            </tr>
        </table>
    </div>

    <p>En acceptant cette invitation, vous pourrez :</p>

    <ul>
        @switch($role)
            @case('admin')
                <li>Gérer les contacts et les listes</li>
                <li>Créer et envoyer des campagnes</li>
                <li>Gérer les templates et automatisations</li>
                <li>Voir les statistiques complètes</li>
                <li>Gérer les utilisateurs de l'équipe</li>
                @break
            @case('editor')
                <li>Gérer les contacts et les listes</li>
                <li>Créer et envoyer des campagnes</li>
                <li>Gérer les templates</li>
                <li>Voir les statistiques</li>
                @break
            @case('analyst')
                <li>Consulter les contacts (lecture seule)</li>
                <li>Voir les campagnes et leurs performances</li>
                <li>Accéder aux statistiques complètes</li>
                @break
            @case('developer')
                <li>Gérer les clés API</li>
                <li>Accéder à la documentation API</li>
                <li>Voir les logs d'intégration</li>
                @break
            @default
                <li>Accéder au tableau de bord</li>
                <li>Utiliser les fonctionnalités selon vos permissions</li>
        @endswitch
    </ul>

    <div style="text-align: center;">
        <a href="{{ $invitationUrl }}" class="button">
            Accepter l'invitation
        </a>
    </div>

    <p class="text-muted" style="margin-top: 30px;">
        Cette invitation expirera dans 7 jours. Si vous ne souhaitez pas rejoindre cette équipe, vous pouvez simplement ignorer cet email.
    </p>

    <div class="divider"></div>

    <p class="text-muted" style="font-size: 12px;">
        Si vous n'avez pas demandé cette invitation ou si vous pensez l'avoir reçue par erreur, veuillez ignorer cet email ou nous contacter à <a href="mailto:support@slimail.com">support@slimail.com</a>.
    </p>
@endsection
