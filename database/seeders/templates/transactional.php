<?php

return [
    [
        'name' => 'Confirmation de Commande',
        'description' => 'Email de confirmation après achat avec détails',
        'category' => 'transactional',
        'default_subject' => '✅ Commande #{{order_id}} confirmée',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Success Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 48px 40px; text-align: center;">
                            <div style="width: 64px; height: 64px; background-color: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 20px; line-height: 64px;">
                                <span style="font-size: 32px;">✓</span>
                            </div>
                            <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #ffffff;">Commande confirmée !</h1>
                            <p style="margin: 0; font-size: 16px; color: rgba(255,255,255,0.9);">Merci pour votre achat, {{contact.first_name}}</p>
                        </td>
                    </tr>
                    <!-- Order Details -->
                    <tr>
                        <td style="padding: 40px;">
                            <div style="background-color: #f8fafc; border-radius: 12px; padding: 24px; margin-bottom: 32px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="50%">
                                            <p style="margin: 0 0 4px; font-size: 12px; color: #64748b; text-transform: uppercase;">N° de commande</p>
                                            <p style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">#{{order_id}}</p>
                                        </td>
                                        <td width="50%" style="text-align: right;">
                                            <p style="margin: 0 0 4px; font-size: 12px; color: #64748b; text-transform: uppercase;">Date</p>
                                            <p style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">{{order_date}}</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- Order Items -->
                            <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #0f172a;">Récapitulatif</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 0; border-bottom: 1px solid #e2e8f0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="60">
                                                    <img src="https://via.placeholder.com/50x50" alt="Produit" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <p style="margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #0f172a;">Nom du produit</p>
                                                    <p style="margin: 0; font-size: 13px; color: #64748b;">Quantité: 1</p>
                                                </td>
                                                <td style="text-align: right;">
                                                    <p style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;">59,00 €</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <!-- Total -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border-radius: 12px; padding: 20px;">
                                <tr>
                                    <td style="padding: 8px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td><p style="margin: 0; font-size: 14px; color: #64748b;">Sous-total</p></td>
                                                <td style="text-align: right;"><p style="margin: 0; font-size: 14px; color: #0f172a;">59,00 €</p></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td><p style="margin: 0; font-size: 14px; color: #64748b;">Livraison</p></td>
                                                <td style="text-align: right;"><p style="margin: 0; font-size: 14px; color: #10b981;">Gratuite</p></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px 8px; border-top: 1px solid #e2e8f0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td><p style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Total</p></td>
                                                <td style="text-align: right;"><p style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">59,00 €</p></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- CTA -->
                    <tr>
                        <td style="padding: 0 40px 40px; text-align: center;">
                            <a href="#" style="display: inline-block; padding: 16px 32px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                Suivre ma commande
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0 0 8px; font-size: 14px; color: #64748b;">
                                Une question ? <a href="#" style="color: #6366f1; text-decoration: none;">Contactez-nous</a>
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">© {{current_year}} Votre Entreprise</p>
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
    [
        'name' => 'Réinitialisation Mot de Passe',
        'description' => 'Email sécurisé pour réinitialisation de mot de passe',
        'category' => 'transactional',
        'default_subject' => '🔐 Réinitialisez votre mot de passe',
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
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 500px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 48px 40px; text-align: center;">
                            <div style="width: 72px; height: 72px; background-color: #fef3c7; border-radius: 50%; margin: 0 auto 24px; line-height: 72px;">
                                <span style="font-size: 36px;">🔐</span>
                            </div>
                            <h1 style="margin: 0 0 16px; font-size: 24px; font-weight: 700; color: #0f172a;">Réinitialisez votre mot de passe</h1>
                            <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.6; color: #64748b;">
                                Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en créer un nouveau.
                            </p>
                            <a href="#" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
                                Réinitialiser mon mot de passe
                            </a>
                            <p style="margin: 24px 0 0; font-size: 13px; color: #94a3b8;">
                                Ce lien expire dans 60 minutes
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;">
                                Si vous n'avez pas fait cette demande, ignorez simplement cet email. Votre mot de passe restera inchangé.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">© {{current_year}} Votre Entreprise</p>
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
    [
        'name' => 'Facture',
        'description' => 'Template de facture professionnel',
        'category' => 'transactional',
        'default_subject' => '📄 Votre facture #{{invoice_number}}',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px; border-bottom: 1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;">FACTURE</h1>
                                        <p style="margin: 8px 0 0; font-size: 14px; color: #64748b;">#{{invoice_number}}</p>
                                    </td>
                                    <td style="text-align: right;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #64748b;">Date d'émission</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{invoice_date}}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Addresses -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="48%" style="vertical-align: top;">
                                        <p style="margin: 0 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase;">Émetteur</p>
                                        <p style="margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #0f172a;">Votre Entreprise</p>
                                        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
                                            123 Rue de l'Exemple<br>
                                            75001 Paris, France
                                        </p>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align: top;">
                                        <p style="margin: 0 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase;">Facturé à</p>
                                        <p style="margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #0f172a;">{{contact.company}}</p>
                                        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
                                            {{contact.first_name}} {{contact.last_name}}<br>
                                            {{contact.email}}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Invoice Items -->
                    <tr>
                        <td style="padding: 0 40px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                                <tr style="background-color: #f8fafc;">
                                    <td style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase;">Description</td>
                                    <td style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: right;">Montant</td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px; border-top: 1px solid #e2e8f0;">
                                        <p style="margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #0f172a;">Abonnement Premium</p>
                                        <p style="margin: 0; font-size: 13px; color: #64748b;">Janvier 2026</p>
                                    </td>
                                    <td style="padding: 20px; border-top: 1px solid #e2e8f0; text-align: right;">
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;">99,00 €</p>
                                    </td>
                                </tr>
                                <tr style="background-color: #f8fafc;">
                                    <td style="padding: 20px; border-top: 1px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Total TTC</p>
                                    </td>
                                    <td style="padding: 20px; border-top: 1px solid #e2e8f0; text-align: right;">
                                        <p style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">99,00 €</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- CTA -->
                    <tr>
                        <td style="padding: 0 40px 40px; text-align: center;">
                            <a href="#" style="display: inline-block; padding: 14px 32px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 15px;">
                                Télécharger le PDF
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">© {{current_year}} Votre Entreprise</p>
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
    [
        'name' => 'Confirmation d\'Expédition',
        'description' => 'Notification d\'expédition avec suivi',
        'category' => 'transactional',
        'default_subject' => '📦 Votre commande est en route !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #eff6ff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 48px 40px; text-align: center;">
                            <span style="font-size: 48px;">📦</span>
                            <h1 style="margin: 16px 0 8px; font-size: 28px; font-weight: 700; color: #ffffff;">En route !</h1>
                            <p style="margin: 0; font-size: 16px; color: rgba(255,255,255,0.9);">Votre colis a été expédié</p>
                        </td>
                    </tr>
                    <!-- Tracking -->
                    <tr>
                        <td style="padding: 40px;">
                            <div style="background-color: #f0f9ff; border: 2px solid #bae6fd; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 32px;">
                                <p style="margin: 0 0 8px; font-size: 12px; color: #0284c7; text-transform: uppercase; font-weight: 600;">Numéro de suivi</p>
                                <p style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; letter-spacing: 2px;">{{tracking_number}}</p>
                            </div>
                            <!-- Progress -->
                            <div style="margin-bottom: 32px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="33%" style="text-align: center;">
                                            <div style="width: 40px; height: 40px; background-color: #22c55e; border-radius: 50%; margin: 0 auto 8px; line-height: 40px; color: #fff;">✓</div>
                                            <p style="margin: 0; font-size: 12px; color: #64748b;">Expédié</p>
                                        </td>
                                        <td width="33%" style="text-align: center;">
                                            <div style="width: 40px; height: 40px; background-color: #3b82f6; border-radius: 50%; margin: 0 auto 8px; line-height: 40px; color: #fff;">📦</div>
                                            <p style="margin: 0; font-size: 12px; color: #64748b;">En transit</p>
                                        </td>
                                        <td width="33%" style="text-align: center;">
                                            <div style="width: 40px; height: 40px; background-color: #e2e8f0; border-radius: 50%; margin: 0 auto 8px; line-height: 40px; color: #94a3b8;">🏠</div>
                                            <p style="margin: 0; font-size: 12px; color: #64748b;">Livré</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #64748b; text-align: center;">
                                Livraison estimée : <strong style="color: #0f172a;">{{delivery_date}}</strong>
                            </p>
                            <div style="text-align: center;">
                                <a href="#" style="display: inline-block; padding: 16px 32px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                    Suivre mon colis
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">© {{current_year}} Votre Entreprise</p>
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
