<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class TemplateLibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates system templates available to all tenants.
     */
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                [
                    'name' => $template['name'],
                    'is_system' => true,
                ],
                array_merge($template, [
                    'is_system' => true,
                    'is_active' => true,
                    'tenant_id' => null,
                ])
            );
        }

        $this->command->info('Created ' . count($templates) . ' system templates.');
    }

    /**
     * Get all template definitions.
     */
    protected function getTemplates(): array
    {
        return [
            // Newsletter Simple
            [
                'name' => 'Newsletter Simple',
                'description' => 'Une newsletter épurée et moderne pour vos communications régulières',
                'category' => 'newsletter',
                'default_subject' => 'Notre Newsletter du mois',
                'html_content' => $this->getNewsletterSimple(),
            ],

            // Newsletter avec Image
            [
                'name' => 'Newsletter avec Image',
                'description' => 'Newsletter avec une grande image d\'en-tête et sections de contenu',
                'category' => 'newsletter',
                'default_subject' => 'Les dernières nouvelles',
                'html_content' => $this->getNewsletterWithImage(),
            ],

            // Email de Bienvenue
            [
                'name' => 'Email de Bienvenue',
                'description' => 'Accueillez vos nouveaux abonnés avec un message chaleureux',
                'category' => 'transactionnel',
                'default_subject' => 'Bienvenue chez {{company_name}} !',
                'html_content' => $this->getWelcomeEmail(),
            ],

            // Confirmation d'Inscription
            [
                'name' => 'Confirmation d\'Inscription',
                'description' => 'Confirmez l\'inscription de vos utilisateurs',
                'category' => 'transactionnel',
                'default_subject' => 'Confirmez votre inscription',
                'html_content' => $this->getConfirmationEmail(),
            ],

            // Annonce Produit
            [
                'name' => 'Annonce Produit',
                'description' => 'Présentez un nouveau produit ou service à votre audience',
                'category' => 'marketing',
                'default_subject' => 'Découvrez notre nouvelle offre !',
                'html_content' => $this->getProductAnnouncement(),
            ],

            // Email Promotionnel
            [
                'name' => 'Email Promotionnel',
                'description' => 'Promotion avec code promo et appel à l\'action fort',
                'category' => 'marketing',
                'default_subject' => '🔥 Offre Exclusive : -30% sur tout !',
                'html_content' => $this->getPromotionalEmail(),
            ],

            // Email de Réengagement
            [
                'name' => 'Email de Réengagement',
                'description' => 'Réactivez vos contacts inactifs',
                'category' => 'marketing',
                'default_subject' => 'Vous nous manquez !',
                'html_content' => $this->getReengagementEmail(),
            ],

            // Invitation Événement
            [
                'name' => 'Invitation Événement',
                'description' => 'Invitez votre audience à un événement',
                'category' => 'evenement',
                'default_subject' => 'Vous êtes invité(e) !',
                'html_content' => $this->getEventInvitation(),
            ],

            // Email Minimaliste
            [
                'name' => 'Email Minimaliste',
                'description' => 'Design épuré pour messages importants',
                'category' => 'transactionnel',
                'default_subject' => 'Information importante',
                'html_content' => $this->getMinimalistEmail(),
            ],

            // E-commerce - Panier Abandonné
            [
                'name' => 'Panier Abandonné',
                'description' => 'Relancez les clients qui ont abandonné leur panier',
                'category' => 'ecommerce',
                'default_subject' => 'Vous avez oublié quelque chose !',
                'html_content' => $this->getAbandonedCartEmail(),
            ],

            // E-commerce - Confirmation de Commande
            [
                'name' => 'Confirmation de Commande',
                'description' => 'Confirmez les commandes de vos clients',
                'category' => 'ecommerce',
                'default_subject' => 'Confirmation de votre commande #{{order_id}}',
                'html_content' => $this->getOrderConfirmationEmail(),
            ],

            // Notification Simple
            [
                'name' => 'Notification Simple',
                'description' => 'Notification transactionnelle basique',
                'category' => 'transactionnel',
                'default_subject' => 'Nouvelle notification',
                'html_content' => $this->getSimpleNotification(),
            ],
        ];
    }

    /**
     * Newsletter Simple Template
     */
    protected function getNewsletterSimple(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;text-align:center;border-bottom:1px solid #e5e7eb;">
                            <img src="https://via.placeholder.com/150x50/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:150px;height:auto;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:48px 40px;">
                            <h1 style="margin:0 0 16px;font-size:28px;font-weight:700;color:#111827;text-align:center;">Notre Newsletter</h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4b5563;text-align:center;">Découvrez les dernières actualités et mises à jour de notre équipe.</p>

                            <!-- Article 1 -->
                            <div style="margin-bottom:32px;">
                                <h2 style="margin:0 0 12px;font-size:20px;font-weight:600;color:#111827;">Article à la une</h2>
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#6b7280;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                <a href="#" style="display:inline-block;color:#6366f1;font-size:14px;font-weight:600;text-decoration:none;">Lire la suite →</a>
                            </div>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">

                            <!-- Article 2 -->
                            <div style="margin-bottom:32px;">
                                <h2 style="margin:0 0 12px;font-size:20px;font-weight:600;color:#111827;">Dernières nouvelles</h2>
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#6b7280;">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                                <a href="#" style="display:inline-block;color:#6366f1;font-size:14px;font-weight:600;text-decoration:none;">Lire la suite →</a>
                            </div>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:32px auto 0;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Voir toutes les actualités</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:32px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">© {{current_year}} {{company_name}}. Tous droits réservés.</p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                <a href="{{unsubscribe_url}}" style="color:#6b7280;text-decoration:underline;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Newsletter with Image Template
     */
    protected function getNewsletterWithImage(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <!-- Header with Logo -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;">
                        </td>
                    </tr>
                    <!-- Hero Image -->
                    <tr>
                        <td>
                            <img src="https://via.placeholder.com/600x300/e5e7eb/9ca3af?text=Image+Hero" alt="Hero" style="width:100%;height:auto;display:block;">
                        </td>
                    </tr>
                    <!-- Main Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h1 style="margin:0 0 16px;font-size:26px;font-weight:700;color:#111827;">Titre de la Newsletter</h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4b5563;">Voici les dernières nouvelles de notre équipe. Nous sommes ravis de partager avec vous nos avancées et projets à venir.</p>

                            <!-- Two Column Content -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="48%" style="vertical-align:top;">
                                        <img src="https://via.placeholder.com/260x160/f3f4f6/9ca3af?text=Article+1" alt="Article" style="width:100%;border-radius:8px;margin-bottom:12px;">
                                        <h3 style="margin:0 0 8px;font-size:16px;font-weight:600;color:#111827;">Premier Article</h3>
                                        <p style="margin:0;font-size:14px;line-height:1.5;color:#6b7280;">Description courte de l'article numéro un.</p>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align:top;">
                                        <img src="https://via.placeholder.com/260x160/f3f4f6/9ca3af?text=Article+2" alt="Article" style="width:100%;border-radius:8px;margin-bottom:12px;">
                                        <h3 style="margin:0 0 8px;font-size:16px;font-weight:600;color:#111827;">Second Article</h3>
                                        <p style="margin:0;font-size:14px;line-height:1.5;color:#6b7280;">Description courte de l'article numéro deux.</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" style="margin:32px auto 0;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Découvrir plus</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#1f2937;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}}</p>
                            <p style="margin:0;font-size:12px;">
                                <a href="{{unsubscribe_url}}" style="color:#9ca3af;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Welcome Email Template
     */
    protected function getWelcomeEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header with Gradient -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);padding:48px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/120x40/ffffff/6366f1?text=LOGO" alt="Logo" style="max-width:120px;margin-bottom:24px;">
                            <h1 style="margin:0;font-size:32px;font-weight:700;color:#ffffff;">Bienvenue {{contact.first_name}} !</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:48px 40px;">
                            <p style="margin:0 0 20px;font-size:16px;line-height:1.7;color:#4b5563;">Nous sommes ravis de vous compter parmi nous ! Votre inscription a bien été prise en compte.</p>

                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4b5563;">Voici ce que vous pouvez faire maintenant :</p>

                            <!-- Features -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="padding:16px 0;border-bottom:1px solid #e5e7eb;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;vertical-align:top;">
                                                    <div style="width:40px;height:40px;background:#eef2ff;border-radius:10px;text-align:center;line-height:40px;font-size:20px;">🎯</div>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <h3 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#111827;">Complétez votre profil</h3>
                                                    <p style="margin:0;font-size:14px;color:#6b7280;">Personnalisez votre expérience en ajoutant vos informations.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 0;border-bottom:1px solid #e5e7eb;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;vertical-align:top;">
                                                    <div style="width:40px;height:40px;background:#fef3c7;border-radius:10px;text-align:center;line-height:40px;font-size:20px;">⭐</div>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <h3 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#111827;">Explorez nos fonctionnalités</h3>
                                                    <p style="margin:0;font-size:14px;color:#6b7280;">Découvrez tout ce que nous avons à offrir.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;vertical-align:top;">
                                                    <div style="width:40px;height:40px;background:#dcfce7;border-radius:10px;text-align:center;line-height:40px;font-size:20px;">💬</div>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <h3 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#111827;">Contactez notre support</h3>
                                                    <p style="margin:0;font-size:14px;color:#6b7280;">Notre équipe est là pour vous aider à tout moment.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Accéder à mon compte</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">Si vous avez des questions, contactez-nous à support@example.com</p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Confirmation Email Template
     */
    protected function getConfirmationEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;margin-bottom:24px;">
                            <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;margin:0 auto 24px;text-align:center;line-height:80px;font-size:40px;">✓</div>
                            <h1 style="margin:0 0 16px;font-size:28px;font-weight:700;color:#111827;">Confirmez votre email</h1>
                            <p style="margin:0 0 32px;font-size:16px;line-height:1.6;color:#4b5563;">Cliquez sur le bouton ci-dessous pour confirmer votre adresse email et activer votre compte.</p>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="background:#10b981;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:16px 40px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Confirmer mon email</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#9ca3af;">Ce lien expirera dans 24 heures.</p>
                        </td>
                    </tr>
                    <!-- Alternative Link -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</p>
                            <p style="margin:0;font-size:12px;color:#6366f1;word-break:break-all;">https://example.com/confirm?token=xxxxx</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Product Announcement Template
     */
    protected function getProductAnnouncement(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Pre-header -->
                    <tr>
                        <td style="padding:16px 40px;background:#fef3c7;text-align:center;">
                            <p style="margin:0;font-size:14px;font-weight:600;color:#92400e;">🎉 Nouveau produit disponible !</p>
                        </td>
                    </tr>
                    <!-- Header -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;">
                        </td>
                    </tr>
                    <!-- Product Image -->
                    <tr>
                        <td style="padding:0 40px;">
                            <img src="https://via.placeholder.com/520x320/f3f4f6/9ca3af?text=Nouveau+Produit" alt="Product" style="width:100%;border-radius:12px;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h1 style="margin:0 0 16px;font-size:28px;font-weight:700;color:#111827;text-align:center;">Découvrez notre nouveauté</h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4b5563;text-align:center;">Nous sommes fiers de vous présenter notre dernier produit, conçu pour répondre à vos besoins les plus exigeants.</p>

                            <!-- Features -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td width="33%" style="text-align:center;padding:16px;">
                                        <div style="font-size:32px;margin-bottom:8px;">⚡</div>
                                        <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">Ultra rapide</p>
                                    </td>
                                    <td width="33%" style="text-align:center;padding:16px;">
                                        <div style="font-size:32px;margin-bottom:8px;">🔒</div>
                                        <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">Sécurisé</p>
                                    </td>
                                    <td width="33%" style="text-align:center;padding:16px;">
                                        <div style="font-size:32px;margin-bottom:8px;">💎</div>
                                        <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">Premium</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Price -->
                            <div style="text-align:center;margin-bottom:24px;">
                                <p style="margin:0 0 8px;font-size:14px;color:#6b7280;">À partir de</p>
                                <p style="margin:0;font-size:36px;font-weight:700;color:#6366f1;">99,99 €</p>
                            </div>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:16px 40px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Découvrir maintenant</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">© {{current_year}} {{company_name}}</p>
                            <p style="margin:0;font-size:12px;"><a href="{{unsubscribe_url}}" style="color:#6b7280;">Se désinscrire</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Promotional Email Template
     */
    protected function getPromotionalEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;">
                        </td>
                    </tr>
                    <!-- Hero Banner -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);padding:48px 40px;text-align:center;">
                            <p style="margin:0 0 8px;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,0.8);">Offre Exclusive</p>
                            <h1 style="margin:0 0 8px;font-size:48px;font-weight:800;color:#ffffff;">-30%</h1>
                            <p style="margin:0 0 24px;font-size:20px;color:rgba(255,255,255,0.9);">sur toute la boutique</p>
                            <div style="display:inline-block;background:rgba(255,255,255,0.2);border-radius:8px;padding:12px 24px;">
                                <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.8);">Code promo :</p>
                                <p style="margin:4px 0 0;font-size:24px;font-weight:700;color:#ffffff;letter-spacing:4px;">PROMO30</p>
                            </div>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;text-align:center;">Offre limitée dans le temps</h2>
                            <p style="margin:0 0 32px;font-size:16px;line-height:1.7;color:#4b5563;text-align:center;">Profitez de cette offre exceptionnelle valable jusqu'au 31 janvier. Ne manquez pas cette opportunité !</p>

                            <!-- Products Grid -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td width="48%" style="vertical-align:top;">
                                        <img src="https://via.placeholder.com/260x200/f3f4f6/9ca3af?text=Produit+1" alt="Product" style="width:100%;border-radius:8px;margin-bottom:12px;">
                                        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#111827;">Produit Premium</p>
                                        <p style="margin:0;"><span style="font-size:14px;color:#9ca3af;text-decoration:line-through;">149,99 €</span> <span style="font-size:16px;font-weight:700;color:#dc2626;">104,99 €</span></p>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="vertical-align:top;">
                                        <img src="https://via.placeholder.com/260x200/f3f4f6/9ca3af?text=Produit+2" alt="Product" style="width:100%;border-radius:8px;margin-bottom:12px;">
                                        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#111827;">Produit Exclusif</p>
                                        <p style="margin:0;"><span style="font-size:14px;color:#9ca3af;text-decoration:line-through;">89,99 €</span> <span style="font-size:16px;font-weight:700;color:#dc2626;">62,99 €</span></p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="background:#dc2626;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:16px 48px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;">J'en profite maintenant !</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Urgency -->
                    <tr>
                        <td style="padding:20px 40px;background:#fef2f2;text-align:center;">
                            <p style="margin:0;font-size:14px;color:#991b1b;">⏰ Cette offre expire dans 48 heures !</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">© {{current_year}} {{company_name}}</p>
                            <p style="margin:0;font-size:12px;"><a href="{{unsubscribe_url}}" style="color:#6b7280;">Se désinscrire</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Reengagement Email Template
     */
    protected function getReengagementEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;margin-bottom:24px;">
                            <div style="font-size:64px;margin-bottom:16px;">💔</div>
                            <h1 style="margin:0 0 16px;font-size:28px;font-weight:700;color:#111827;">Vous nous manquez !</h1>
                            <p style="margin:0 0 32px;font-size:16px;line-height:1.7;color:#4b5563;">Cela fait un moment que nous n'avons pas eu de vos nouvelles, {{contact.first_name}}. Nous espérons que tout va bien !</p>

                            <p style="margin:0 0 24px;font-size:16px;color:#4b5563;">Pour vous souhaiter un bon retour, voici une offre spéciale :</p>

                            <!-- Offer Box -->
                            <div style="background:#eef2ff;border-radius:12px;padding:24px;margin-bottom:32px;">
                                <p style="margin:0 0 8px;font-size:16px;font-weight:600;color:#6366f1;">Offre spéciale retour</p>
                                <p style="margin:0;font-size:32px;font-weight:700;color:#111827;">-20% sur votre prochaine commande</p>
                                <p style="margin:16px 0 0;font-size:14px;color:#6b7280;">Code : <strong>COMEBACK20</strong></p>
                            </div>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Revenir sur le site</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#9ca3af;">Si vous ne souhaitez plus recevoir nos emails, vous pouvez <a href="{{unsubscribe_url}}" style="color:#6366f1;">vous désinscrire ici</a>.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Event Invitation Template
     */
    protected function getEventInvitation(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:24px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;">
                        </td>
                    </tr>
                    <!-- Event Image -->
                    <tr>
                        <td>
                            <img src="https://via.placeholder.com/600x280/6366f1/ffffff?text=Evenement" alt="Event" style="width:100%;height:auto;display:block;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 8px;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#6366f1;">Vous êtes invité(e)</p>
                            <h1 style="margin:0 0 24px;font-size:28px;font-weight:700;color:#111827;">Conférence Annuelle 2025</h1>

                            <!-- Event Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="padding:16px 0;border-bottom:1px solid #e5e7eb;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;"><span style="font-size:24px;">📅</span></td>
                                                <td>
                                                    <p style="margin:0 0 2px;font-size:12px;color:#6b7280;">Date</p>
                                                    <p style="margin:0;font-size:15px;font-weight:600;color:#111827;">25 Janvier 2025</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 0;border-bottom:1px solid #e5e7eb;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;"><span style="font-size:24px;">🕐</span></td>
                                                <td>
                                                    <p style="margin:0 0 2px;font-size:12px;color:#6b7280;">Heure</p>
                                                    <p style="margin:0;font-size:15px;font-weight:600;color:#111827;">09:00 - 18:00</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:48px;"><span style="font-size:24px;">📍</span></td>
                                                <td>
                                                    <p style="margin:0 0 2px;font-size:12px;color:#6b7280;">Lieu</p>
                                                    <p style="margin:0;font-size:15px;font-weight:600;color:#111827;">Centre de Congrès, Abidjan</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 32px;font-size:16px;line-height:1.7;color:#4b5563;">Rejoignez-nous pour une journée exceptionnelle de conférences, ateliers et networking. Ne manquez pas cette occasion de rencontrer les experts du secteur !</p>

                            <!-- CTA Buttons -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align:center;">
                                        <table cellpadding="0" cellspacing="0" style="display:inline-table;">
                                            <tr>
                                                <td style="background:#6366f1;border-radius:8px;">
                                                    <a href="#" style="display:inline-block;padding:14px 28px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Je participe</a>
                                                </td>
                                                <td style="width:12px;"></td>
                                                <td style="border:2px solid #e5e7eb;border-radius:8px;">
                                                    <a href="#" style="display:inline-block;padding:12px 24px;font-size:16px;font-weight:600;color:#374151;text-decoration:none;">Plus d'infos</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">© {{current_year}} {{company_name}}</p>
                            <p style="margin:0;font-size:12px;"><a href="{{unsubscribe_url}}" style="color:#6b7280;">Se désinscrire</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Minimalist Email Template
     */
    protected function getMinimalistEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;padding:48px 24px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
                    <!-- Logo -->
                    <tr>
                        <td style="padding-bottom:32px;">
                            <img src="https://via.placeholder.com/100x32/111827/ffffff?text=LOGO" alt="Logo" style="max-width:100px;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td>
                            <h1 style="margin:0 0 20px;font-size:24px;font-weight:600;color:#111827;line-height:1.3;">Titre de votre message</h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#4b5563;">Bonjour {{contact.first_name}},</p>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;color:#4b5563;">Voici le contenu de votre message. Ce template minimaliste est parfait pour les communications importantes qui nécessitent une attention particulière.</p>
                            <p style="margin:0 0 32px;font-size:16px;line-height:1.7;color:#4b5563;">N'hésitez pas à nous contacter si vous avez des questions.</p>

                            <!-- Simple CTA -->
                            <p style="margin:0 0 32px;">
                                <a href="#" style="font-size:16px;font-weight:600;color:#6366f1;text-decoration:none;">En savoir plus →</a>
                            </p>

                            <p style="margin:0;font-size:16px;color:#4b5563;">Cordialement,<br><strong>L'équipe {{company_name}}</strong></p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding-top:48px;border-top:1px solid #e5e7eb;margin-top:48px;">
                            <p style="margin:0;font-size:13px;color:#9ca3af;">
                                © {{current_year}} {{company_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af;">Se désinscrire</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Abandoned Cart Email Template
     */
    protected function getAbandonedCartEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;text-align:center;">
                            <img src="https://via.placeholder.com/140x45/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:140px;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:0 40px 40px;">
                            <div style="text-align:center;margin-bottom:32px;">
                                <span style="font-size:48px;">🛒</span>
                                <h1 style="margin:16px 0 8px;font-size:26px;font-weight:700;color:#111827;">Vous avez oublié quelque chose !</h1>
                                <p style="margin:0;font-size:16px;color:#6b7280;">Votre panier vous attend patiemment...</p>
                            </div>

                            <!-- Cart Items -->
                            <div style="background:#f9fafb;border-radius:12px;padding:24px;margin-bottom:24px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="80">
                                                        <img src="https://via.placeholder.com/70x70/e5e7eb/9ca3af?text=Prod" alt="Product" style="width:70px;height:70px;border-radius:8px;">
                                                    </td>
                                                    <td style="padding-left:16px;">
                                                        <p style="margin:0 0 4px;font-size:15px;font-weight:600;color:#111827;">Produit Premium</p>
                                                        <p style="margin:0;font-size:14px;color:#6b7280;">Quantité: 1</p>
                                                    </td>
                                                    <td style="text-align:right;">
                                                        <p style="margin:0;font-size:16px;font-weight:600;color:#111827;">49,99 €</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 0 0;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td><p style="margin:0;font-size:16px;font-weight:600;color:#111827;">Total</p></td>
                                                    <td style="text-align:right;"><p style="margin:0;font-size:20px;font-weight:700;color:#6366f1;">49,99 €</p></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:16px 40px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Finaliser ma commande</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#9ca3af;text-align:center;">Votre panier sera conservé pendant 24h</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">© {{current_year}} {{company_name}}</p>
                            <p style="margin:0;font-size:12px;"><a href="{{unsubscribe_url}}" style="color:#6b7280;">Se désinscrire</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Order Confirmation Email Template
     */
    protected function getOrderConfirmationEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;text-align:center;background:#10b981;">
                            <img src="https://via.placeholder.com/120x40/ffffff/10b981?text=LOGO" alt="Logo" style="max-width:120px;margin-bottom:16px;">
                            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;text-align:center;line-height:64px;">
                                <span style="font-size:32px;">✓</span>
                            </div>
                            <h1 style="margin:0;font-size:24px;font-weight:700;color:#ffffff;">Commande confirmée !</h1>
                        </td>
                    </tr>
                    <!-- Order Details -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="margin:0 0 24px;font-size:16px;color:#4b5563;">Merci pour votre commande, {{contact.first_name}} ! Voici les détails :</p>

                            <!-- Order Info -->
                            <div style="background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="50%">
                                            <p style="margin:0 0 4px;font-size:12px;color:#6b7280;">Numéro de commande</p>
                                            <p style="margin:0;font-size:16px;font-weight:600;color:#111827;">#{{order_id}}</p>
                                        </td>
                                        <td width="50%">
                                            <p style="margin:0 0 4px;font-size:12px;color:#6b7280;">Date</p>
                                            <p style="margin:0;font-size:16px;font-weight:600;color:#111827;">{{order_date}}</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Items -->
                            <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;color:#111827;">Articles commandés</h3>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 0;border-bottom:1px solid #e5e7eb;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="60">
                                                    <img src="https://via.placeholder.com/50x50/e5e7eb/9ca3af?text=P1" alt="Product" style="width:50px;height:50px;border-radius:6px;">
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">Produit 1</p>
                                                    <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">x1</p>
                                                </td>
                                                <td style="text-align:right;">
                                                    <p style="margin:0;font-size:14px;font-weight:600;color:#111827;">49,99 €</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Total -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="padding:8px 0;"><p style="margin:0;font-size:14px;color:#6b7280;">Sous-total</p></td>
                                    <td style="text-align:right;"><p style="margin:0;font-size:14px;color:#111827;">49,99 €</p></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;"><p style="margin:0;font-size:14px;color:#6b7280;">Livraison</p></td>
                                    <td style="text-align:right;"><p style="margin:0;font-size:14px;color:#111827;">5,00 €</p></td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-top:2px solid #111827;"><p style="margin:0;font-size:16px;font-weight:700;color:#111827;">Total</p></td>
                                    <td style="text-align:right;border-top:2px solid #111827;"><p style="margin:0;font-size:18px;font-weight:700;color:#111827;">54,99 €</p></td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="background:#111827;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Suivre ma commande</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">Des questions ? Contactez-nous à support@example.com</p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Simple Notification Template
     */
    protected function getSimpleNotification(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px 40px;border-bottom:1px solid #e5e7eb;">
                            <img src="https://via.placeholder.com/120x40/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:120px;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <div style="width:48px;height:48px;background:#eef2ff;border-radius:12px;text-align:center;line-height:48px;margin-bottom:20px;">
                                <span style="font-size:24px;">🔔</span>
                            </div>
                            <h1 style="margin:0 0 16px;font-size:22px;font-weight:600;color:#111827;">Nouvelle notification</h1>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#4b5563;">Vous avez reçu une nouvelle notification. Cliquez sur le bouton ci-dessous pour en savoir plus.</p>

                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#6366f1;border-radius:8px;">
                                        <a href="#" style="display:inline-block;padding:12px 24px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">Voir les détails</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© {{current_year}} {{company_name}} · <a href="{{unsubscribe_url}}" style="color:#9ca3af;">Se désinscrire</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
