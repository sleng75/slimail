<?php

return [
    [
        'name' => 'Promotion Flash',
        'description' => 'Template pour offres flash avec compte à rebours',
        'category' => 'promotional',
        'default_subject' => '⚡ FLASH SALE - {{discount}}% de réduction !',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #18181b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 40px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Hero -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 20px 20px 0 0; padding: 60px 40px; text-align: center;">
                            <p style="margin: 0 0 8px; font-size: 14px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 3px; font-weight: 600;">Offre limitée</p>
                            <h1 style="margin: 0 0 16px; font-size: 72px; font-weight: 800; color: #ffffff; line-height: 1;">-50%</h1>
                            <p style="margin: 0; font-size: 24px; color: #ffffff; font-weight: 600;">Sur toute la collection</p>
                        </td>
                    </tr>
                    <!-- Timer -->
                    <tr>
                        <td style="background-color: #27272a; padding: 32px 40px;">
                            <p style="margin: 0 0 16px; font-size: 14px; color: #a1a1aa; text-align: center; text-transform: uppercase; letter-spacing: 2px;">Se termine dans</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="25%" style="text-align: center;">
                                        <div style="background-color: #3f3f46; border-radius: 12px; padding: 20px 0;">
                                            <span style="font-size: 36px; font-weight: 700; color: #ffffff;">23</span>
                                            <p style="margin: 8px 0 0; font-size: 12px; color: #71717a; text-transform: uppercase;">Heures</p>
                                        </div>
                                    </td>
                                    <td width="8px" style="text-align: center; color: #71717a; font-size: 24px;">:</td>
                                    <td width="25%" style="text-align: center;">
                                        <div style="background-color: #3f3f46; border-radius: 12px; padding: 20px 0;">
                                            <span style="font-size: 36px; font-weight: 700; color: #ffffff;">59</span>
                                            <p style="margin: 8px 0 0; font-size: 12px; color: #71717a; text-transform: uppercase;">Minutes</p>
                                        </div>
                                    </td>
                                    <td width="8px" style="text-align: center; color: #71717a; font-size: 24px;">:</td>
                                    <td width="25%" style="text-align: center;">
                                        <div style="background-color: #3f3f46; border-radius: 12px; padding: 20px 0;">
                                            <span style="font-size: 36px; font-weight: 700; color: #ffffff;">42</span>
                                            <p style="margin: 8px 0 0; font-size: 12px; color: #71717a; text-transform: uppercase;">Secondes</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Promo Code -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px; text-align: center;">
                            <p style="margin: 0 0 16px; font-size: 16px; color: #52525b;">Utilisez le code promo :</p>
                            <div style="display: inline-block; padding: 16px 32px; background-color: #fef2f2; border: 2px dashed #dc2626; border-radius: 12px; margin-bottom: 32px;">
                                <span style="font-size: 28px; font-weight: 700; color: #dc2626; letter-spacing: 6px;">FLASH50</span>
                            </div>
                            <br>
                            <a href="#" style="display: inline-block; padding: 18px 48px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 18px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);">
                                PROFITER DE L'OFFRE →
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #18181b; border-radius: 0 0 20px 20px; padding: 24px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #71717a;">
                                <a href="{{unsubscribe_url}}" style="color: #71717a;">Se désinscrire</a>
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
        'name' => 'Vitrine Produits',
        'description' => 'Présentation élégante de produits en grille',
        'category' => 'promotional',
        'default_subject' => '✨ Découvrez notre nouvelle collection',
        'html_content' => <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #fafaf9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 48px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 32px; text-align: center;">
                            <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 300; color: #292524; letter-spacing: 4px; text-transform: uppercase;">Nouvelle Collection</h1>
                            <p style="margin: 0; font-size: 16px; color: #78716c;">Découvrez nos dernières créations</p>
                        </td>
                    </tr>
                    <!-- Products Grid -->
                    <tr>
                        <td style="padding: 0 40px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="48%" style="vertical-align: top; padding-bottom: 24px;">
                                        <div style="background-color: #f5f5f4; border-radius: 12px; overflow: hidden;">
                                            <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=260&h=260&fit=crop" alt="Produit" style="width: 100%; display: block;">
                                            <div style="padding: 20px;">
                                                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #292524;">Produit Élégant</h3>
                                                <p style="margin: 0 0 12px; font-size: 14px; color: #78716c;">Design minimaliste</p>
                                                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #292524;">89,00 €</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align: top; padding-bottom: 24px;">
                                        <div style="background-color: #f5f5f4; border-radius: 12px; overflow: hidden;">
                                            <img src="https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=260&h=260&fit=crop" alt="Produit" style="width: 100%; display: block;">
                                            <div style="padding: 20px;">
                                                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #292524;">Accessoire Premium</h3>
                                                <p style="margin: 0 0 12px; font-size: 14px; color: #78716c;">Qualité artisanale</p>
                                                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #292524;">129,00 €</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="48%" style="vertical-align: top;">
                                        <div style="background-color: #f5f5f4; border-radius: 12px; overflow: hidden;">
                                            <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=260&h=260&fit=crop" alt="Produit" style="width: 100%; display: block;">
                                            <div style="padding: 20px;">
                                                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #292524;">Objet Signature</h3>
                                                <p style="margin: 0 0 12px; font-size: 14px; color: #78716c;">Édition limitée</p>
                                                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #292524;">199,00 €</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align: top;">
                                        <div style="background-color: #f5f5f4; border-radius: 12px; overflow: hidden;">
                                            <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?w=260&h=260&fit=crop" alt="Produit" style="width: 100%; display: block;">
                                            <div style="padding: 20px;">
                                                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #292524;">Création Unique</h3>
                                                <p style="margin: 0 0 12px; font-size: 14px; color: #78716c;">Fait main</p>
                                                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #292524;">249,00 €</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- CTA -->
                    <tr>
                        <td style="padding: 0 40px 48px; text-align: center;">
                            <a href="#" style="display: inline-block; padding: 16px 40px; background-color: #292524; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; letter-spacing: 1px; text-transform: uppercase;">
                                Voir la collection
                            </a>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafaf9; text-align: center; border-top: 1px solid #e7e5e4;">
                            <p style="margin: 0; font-size: 12px; color: #a8a29e;">
                                © {{current_year}} · <a href="{{unsubscribe_url}}" style="color: #a8a29e;">Se désinscrire</a>
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
        'name' => 'Soldes Saisonnières',
        'description' => 'Template pour soldes avec plusieurs catégories',
        'category' => 'promotional',
        'default_subject' => '🔥 SOLDES : Jusqu\'à -70% sur tout le site !',
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
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Hero -->
                    <tr>
                        <td style="background-color: #dc2626; border-radius: 20px 20px 0 0; padding: 48px 40px; text-align: center;">
                            <h1 style="margin: 0 0 8px; font-size: 48px; font-weight: 800; color: #ffffff; letter-spacing: -1px;">SOLDES</h1>
                            <p style="margin: 0 0 24px; font-size: 24px; color: #fecaca;">Jusqu'à -70%</p>
                            <a href="#" style="display: inline-block; padding: 14px 32px; background-color: #ffffff; color: #dc2626; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 16px;">
                                VOIR LES OFFRES
                            </a>
                        </td>
                    </tr>
                    <!-- Categories -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">
                            <h2 style="margin: 0 0 24px; font-size: 20px; font-weight: 700; color: #18181b; text-align: center;">Par catégorie</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="32%" style="text-align: center; padding: 16px; background-color: #fef2f2; border-radius: 12px;">
                                        <p style="margin: 0 0 4px; font-size: 28px; font-weight: 700; color: #dc2626;">-50%</p>
                                        <p style="margin: 0; font-size: 14px; font-weight: 600; color: #57534e;">Mode</p>
                                    </td>
                                    <td width="2%"></td>
                                    <td width="32%" style="text-align: center; padding: 16px; background-color: #fef2f2; border-radius: 12px;">
                                        <p style="margin: 0 0 4px; font-size: 28px; font-weight: 700; color: #dc2626;">-60%</p>
                                        <p style="margin: 0; font-size: 14px; font-weight: 600; color: #57534e;">Accessoires</p>
                                    </td>
                                    <td width="2%"></td>
                                    <td width="32%" style="text-align: center; padding: 16px; background-color: #fef2f2; border-radius: 12px;">
                                        <p style="margin: 0 0 4px; font-size: 28px; font-weight: 700; color: #dc2626;">-70%</p>
                                        <p style="margin: 0; font-size: 14px; font-weight: 600; color: #57534e;">Déco</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #18181b; border-radius: 0 0 20px 20px; padding: 24px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #71717a;">
                                <a href="{{unsubscribe_url}}" style="color: #71717a;">Se désinscrire</a>
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
