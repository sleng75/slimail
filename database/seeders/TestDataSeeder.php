<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Segment;
use App\Models\SentEmail;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer le tenant et l'utilisateur
        $user = User::where('email', 'slmty109@gmail.com')->first();

        if (!$user) {
            $this->command->error('Utilisateur slmty109@gmail.com non trouvé. Exécutez UserSeeder d\'abord.');
            return;
        }

        $tenant = $user->tenant;
        $tenantId = $tenant->id;

        $this->command->info('Création des données de test pour ' . $user->email);

        // 1. Créer des Tags
        $this->command->info('Création des tags...');
        $tags = $this->createTags($tenantId);

        // 2. Créer des Listes de contacts
        $this->command->info('Création des listes de contacts...');
        $lists = $this->createContactLists($tenantId);

        // 3. Créer des Contacts
        $this->command->info('Création des contacts...');
        $contacts = $this->createContacts($tenantId, $lists, $tags);

        // 4. Créer des Segments
        $this->command->info('Création des segments...');
        $this->createSegments($tenantId);

        // 5. Créer des Templates
        $this->command->info('Création des templates...');
        $templates = $this->createTemplates($tenantId, $user->id);

        // 6. Créer des Campagnes
        $this->command->info('Création des campagnes...');
        $campaigns = $this->createCampaigns($tenantId, $user->id, $lists, $templates);

        // 7. Créer des emails envoyés et événements
        $this->command->info('Création des statistiques d\'emails...');
        $this->createEmailStats($campaigns, $contacts);

        // 8. Créer des clés API
        $this->command->info('Création des clés API...');
        $this->createApiKeys($tenantId, $user->id);

        // 9. Créer des factures et paiements
        $this->command->info('Création des factures...');
        $this->createInvoicesAndPayments($tenant);

        // 10. Créer des notifications
        $this->command->info('Création des notifications...');
        $this->createNotifications($user);

        $this->command->info('Données de test créées avec succès!');
    }

    private function createTags(int $tenantId): array
    {
        $tagsData = [
            ['name' => 'VIP', 'color' => '#FFD700'],
            ['name' => 'Newsletter', 'color' => '#3B82F6'],
            ['name' => 'Client actif', 'color' => '#10B981'],
            ['name' => 'Prospect', 'color' => '#F59E0B'],
            ['name' => 'Inactif', 'color' => '#EF4444'],
            ['name' => 'E-commerce', 'color' => '#8B5CF6'],
            ['name' => 'B2B', 'color' => '#06B6D4'],
            ['name' => 'Partenaire', 'color' => '#EC4899'],
        ];

        $tags = [];
        foreach ($tagsData as $data) {
            $tags[] = Tag::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                ['color' => $data['color']]
            );
        }

        return $tags;
    }

    private function createContactLists(int $tenantId): array
    {
        $listsData = [
            ['name' => 'Newsletter principale', 'description' => 'Liste des abonnés à la newsletter mensuelle'],
            ['name' => 'Clients Premium', 'description' => 'Clients avec abonnement premium'],
            ['name' => 'Prospects chauds', 'description' => 'Prospects ayant montré un intérêt récent'],
            ['name' => 'Webinar Janvier 2026', 'description' => 'Participants au webinar de janvier'],
            ['name' => 'Black Friday 2025', 'description' => 'Contacts ciblés pour Black Friday'],
        ];

        $lists = [];
        foreach ($listsData as $data) {
            $lists[] = ContactList::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                ['description' => $data['description']]
            );
        }

        return $lists;
    }

    private function createContacts(int $tenantId, array $lists, array $tags): array
    {
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Emma', 'Paul', 'Julie', 'Marc', 'Claire', 'Antoine', 'Léa', 'Thomas', 'Camille', 'Nicolas', 'Sarah', 'Maxime', 'Laura', 'Alexandre', 'Manon'];
        $lastNames = ['Dupont', 'Martin', 'Bernard', 'Durand', 'Petit', 'Robert', 'Richard', 'Moreau', 'Simon', 'Laurent', 'Michel', 'Garcia', 'Thomas', 'Leroy', 'Roux', 'David', 'Bertrand', 'Morel', 'Fournier', 'Girard'];
        $domains = ['gmail.com', 'yahoo.fr', 'outlook.com', 'hotmail.fr', 'orange.fr', 'free.fr', 'entreprise.ci', 'societe.com'];
        $companies = ['Tech Solutions', 'Digital Agency', 'Commerce Plus', 'Services Pro', 'Innovation Labs', 'Global Trade', 'Smart Business', 'Future Corp', null, null];
        $countries = ['Côte d\'Ivoire', 'France', 'Sénégal', 'Mali', 'Burkina Faso', 'Cameroun', 'Belgique', 'Canada'];
        $cities = ['Abidjan', 'Paris', 'Dakar', 'Bamako', 'Ouagadougou', 'Douala', 'Bruxelles', 'Montréal'];

        $contacts = [];

        for ($i = 0; $i < 150; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $domain = $domains[array_rand($domains)];
            $email = strtolower(Str::slug($firstName) . '.' . Str::slug($lastName) . $i . '@' . $domain);

            $status = $this->weightedRandom(['subscribed' => 70, 'unsubscribed' => 10, 'bounced' => 5, 'complained' => 2]);
            $countryIndex = array_rand($countries);

            $contact = Contact::firstOrCreate(
                ['tenant_id' => $tenantId, 'email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => '+225 ' . rand(1, 9) . rand(0, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'company' => $companies[array_rand($companies)],
                    'status' => $status,
                    'source' => $this->weightedRandom(['import' => 30, 'form' => 40, 'api' => 20, 'manual' => 10]),
                    'custom_fields' => [
                        'country' => $countries[$countryIndex],
                        'city' => $cities[$countryIndex],
                        'age' => rand(25, 60),
                        'interests' => $this->randomInterests(),
                    ],
                    'subscribed_at' => now()->subDays(rand(1, 365)),
                    'created_at' => now()->subDays(rand(1, 400)),
                ]
            );

            // Ajouter à des listes aléatoires
            $numLists = rand(1, 3);
            $randomLists = array_rand($lists, min($numLists, count($lists)));
            if (!is_array($randomLists)) $randomLists = [$randomLists];

            foreach ($randomLists as $listIndex) {
                $contact->lists()->syncWithoutDetaching([$lists[$listIndex]->id]);
            }

            // Ajouter des tags aléatoires
            $numTags = rand(0, 3);
            if ($numTags > 0) {
                $randomTags = array_rand($tags, min($numTags, count($tags)));
                if (!is_array($randomTags)) $randomTags = [$randomTags];

                $tagIds = array_map(fn($idx) => $tags[$idx]->id, $randomTags);
                $contact->tags()->syncWithoutDetaching($tagIds);
            }

            $contacts[] = $contact;
        }

        // Mettre à jour le count des listes
        foreach ($lists as $list) {
            $list->update(['contacts_count' => $list->contacts()->count()]);
        }

        return $contacts;
    }

    private function createSegments(int $tenantId): void
    {
        $segments = [
            [
                'name' => 'Clients VIP actifs',
                'description' => 'Clients VIP ayant ouvert un email dans les 30 derniers jours',
                'criteria' => [
                    ['field' => 'tags', 'operator' => 'contains', 'value' => 'VIP'],
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                ],
                'match_type' => 'all',
            ],
            [
                'name' => 'Nouveaux abonnés',
                'description' => 'Contacts inscrits dans les 7 derniers jours',
                'criteria' => [
                    ['field' => 'created_at', 'operator' => 'within_days', 'value' => 7],
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                ],
                'match_type' => 'all',
            ],
            [
                'name' => 'Contacts Côte d\'Ivoire',
                'description' => 'Tous les contacts en Côte d\'Ivoire',
                'criteria' => [
                    ['field' => 'custom_fields.country', 'operator' => 'equals', 'value' => 'Côte d\'Ivoire'],
                ],
                'match_type' => 'all',
            ],
        ];

        foreach ($segments as $data) {
            Segment::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'description' => $data['description'],
                    'criteria' => $data['criteria'],
                    'match_type' => $data['match_type'],
                    'cached_count' => rand(10, 50),
                ]
            );
        }
    }

    private function createTemplates(int $tenantId, int $userId): array
    {
        $templatesData = [
            [
                'name' => 'Newsletter Standard',
                'default_subject' => 'Les actualités de {{company_name}}',
                'category' => 'newsletter',
                'html_content' => $this->getNewsletterTemplate(),
            ],
            [
                'name' => 'Promotion Flash',
                'default_subject' => '🔥 -{{discount}}% sur toute la boutique !',
                'category' => 'promotional',
                'html_content' => $this->getPromoTemplate(),
            ],
            [
                'name' => 'Bienvenue',
                'default_subject' => 'Bienvenue chez {{company_name}}, {{first_name}} !',
                'category' => 'transactional',
                'html_content' => $this->getWelcomeTemplate(),
            ],
            [
                'name' => 'Invitation Webinar',
                'default_subject' => '📅 Invitation : {{webinar_title}}',
                'category' => 'event',
                'html_content' => $this->getWebinarTemplate(),
            ],
        ];

        $templates = [];
        foreach ($templatesData as $data) {
            $templates[] = EmailTemplate::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'created_by' => $userId,
                    'default_subject' => $data['default_subject'],
                    'category' => $data['category'],
                    'html_content' => $data['html_content'],
                    'text_content' => strip_tags($data['html_content']),
                    'is_active' => true,
                ]
            );
        }

        return $templates;
    }

    private function createCampaigns(int $tenantId, int $userId, array $lists, array $templates): array
    {
        $campaignsData = [
            [
                'name' => 'Newsletter Janvier 2026',
                'subject' => 'Les actualités de janvier - SliMail',
                'status' => 'sent',
                'type' => 'regular',
                'completed_at' => now()->subDays(20),
            ],
            [
                'name' => 'Promo Nouvel An',
                'subject' => '🎉 -30% pour bien commencer l\'année !',
                'status' => 'sent',
                'type' => 'regular',
                'completed_at' => now()->subDays(25),
            ],
            [
                'name' => 'Webinar IA & Marketing',
                'subject' => '📅 Webinar gratuit : L\'IA au service du marketing',
                'status' => 'sent',
                'type' => 'regular',
                'completed_at' => now()->subDays(10),
            ],
            [
                'name' => 'Test A/B - Objet email',
                'subject' => 'Découvrez nos nouveautés',
                'status' => 'sent',
                'type' => 'ab_test',
                'completed_at' => now()->subDays(5),
            ],
            [
                'name' => 'Newsletter Février 2026',
                'subject' => 'Février : nouvelles fonctionnalités SliMail',
                'status' => 'scheduled',
                'type' => 'regular',
                'scheduled_at' => now()->addDays(5),
            ],
            [
                'name' => 'Campagne Saint-Valentin',
                'subject' => '💕 Offres spéciales Saint-Valentin',
                'status' => 'draft',
                'type' => 'regular',
            ],
        ];

        $campaigns = [];
        foreach ($campaignsData as $index => $data) {
            $template = $templates[$index % count($templates)];
            $list = $lists[$index % count($lists)];

            $campaign = Campaign::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'created_by' => $userId,
                    'subject' => $data['subject'],
                    'from_name' => 'SliMail',
                    'from_email' => 'noreply@slimail.com',
                    'reply_to' => 'support@slimail.com',
                    'status' => $data['status'],
                    'type' => $data['type'],
                    'html_content' => $template->html_content,
                    'text_content' => $template->text_content,
                    'template_id' => $template->id,
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'started_at' => isset($data['completed_at']) ? $data['completed_at']->copy()->subMinutes(30) : null,
                    'completed_at' => $data['completed_at'] ?? null,
                    'recipients_count' => rand(50, 150),
                    'sent_count' => isset($data['completed_at']) ? rand(50, 100) : 0,
                    'delivered_count' => isset($data['completed_at']) ? rand(45, 95) : 0,
                    'opened_count' => isset($data['completed_at']) ? rand(20, 50) : 0,
                    'clicked_count' => isset($data['completed_at']) ? rand(10, 30) : 0,
                ]
            );

            // Associer la liste via list_ids JSON
            $campaign->update(['list_ids' => [$list->id]]);

            // Si c'est un test A/B, configurer les variantes
            if ($data['type'] === 'ab_test') {
                $campaign->update([
                    'ab_test_config' => [
                        'test_type' => 'subject',
                        'variants' => [
                            ['id' => 'A', 'subject' => 'Découvrez nos nouveautés', 'percentage' => 50],
                            ['id' => 'B', 'subject' => '🚀 Nouveautés exclusives à découvrir', 'percentage' => 50],
                        ],
                        'winner_criteria' => 'open_rate',
                        'test_duration_hours' => 4,
                    ],
                ]);
            }

            $campaigns[] = $campaign;
        }

        return $campaigns;
    }

    private function createEmailStats(array $campaigns, array $contacts): void
    {
        foreach ($campaigns as $campaign) {
            if (!in_array($campaign->status, ['sent', 'sending'])) {
                continue;
            }

            // Sélectionner un sous-ensemble de contacts
            $numRecipients = rand(50, min(100, count($contacts)));
            $recipients = array_slice($contacts, 0, $numRecipients);

            $totalSent = 0;
            $totalDelivered = 0;
            $totalOpened = 0;
            $totalClicked = 0;
            $totalBounced = 0;
            $totalComplaints = 0;

            $sentAt = $campaign->completed_at ?? now()->subDays(5);

            foreach ($recipients as $contact) {
                $status = $this->weightedRandom([
                    'delivered' => 85,
                    'bounced' => 8,
                    'failed' => 5,
                    'complained' => 2,
                ]);

                $messageId = 'msg_' . Str::random(24);
                $sentEmail = SentEmail::create([
                    'tenant_id' => $campaign->tenant_id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'message_id' => $messageId,
                    'from_email' => $campaign->from_email,
                    'from_name' => $campaign->from_name,
                    'to_email' => $contact->email,
                    'to_name' => $contact->first_name . ' ' . $contact->last_name,
                    'subject' => $campaign->subject,
                    'type' => 'campaign',
                    'status' => $status,
                    'sent_at' => $sentAt,
                    'delivered_at' => $status === 'delivered' ? $sentAt->copy()->addSeconds(rand(1, 60)) : null,
                ]);

                $totalSent++;

                if ($status === 'delivered') {
                    $totalDelivered++;

                    // Simuler des ouvertures (40% des délivrés)
                    if (rand(1, 100) <= 40) {
                        $totalOpened++;
                        $openedAt = $sentAt->copy()->addMinutes(rand(5, 1440));
                        $sentEmail->update([
                            'status' => 'opened',
                            'opened_at' => $openedAt,
                            'opens_count' => rand(1, 5),
                        ]);

                        // Créer événement d'ouverture
                        DB::table('email_events')->insert([
                            'tenant_id' => $campaign->tenant_id,
                            'sent_email_id' => $sentEmail->id,
                            'contact_id' => $contact->id,
                            'event_type' => 'open',
                            'message_id' => $messageId,
                            'event_at' => $openedAt,
                            'ip_address' => '192.168.1.' . rand(1, 255),
                            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                            'device_type' => $this->weightedRandom(['desktop' => 60, 'mobile' => 35, 'tablet' => 5]),
                            'client_name' => $this->weightedRandom(['Gmail' => 40, 'Outlook' => 30, 'Apple Mail' => 20, 'Yahoo' => 10]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Simuler des clics (60% des ouvertures)
                        if (rand(1, 100) <= 60) {
                            $totalClicked++;
                            $clickedAt = $openedAt->copy()->addMinutes(rand(1, 30));
                            $sentEmail->update([
                                'status' => 'clicked',
                                'clicked_at' => $clickedAt,
                                'clicks_count' => rand(1, 3),
                            ]);

                            // Créer événement de clic
                            DB::table('email_events')->insert([
                                'tenant_id' => $campaign->tenant_id,
                                'sent_email_id' => $sentEmail->id,
                                'contact_id' => $contact->id,
                                'event_type' => 'click',
                                'message_id' => $messageId,
                                'event_at' => $clickedAt,
                                'link_url' => 'https://slimail.com/promo',
                                'ip_address' => '192.168.1.' . rand(1, 255),
                                'user_agent' => 'Mozilla/5.0',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                } elseif ($status === 'bounced') {
                    $totalBounced++;
                    DB::table('email_events')->insert([
                        'tenant_id' => $campaign->tenant_id,
                        'sent_email_id' => $sentEmail->id,
                        'contact_id' => $contact->id,
                        'event_type' => 'bounce',
                        'message_id' => $messageId,
                        'event_at' => $sentAt->copy()->addSeconds(rand(1, 300)),
                        'bounce_type' => 'Permanent',
                        'bounce_subtype' => 'General',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($status === 'complained') {
                    $totalComplaints++;
                }
            }

            // Mettre à jour les statistiques de la campagne
            $campaign->update([
                'recipients_count' => $totalSent,
                'sent_count' => $totalSent,
                'delivered_count' => $totalDelivered,
                'opened_count' => $totalOpened,
                'clicked_count' => $totalClicked,
                'bounced_count' => $totalBounced,
                'complained_count' => $totalComplaints,
            ]);
        }
    }

    private function createApiKeys(int $tenantId, int $userId): void
    {
        $keys = [
            ['name' => 'Production API', 'permissions' => ['send', 'contacts.read', 'contacts.write', 'campaigns.read']],
            ['name' => 'Webhook Integration', 'permissions' => ['send', 'contacts.read']],
            ['name' => 'Analytics Dashboard', 'permissions' => ['statistics.read', 'campaigns.read']],
        ];

        foreach ($keys as $data) {
            $secret = Str::random(32);
            ApiKey::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'user_id' => $userId,
                    'key' => 'sk_live_' . Str::random(32),
                    'secret_hash' => hash('sha256', $secret),
                    'permissions' => $data['permissions'],
                    'is_active' => true,
                    'last_used_at' => now()->subDays(rand(0, 30)),
                    'requests_count' => rand(100, 10000),
                ]
            );
        }
    }

    private function createInvoicesAndPayments(Tenant $tenant): void
    {
        // Obtenir ou créer un plan
        $plan = Plan::first();
        if (!$plan) {
            $plan = Plan::create([
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Plan professionnel',
                'price_monthly' => 29900,
                'price_yearly' => 299000,
                'emails_per_month' => 50000,
                'contacts_limit' => 10000,
                'features' => ['templates', 'automation', 'analytics', 'api', 'support'],
                'is_active' => true,
                'sort_order' => 2,
            ]);
        }

        // Créer ou mettre à jour l'abonnement
        $subscription = Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'price' => $plan->price_monthly,
                'currency' => 'XOF',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'emails_used' => rand(5000, 20000),
            ]
        );

        // Créer des factures pour les 3 derniers mois
        $sequenceNumber = 100;
        for ($i = 3; $i >= 0; $i--) {
            $invoiceDate = now()->subMonths($i)->startOfMonth();
            $invoiceNumber = 'FA-' . $invoiceDate->format('Y') . '-' . str_pad($sequenceNumber, 6, '0', STR_PAD_LEFT);

            $status = $i > 0 ? 'paid' : 'pending';
            $paidAt = $status === 'paid' ? $invoiceDate->copy()->addDays(rand(1, 5)) : null;

            $subtotal = $plan->price_monthly;
            $taxRate = 18;
            $taxAmount = round($subtotal * $taxRate / 100);
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::firstOrCreate(
                ['tenant_id' => $tenant->id, 'number' => $invoiceNumber],
                [
                    'subscription_id' => $subscription->id,
                    'sequence_number' => $sequenceNumber,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'amount_paid' => $status === 'paid' ? $total : 0,
                    'balance_due' => $status === 'paid' ? 0 : $total,
                    'currency' => 'XOF',
                    'status' => $status,
                    'issue_date' => $invoiceDate,
                    'due_date' => $invoiceDate->copy()->addDays(15),
                    'paid_at' => $paidAt,
                    'billing_name' => $tenant->name,
                    'billing_email' => $tenant->email,
                    'line_items' => [
                        [
                            'description' => 'Abonnement ' . $plan->name . ' - ' . $invoiceDate->format('F Y'),
                            'quantity' => 1,
                            'unit_price' => $plan->price_monthly,
                            'total' => $plan->price_monthly,
                        ]
                    ],
                ]
            );

            $sequenceNumber++;

            // Créer un paiement pour les factures payées
            if ($status === 'paid') {
                Payment::firstOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'tenant_id' => $tenant->id,
                        'subscription_id' => $subscription->id,
                        'amount' => $total,
                        'currency' => 'XOF',
                        'status' => 'completed',
                        'payment_method' => $this->weightedRandom(['orange_money' => 50, 'mtn_money' => 30, 'wave' => 15, 'card' => 5]),
                        'transaction_id' => 'TXN' . strtoupper(Str::random(10)),
                        'cinetpay_transaction_id' => 'CP' . strtoupper(Str::random(12)),
                        'initiated_at' => $paidAt,
                        'completed_at' => $paidAt,
                        'metadata' => [
                            'phone' => '+225 07 XX XX XX XX',
                        ],
                    ]
                );
            }
        }
    }

    private function createNotifications(User $user): void
    {
        $notifications = [
            [
                'type' => 'App\\Notifications\\CampaignSentNotification',
                'data' => [
                    'title' => 'Campagne envoyée',
                    'message' => 'Votre campagne "Newsletter Janvier 2026" a été envoyée avec succès à 150 contacts.',
                    'action_url' => '/campaigns',
                    'action_text' => 'Voir les statistiques',
                ],
                'created_at' => now()->subDays(20),
            ],
            [
                'type' => 'App\\Notifications\\CampaignSentNotification',
                'data' => [
                    'title' => 'Campagne envoyée',
                    'message' => 'Votre campagne "Promo Nouvel An" a été envoyée avec succès à 120 contacts.',
                    'action_url' => '/campaigns',
                    'action_text' => 'Voir les statistiques',
                ],
                'created_at' => now()->subDays(25),
                'read_at' => now()->subDays(24),
            ],
            [
                'type' => 'App\\Notifications\\PaymentConfirmedNotification',
                'data' => [
                    'title' => 'Paiement confirmé',
                    'message' => 'Votre paiement de 35 282 FCFA a été confirmé. Merci !',
                    'action_url' => '/billing',
                    'action_text' => 'Voir la facture',
                ],
                'created_at' => now()->subDays(5),
                'read_at' => now()->subDays(4),
            ],
            [
                'type' => 'App\\Notifications\\ContactImportedNotification',
                'data' => [
                    'title' => 'Import terminé',
                    'message' => '150 contacts ont été importés avec succès dans la liste "Newsletter principale".',
                    'action_url' => '/contacts',
                    'action_text' => 'Voir les contacts',
                ],
                'created_at' => now()->subDays(15),
                'read_at' => now()->subDays(14),
            ],
            [
                'type' => 'App\\Notifications\\SubscriptionExpiringNotification',
                'data' => [
                    'title' => 'Abonnement à renouveler',
                    'message' => 'Votre abonnement expire dans 7 jours. Renouvelez-le pour continuer à profiter de SliMail.',
                    'action_url' => '/billing',
                    'action_text' => 'Renouveler',
                ],
                'created_at' => now()->subDays(2),
            ],
            [
                'type' => 'App\\Notifications\\NewFeatureNotification',
                'data' => [
                    'title' => 'Nouvelle fonctionnalité',
                    'message' => 'Les tests A/B sont maintenant disponibles ! Optimisez vos campagnes en testant différents objets.',
                    'action_url' => '/campaigns/create',
                    'action_text' => 'Essayer',
                ],
                'created_at' => now()->subDays(1),
            ],
        ];

        foreach ($notifications as $data) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => $data['type'],
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode($data['data']),
                'read_at' => $data['read_at'] ?? null,
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
        }
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $random = rand(1, $total);

        $cumulative = 0;
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    private function randomInterests(): array
    {
        $allInterests = ['Marketing', 'E-commerce', 'SaaS', 'Finance', 'Technologie', 'Santé', 'Éducation', 'Immobilier'];
        $numInterests = rand(1, 4);
        $keys = array_rand($allInterests, $numInterests);
        if (!is_array($keys)) $keys = [$keys];
        return array_map(fn($k) => $allInterests[$k], $keys);
    }

    private function getNewsletterTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #e53935;">{{company_name}}</h1>
    </div>
    <h2>Bonjour {{first_name}},</h2>
    <p>Voici les dernières actualités de notre entreprise.</p>
    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>📰 Article à la une</h3>
        <p>Découvrez nos dernières innovations et actualités.</p>
        <a href="{{article_url}}" style="background: #e53935; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Lire l'article</a>
    </div>
    <p style="color: #666; font-size: 12px; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
        Vous recevez cet email car vous êtes inscrit à notre newsletter.<br>
        <a href="{{unsubscribe_url}}">Se désabonner</a>
    </p>
</body>
</html>
HTML;
    }

    private function getPromoTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #e53935, #ff5722); color: white; padding: 40px; text-align: center; border-radius: 8px;">
        <h1 style="margin: 0; font-size: 48px;">-{{discount}}%</h1>
        <p style="font-size: 24px; margin: 10px 0;">OFFRE FLASH</p>
        <p>Valable jusqu'au {{end_date}}</p>
    </div>
    <div style="text-align: center; padding: 30px;">
        <p>Bonjour {{first_name}},</p>
        <p>Profitez de cette offre exceptionnelle sur toute notre boutique !</p>
        <a href="{{shop_url}}" style="background: #e53935; color: white; padding: 16px 32px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 18px;">J'en profite maintenant</a>
    </div>
    <p style="color: #666; font-size: 12px; text-align: center; margin-top: 40px;">
        <a href="{{unsubscribe_url}}">Se désabonner</a>
    </p>
</body>
</html>
HTML;
    }

    private function getWelcomeTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #e53935;">Bienvenue {{first_name}} ! 🎉</h1>
    </div>
    <p>Nous sommes ravis de vous compter parmi nous.</p>
    <p>Voici ce que vous pouvez faire avec {{company_name}} :</p>
    <ul>
        <li>✅ Créer des campagnes email professionnelles</li>
        <li>✅ Gérer vos contacts facilement</li>
        <li>✅ Suivre vos statistiques en temps réel</li>
        <li>✅ Automatiser vos communications</li>
    </ul>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{dashboard_url}}" style="background: #e53935; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; display: inline-block;">Accéder à mon espace</a>
    </div>
    <p>À très bientôt,<br>L'équipe {{company_name}}</p>
</body>
</html>
HTML;
    }

    private function getWebinarTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <span style="font-size: 48px;">📅</span>
        <h1>{{webinar_title}}</h1>
    </div>
    <p>Bonjour {{first_name}},</p>
    <p>Vous êtes invité(e) à notre prochain webinar :</p>
    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p><strong>📆 Date :</strong> {{webinar_date}}</p>
        <p><strong>⏰ Heure :</strong> {{webinar_time}}</p>
        <p><strong>⏱️ Durée :</strong> {{webinar_duration}}</p>
    </div>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{registration_url}}" style="background: #e53935; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; display: inline-block;">S'inscrire gratuitement</a>
    </div>
    <p style="color: #666; font-size: 12px; margin-top: 40px;">
        <a href="{{unsubscribe_url}}">Se désabonner</a>
    </p>
</body>
</html>
HTML;
    }
}
