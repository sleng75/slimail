<?php

return [
    [
        'name' => 'Bienvenue Simple',
        'description' => 'Template de bienvenue minimaliste et élégant',
        'category' => 'welcome',
        'default_subject' => 'Bienvenue {{contact.first_name}} !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f5;">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 48px 40px 32px; text-align: center;">
                            <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 16px; margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 32px; line-height: 72px;">👋</span>
                            </div>
                            <h1 style="margin: 0 0 16px; font-size: 28px; font-weight: 700; color: #18181b; letter-spacing: -0.5px;">
                                Bienvenue {{contact.first_name}} !
                            </h1>
                            <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #71717a;">
                                Nous sommes ravis de vous accueillir parmi nous.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 40px 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.7; color: #52525b; text-align: center;">
                                Votre compte a été créé avec succès. Vous pouvez maintenant accéder à toutes nos fonctionnalités et commencer votre aventure.
                            </p>
                            <div style="text-align: center;">
                                <a href="#" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
                                    Accéder à mon compte
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; font-size: 13px; color: #a1a1aa;">
                                © {{current_year}} Votre Entreprise · <a href="{{unsubscribe_url}}" style="color: #a1a1aa; text-decoration: underline;">Se désinscrire</a>
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
    [
        'name' => 'Bienvenue Premium',
        'description' => 'Template de bienvenue avec design premium et étapes',
        'category' => 'welcome',
        'default_subject' => 'Bienvenue dans la famille {{company_name}} !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Hero -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #7c3aed 50%, #db2777 100%); border-radius: 24px 24px 0 0; padding: 60px 40px; text-align: center;">
                            <h1 style="margin: 0 0 16px; font-size: 36px; font-weight: 800; color: #ffffff; letter-spacing: -1px;">
                                Bienvenue ! 🎉
                            </h1>
                            <p style="margin: 0; font-size: 18px; color: rgba(255,255,255,0.9);">
                                {{contact.first_name}}, votre aventure commence maintenant
                            </p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 48px 40px;">
                            <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #475569;">
                                Merci de nous avoir rejoints ! Voici les 3 étapes pour bien démarrer :
                            </p>
                            <!-- Steps -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding: 16px 20px; background-color: #f8fafc; border-radius: 12px; margin-bottom: 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="48" style="vertical-align: top;">
                                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; text-align: center; line-height: 40px; color: #fff; font-weight: 700;">1</div>
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #1e293b;">Complétez votre profil</h3>
                                                    <p style="margin: 0; font-size: 14px; color: #64748b;">Ajoutez vos informations pour personnaliser votre expérience</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height: 12px;"></td></tr>
                                <tr>
                                    <td style="padding: 16px 20px; background-color: #f8fafc; border-radius: 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="48" style="vertical-align: top;">
                                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; text-align: center; line-height: 40px; color: #fff; font-weight: 700;">2</div>
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #1e293b;">Explorez les fonctionnalités</h3>
                                                    <p style="margin: 0; font-size: 14px; color: #64748b;">Découvrez tout ce que vous pouvez accomplir</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td style="height: 12px;"></td></tr>
                                <tr>
                                    <td style="padding: 16px 20px; background-color: #f8fafc; border-radius: 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="48" style="vertical-align: top;">
                                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; text-align: center; line-height: 40px; color: #fff; font-weight: 700;">3</div>
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #1e293b;">Lancez votre première campagne</h3>
                                                    <p style="margin: 0; font-size: 14px; color: #64748b;">Créez et envoyez votre premier email en quelques clics</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <div style="text-align: center; margin-top: 32px;">
                                <a href="#" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                    Commencer maintenant →
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 0 0 24px 24px; padding: 32px 40px; text-align: center;">
                            <p style="margin: 0 0 8px; font-size: 13px; color: #94a3b8;">
                                © {{current_year}} Votre Entreprise
                            </p>
                            <p style="margin: 0; font-size: 13px;">
                                <a href="{{unsubscribe_url}}" style="color: #64748b;">Se désinscrire</a>
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
    [
        'name' => 'Bienvenue SaaS',
        'description' => 'Template pour applications SaaS avec période d\'essai',
        'category' => 'welcome',
        'default_subject' => 'Votre essai gratuit commence maintenant !',
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
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
                    <tr>
                        <td style="padding: 48px 40px; text-align: center;">
                            <div style="display: inline-block; padding: 8px 16px; background-color: #dcfce7; border-radius: 20px; margin-bottom: 24px;">
                                <span style="font-size: 14px; font-weight: 600; color: #16a34a;">✓ Compte activé</span>
                            </div>
                            <h1 style="margin: 0 0 16px; font-size: 28px; font-weight: 700; color: #0f172a;">
                                Bienvenue {{contact.first_name}} !
                            </h1>
                            <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.6; color: #64748b;">
                                Votre essai gratuit de <strong style="color: #0f172a;">14 jours</strong> est maintenant actif. Profitez de toutes les fonctionnalités premium sans engagement.
                            </p>
                            <!-- Trial Badge -->
                            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #86efac; border-radius: 16px; padding: 24px; margin-bottom: 32px;">
                                <p style="margin: 0 0 8px; font-size: 14px; color: #16a34a; font-weight: 600;">PÉRIODE D'ESSAI</p>
                                <p style="margin: 0; font-size: 32px; font-weight: 800; color: #15803d;">14 jours restants</p>
                            </div>
                            <a href="#" style="display: inline-block; padding: 16px 32px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                Accéder à mon espace
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 40px; background-color: #f8fafc; text-align: center;">
                            <p style="margin: 0 0 16px; font-size: 14px; color: #64748b;">
                                Besoin d'aide ? Notre équipe est là pour vous.
                            </p>
                            <a href="#" style="color: #6366f1; text-decoration: none; font-weight: 600;">Contacter le support →</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                © {{current_year}} · <a href="{{unsubscribe_url}}" style="color: #94a3b8;">Se désinscrire</a>
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
