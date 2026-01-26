<?php

return [
    [
        'name' => 'Newsletter Moderne',
        'description' => 'Newsletter avec design moderne et sections multiples',
        'category' => 'newsletter',
        'default_subject' => '📬 Les actualités de la semaine',
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
            <td align="center" style="padding: 40px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 16px 16px 0 0; padding: 32px 40px; text-align: center; border-bottom: 1px solid #e2e8f0;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;">NEWSLETTER</h1>
                            <p style="margin: 8px 0 0; font-size: 14px; color: #64748b;">Édition du {{current_date}}</p>
                        </td>
                    </tr>
                    <!-- Featured Article -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">
                            <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=520&h=280&fit=crop" alt="Article" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 24px;">
                            <span style="display: inline-block; padding: 6px 12px; background-color: #ede9fe; color: #7c3aed; font-size: 12px; font-weight: 600; border-radius: 6px; margin-bottom: 12px;">À LA UNE</span>
                            <h2 style="margin: 0 0 12px; font-size: 24px; font-weight: 700; color: #0f172a; line-height: 1.3;">
                                Titre de l'article principal qui attire l'attention
                            </h2>
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.7; color: #475569;">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.
                            </p>
                            <a href="#" style="display: inline-flex; align-items: center; color: #6366f1; text-decoration: none; font-weight: 600; font-size: 15px;">
                                Lire l'article complet →
                            </a>
                        </td>
                    </tr>
                    <!-- Divider -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 0 40px;">
                            <div style="height: 1px; background-color: #e2e8f0;"></div>
                        </td>
                    </tr>
                    <!-- Secondary Articles -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">
                            <h3 style="margin: 0 0 24px; font-size: 18px; font-weight: 700; color: #0f172a;">Également cette semaine</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="48%" style="vertical-align: top;">
                                        <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=260&h=160&fit=crop" alt="Article" style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 16px;">
                                        <h4 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #0f172a; line-height: 1.4;">Article secondaire numéro un</h4>
                                        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">Description courte de l'article.</p>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align: top;">
                                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=260&h=160&fit=crop" alt="Article" style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 16px;">
                                        <h4 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #0f172a; line-height: 1.4;">Article secondaire numéro deux</h4>
                                        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">Description courte de l'article.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; border-radius: 0 0 16px 16px; padding: 32px 40px; text-align: center;">
                            <p style="margin: 0 0 16px; font-size: 14px; color: #94a3b8;">Suivez-nous</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td style="padding: 0 8px;"><a href="#" style="color: #ffffff; text-decoration: none;">Twitter</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="color: #ffffff; text-decoration: none;">LinkedIn</a></td>
                                    <td style="padding: 0 8px;"><a href="#" style="color: #ffffff; text-decoration: none;">Facebook</a></td>
                                </tr>
                            </table>
                            <p style="margin: 0; font-size: 12px; color: #64748b;">
                                © {{current_year}} · <a href="{{unsubscribe_url}}" style="color: #64748b;">Se désinscrire</a>
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
        'name' => 'Newsletter Digest',
        'description' => 'Format digest avec liste d\'articles numérotés',
        'category' => 'newsletter',
        'default_subject' => '📰 Votre digest hebdomadaire #{{issue_number}}',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: Georgia, 'Times New Roman', serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 60px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 560px;">
                    <!-- Header -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 40px; border-bottom: 2px solid #0f172a;">
                            <h1 style="margin: 0; font-size: 32px; font-weight: 400; color: #0f172a; letter-spacing: 4px;">DIGEST</h1>
                            <p style="margin: 12px 0 0; font-size: 14px; color: #64748b; font-family: -apple-system, sans-serif;">Issue #42 · {{current_date}}</p>
                        </td>
                    </tr>
                    <!-- Intro -->
                    <tr>
                        <td style="padding: 40px 0;">
                            <p style="margin: 0; font-size: 20px; line-height: 1.8; color: #334155;">
                                Bonjour {{contact.first_name}},
                            </p>
                            <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.8; color: #475569;">
                                Voici les 5 articles les plus importants de cette semaine, soigneusement sélectionnés pour vous.
                            </p>
                        </td>
                    </tr>
                    <!-- Articles -->
                    <tr>
                        <td style="padding: 0 0 40px;">
                            <!-- Article 1 -->
                            <div style="padding: 24px 0; border-top: 1px solid #e2e8f0;">
                                <span style="display: inline-block; width: 32px; height: 32px; background-color: #0f172a; color: #ffffff; text-align: center; line-height: 32px; border-radius: 50%; font-family: -apple-system, sans-serif; font-weight: 600; font-size: 14px; margin-bottom: 16px;">1</span>
                                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #0f172a; line-height: 1.4;">
                                    <a href="#" style="color: #0f172a; text-decoration: none;">Premier article important de la semaine</a>
                                </h3>
                                <p style="margin: 0; font-size: 16px; line-height: 1.7; color: #64748b;">
                                    Résumé concis de l'article qui donne envie d'en savoir plus sur le sujet abordé.
                                </p>
                            </div>
                            <!-- Article 2 -->
                            <div style="padding: 24px 0; border-top: 1px solid #e2e8f0;">
                                <span style="display: inline-block; width: 32px; height: 32px; background-color: #0f172a; color: #ffffff; text-align: center; line-height: 32px; border-radius: 50%; font-family: -apple-system, sans-serif; font-weight: 600; font-size: 14px; margin-bottom: 16px;">2</span>
                                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #0f172a; line-height: 1.4;">
                                    <a href="#" style="color: #0f172a; text-decoration: none;">Deuxième sujet qui mérite votre attention</a>
                                </h3>
                                <p style="margin: 0; font-size: 16px; line-height: 1.7; color: #64748b;">
                                    Description engageante qui résume les points clés de cet article passionnant.
                                </p>
                            </div>
                            <!-- Article 3 -->
                            <div style="padding: 24px 0; border-top: 1px solid #e2e8f0;">
                                <span style="display: inline-block; width: 32px; height: 32px; background-color: #0f172a; color: #ffffff; text-align: center; line-height: 32px; border-radius: 50%; font-family: -apple-system, sans-serif; font-weight: 600; font-size: 14px; margin-bottom: 16px;">3</span>
                                <h3 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: #0f172a; line-height: 1.4;">
                                    <a href="#" style="color: #0f172a; text-decoration: none;">Troisième découverte à ne pas manquer</a>
                                </h3>
                                <p style="margin: 0; font-size: 16px; line-height: 1.7; color: #64748b;">
                                    Un aperçu de ce contenu exceptionnel qui pourrait changer votre perspective.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding-top: 40px; border-top: 2px solid #0f172a; text-align: center;">
                            <p style="margin: 0; font-size: 14px; color: #94a3b8; font-family: -apple-system, sans-serif;">
                                <a href="{{unsubscribe_url}}" style="color: #94a3b8; text-decoration: underline;">Se désinscrire</a>
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
