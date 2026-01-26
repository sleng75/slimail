<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SliMail</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f5f5f5;
            padding: 40px 0;
        }
        .main {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #1f2937;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
        }
        .content p {
            color: #4b5563;
            margin-bottom: 16px;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #3B82F6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #2563EB;
        }
        .button-secondary {
            background-color: #6B7280;
        }
        .info-box {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box-success {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
        }
        .info-box-warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .info-box-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .info-box p {
            margin: 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            color: #9ca3af;
            font-size: 13px;
            margin: 5px 0;
        }
        .footer a {
            color: #3B82F6;
            text-decoration: none;
        }
        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 30px 0;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table.details td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.details td:first-child {
            color: #6b7280;
            font-weight: 500;
            width: 40%;
        }
        table.details td:last-child {
            color: #1f2937;
            text-align: right;
        }
        .highlight {
            color: #3B82F6;
            font-weight: 600;
        }
        .text-muted {
            color: #9ca3af;
        }
        .text-success {
            color: #10b981;
        }
        .text-warning {
            color: #f59e0b;
        }
        .text-danger {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header">
                <h1>SliMail</h1>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} SliMail. Tous droits réservés.</p>
                <p>
                    <a href="{{ config('app.url') }}">Accéder à mon compte</a> |
                    <a href="{{ config('app.url') }}/contact">Contact</a>
                </p>
                @hasSection('footer_extra')
                    @yield('footer_extra')
                @endif
            </div>
        </div>
    </div>
</body>
</html>
