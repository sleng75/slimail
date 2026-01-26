<?php

return [
    // Event templates
    [
        'name' => 'Invitation Événement',
        'description' => 'Invitation élégante pour événement ou webinaire',
        'category' => 'event',
        'default_subject' => '🎉 Vous êtes invité(e) : {{event_name}}',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #faf5ff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Hero Image -->
                    <tr>
                        <td>
                            <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=280&fit=crop" alt="Event" style="width: 100%; height: 240px; object-fit: cover; border-radius: 20px 20px 0 0;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 48px 40px; text-align: center;">
                            <span style="display: inline-block; padding: 8px 16px; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); color: #ffffff; font-size: 12px; font-weight: 600; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 24px;">Invitation exclusive</span>
                            <h1 style="margin: 0 0 24px; font-size: 32px; font-weight: 700; color: #0f172a; line-height: 1.2;">Nom de l'événement</h1>
                            <!-- Event Details -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 32px; background-color: #f8fafc; border-radius: 16px;">
                                <tr>
                                    <td width="33%" style="padding: 24px 16px; text-align: center; border-right: 1px solid #e2e8f0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #64748b; text-transform: uppercase;">Date</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">15 Mars 2026</p>
                                    </td>
                                    <td width="33%" style="padding: 24px 16px; text-align: center; border-right: 1px solid #e2e8f0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #64748b; text-transform: uppercase;">Heure</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">14:00 - 18:00</p>
                                    </td>
                                    <td width="33%" style="padding: 24px 16px; text-align: center;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #64748b; text-transform: uppercase;">Format</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">En ligne</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #64748b;">
                                Rejoignez-nous pour cet événement exceptionnel. Places limitées, inscrivez-vous dès maintenant !
                            </p>
                            <a href="#" style="display: inline-block; padding: 18px 48px; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 24px rgba(168, 85, 247, 0.35);">
                                S'inscrire maintenant
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e1b4b; border-radius: 0 0 20px 20px; padding: 32px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #a5b4fc;">
                                © {{current_year}} · <a href="{{unsubscribe_url}}" style="color: #a5b4fc;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML,
    ],
    // Abandoned Cart
    [
        'name' => 'Panier Abandonné',
        'description' => 'Rappel de panier avec incitation à finaliser',
        'category' => 'abandoned_cart',
        'default_subject' => '🛒 Votre panier vous attend !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #fff7ed; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 48px 40px; text-align: center;">
                            <span style="font-size: 56px;">🛒</span>
                            <h1 style="margin: 24px 0 8px; font-size: 28px; font-weight: 700; color: #0f172a;">Vous avez oublié quelque chose...</h1>
                            <p style="margin: 0; font-size: 16px; color: #64748b;">Votre panier vous attend toujours, {{contact.first_name}}</p>
                        </td>
                    </tr>
                    <!-- Product -->
                    <tr>
                        <td style="padding: 0 40px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border-radius: 16px; overflow: hidden;">
                                <tr>
                                    <td width="120" style="padding: 24px;">
                                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100&h=100&fit=crop" alt="Produit" style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover;">
                                    </td>
                                    <td style="padding: 24px 24px 24px 0;">
                                        <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 600; color: #0f172a;">Nom du Produit</h3>
                                        <p style="margin: 0 0 12px; font-size: 14px; color: #64748b;">Quantité: 1</p>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #ea580c;">79,00 €</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Promo -->
                    <tr>
                        <td style="padding: 0 40px 32px;">
                            <div style="background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); border-radius: 16px; padding: 24px; text-align: center;">
                                <p style="margin: 0 0 8px; font-size: 14px; color: #9a3412; font-weight: 600;">Offre spéciale pour vous !</p>
                                <p style="margin: 0; font-size: 24px; font-weight: 700; color: #7c2d12;">-15% avec le code <span style="background-color: #ffffff; padding: 4px 12px; border-radius: 6px;">REVIENS15</span></p>
                            </div>
                        </td>
                    </tr>
                    <!-- CTA -->
                    <tr>
                        <td style="padding: 0 40px 48px; text-align: center;">
                            <a href="#" style="display: inline-block; padding: 18px 48px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff; text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 24px rgba(249, 115, 22, 0.35);">
                                Finaliser ma commande
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                <a href="{{unsubscribe_url}}" style="color: #94a3b8;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML,
    ],
    // Survey
    [
        'name' => 'Enquête de Satisfaction',
        'description' => 'Demande de feedback avec notation rapide',
        'category' => 'survey',
        'default_subject' => '⭐ Votre avis compte pour nous !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #ecfdf5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 48px 40px; text-align: center;">
                            <span style="font-size: 56px;">💬</span>
                            <h1 style="margin: 24px 0 16px; font-size: 28px; font-weight: 700; color: #0f172a;">Comment s'est passée votre expérience ?</h1>
                            <p style="margin: 0 0 40px; font-size: 16px; line-height: 1.6; color: #64748b;">
                                Bonjour {{contact.first_name}}, nous aimerions avoir votre retour. Cela ne prend que 30 secondes !
                            </p>
                            <!-- Rating -->
                            <p style="margin: 0 0 16px; font-size: 14px; color: #64748b;">Comment évaluez-vous votre expérience ?</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto 40px;">
                                <tr>
                                    <td style="padding: 0 8px;"><a href="#" style="display: inline-block; width: 56px; height: 56px; background-color: #fef2f2; border-radius: 12px; text-decoration: none; line-height: 56px; font-size: 28px;">😞</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="display: inline-block; width: 56px; height: 56px; background-color: #fef9c3; border-radius: 12px; text-decoration: none; line-height: 56px; font-size: 28px;">😐</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="display: inline-block; width: 56px; height: 56px; background-color: #dcfce7; border-radius: 12px; text-decoration: none; line-height: 56px; font-size: 28px;">🙂</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="display: inline-block; width: 56px; height: 56px; background-color: #d1fae5; border-radius: 12px; text-decoration: none; line-height: 56px; font-size: 28px;">😊</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="display: inline-block; width: 56px; height: 56px; background-color: #a7f3d0; border-radius: 12px; text-decoration: none; line-height: 56px; font-size: 28px;">🤩</a></td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 24px; font-size: 14px; color: #94a3b8;">Ou répondez à notre enquête complète :</p>
                            <a href="#" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                Répondre à l'enquête
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                <a href="{{unsubscribe_url}}" style="color: #94a3b8;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML,
    ],
    // Notification
    [
        'name' => 'Notification Importante',
        'description' => 'Notification avec style alerte',
        'category' => 'notification',
        'default_subject' => '🔔 Notification importante',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; border-left: 6px solid #6366f1; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 40px;">
                            <div style="display: flex; align-items: center; margin-bottom: 24px;">
                                <div style="width: 48px; height: 48px; background-color: #eef2ff; border-radius: 12px; line-height: 48px; text-align: center; margin-right: 16px;">
                                    <span style="font-size: 24px;">🔔</span>
                                </div>
                                <div>
                                    <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">Nouvelle notification</h2>
                                </div>
                            </div>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.7; color: #475569;">
                                Bonjour {{contact.first_name}}, vous avez une nouvelle notification importante qui nécessite votre attention.
                            </p>
                            <a href="#" style="display: inline-block; padding: 14px 28px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 15px;">
                                Voir les détails →
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin: 24px 0 0; font-size: 12px; color: #94a3b8; text-align: center;">
                    <a href="{{unsubscribe_url}}" style="color: #94a3b8;">Gérer les notifications</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML,
    ],
    // Reactivation
    [
        'name' => 'Réactivation Client',
        'description' => 'Email pour clients inactifs',
        'category' => 'notification',
        'default_subject' => '😢 Vous nous manquez, {{contact.first_name}} !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #fef2f2; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 56px 40px; text-align: center;">
                            <span style="font-size: 64px;">💔</span>
                            <h1 style="margin: 24px 0 16px; font-size: 28px; font-weight: 700; color: #0f172a;">Vous nous manquez !</h1>
                            <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #64748b;">
                                Ça fait un moment qu'on ne vous a pas vu, {{contact.first_name}}. Revenez découvrir ce qui a changé !
                            </p>
                            <div style="background-color: #fef2f2; border: 2px dashed #fca5a5; border-radius: 16px; padding: 24px; margin-bottom: 32px;">
                                <p style="margin: 0 0 8px; font-size: 14px; color: #dc2626; font-weight: 600;">Cadeau de bienvenue</p>
                                <p style="margin: 0; font-size: 36px; font-weight: 800; color: #dc2626;">-20%</p>
                                <p style="margin: 8px 0 0; font-size: 14px; color: #64748b;">sur votre prochaine commande</p>
                            </div>
                            <a href="#" style="display: inline-block; padding: 18px 48px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);">
                                Revenir sur le site
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                <a href="{{unsubscribe_url}}" style="color: #94a3b8;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML,
    ],
];
