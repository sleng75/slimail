<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Désabonnement - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg {
            width: 32px;
            height: 32px;
            color: #d97706;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .email {
            font-weight: 600;
            color: #111827;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        button {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-unsubscribe {
            background: #ef4444;
            color: white;
        }
        .btn-unsubscribe:hover {
            background: #dc2626;
        }
        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-cancel:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1>Se désabonner ?</h1>
        <p>
            Vous êtes sur le point de vous désabonner avec l'adresse email
            <span class="email">{{ $email }}</span>.
            Vous ne recevrez plus nos communications marketing.
        </p>
        <form method="POST" action="{{ url('/unsubscribe') }}">
            @csrf
            <input type="hidden" name="c" value="{{ $contactId }}">
            <input type="hidden" name="t" value="{{ $token }}">
            @if($campaignId)
            <input type="hidden" name="campaign" value="{{ $campaignId }}">
            @endif
            <button type="submit" class="btn-unsubscribe">
                Confirmer le désabonnement
            </button>
            <button type="button" class="btn-cancel" onclick="window.close()">
                Annuler
            </button>
        </form>
    </div>
</body>
</html>
