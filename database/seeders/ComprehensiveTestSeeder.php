<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailEvent;
use App\Models\EmailTemplate;
use App\Models\SentEmail;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveTestSeeder extends Seeder
{
    protected Tenant $tenant;
    protected User $user;
    protected array $lists = [];
    protected array $tags = [];
    protected array $templates = [];
    protected array $contacts = [];
    protected array $campaigns = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive Test Seeder...');

        DB::beginTransaction();

        try {
            // Step 1: Create or get user and tenant
            $this->setupUserAndTenant();

            // Step 2: Create 10 tags
            $this->createTags();

            // Step 3: Create 5 lists
            $this->createLists();

            // Step 4: Create 245 contacts distributed across lists and tags
            $this->createContacts();

            // Step 5: Create 3 email templates (2 predefined + 1 manual)
            $this->createTemplates();

            // Step 6: Create 3 campaigns
            $this->createCampaigns();

            // Step 7: Simulate email sending and events
            $this->simulateEmailEvents();

            DB::commit();

            $this->command->info('✅ Comprehensive Test Seeder completed successfully!');
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Setup user and tenant.
     */
    protected function setupUserAndTenant(): void
    {
        $this->command->info('👤 Setting up user and tenant...');

        // Check if user exists
        $this->user = User::where('email', 'ousweb51@gmail.com')->first();

        if (!$this->user) {
            // Create tenant first
            $this->tenant = Tenant::create([
                'name' => 'Test Company',
                'slug' => 'test-company-' . Str::random(6),
                'company_name' => 'Test Company SARL',
                'email' => 'ousweb51@gmail.com',
                'phone' => '+226 70 00 00 00',
                'address' => 'Ouagadougou, Burkina Faso',
                'status' => Tenant::STATUS_ACTIVE,
                'default_from_email' => 'noreply@testcompany.com',
                'default_from_name' => 'Test Company',
                'trial_ends_at' => now()->addDays(30),
            ]);

            // Create user
            $this->user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Test User',
                'email' => 'ousweb51@gmail.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_OWNER,
                'email_verified_at' => now(),
            ]);

            $this->command->info("   Created new user: {$this->user->email}");
        } else {
            $this->tenant = $this->user->tenant;
            $this->command->info("   Using existing user: {$this->user->email}");
        }
    }

    /**
     * Create 10 tags.
     */
    protected function createTags(): void
    {
        $this->command->info('🏷️  Creating 10 tags...');

        $tagNames = [
            'VIP' => '#FFD700',
            'Newsletter' => '#3B82F6',
            'Prospect' => '#10B981',
            'Client' => '#8B5CF6',
            'Inactif' => '#6B7280',
            'Premium' => '#F59E0B',
            'Entreprise' => '#EF4444',
            'Particulier' => '#06B6D4',
            'Partenaire' => '#EC4899',
            'Lead chaud' => '#F97316',
        ];

        foreach ($tagNames as $name => $color) {
            $tag = Tag::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $name],
                ['color' => $color]
            );
            $this->tags[] = $tag;
        }

        $this->command->info("   Created " . count($this->tags) . " tags");
    }

    /**
     * Create 5 contact lists.
     */
    protected function createLists(): void
    {
        $this->command->info('📋 Creating 5 contact lists...');

        $listData = [
            ['name' => 'Newsletter Générale', 'description' => 'Abonnés à la newsletter mensuelle'],
            ['name' => 'Clients Actifs', 'description' => 'Clients ayant effectué un achat récemment'],
            ['name' => 'Prospects Qualifiés', 'description' => 'Prospects ayant montré un intérêt'],
            ['name' => 'VIP & Premium', 'description' => 'Clients premium et VIP'],
            ['name' => 'Partenaires & Entreprises', 'description' => 'Contacts B2B et partenaires'],
        ];

        foreach ($listData as $data) {
            $list = ContactList::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $data['name']],
                ['description' => $data['description']]
            );
            $this->lists[] = $list;
        }

        $this->command->info("   Created " . count($this->lists) . " lists");
    }

    /**
     * Create 245 contacts distributed across lists and tags.
     */
    protected function createContacts(): void
    {
        $this->command->info('👥 Creating 245 contacts...');

        $firstNames = ['Amadou', 'Fatou', 'Ibrahim', 'Mariam', 'Ousmane', 'Aissata', 'Moussa', 'Kadiatou', 'Seydou', 'Aminata',
                       'Bakary', 'Fatoumata', 'Mamadou', 'Rokia', 'Boubacar', 'Djénéba', 'Abdoulaye', 'Safiatou', 'Drissa', 'Oumou',
                       'Jean', 'Marie', 'Pierre', 'Sophie', 'Paul', 'Claire', 'François', 'Isabelle', 'Michel', 'Nathalie'];
        $lastNames = ['Traoré', 'Coulibaly', 'Diallo', 'Koné', 'Sanogo', 'Keita', 'Touré', 'Cissé', 'Bamba', 'Ouattara',
                      'Diarra', 'Konaté', 'Sidibé', 'Sylla', 'Camara', 'Barry', 'Sow', 'Ba', 'Ndiaye', 'Fall'];
        $domains = ['gmail.com', 'yahoo.fr', 'outlook.com', 'hotmail.com', 'entreprise.bf', 'societe.ml', 'company.ci'];
        $companies = ['Tech Solutions', 'Agro Plus', 'Commerce Express', 'Services Pro', 'Digital Agency', 'Construction SA', null];

        // Distribution: 60 contacts in list 1, 55 in list 2, 50 in list 3, 45 in list 4, 35 in list 5
        $listDistribution = [60, 55, 50, 45, 35];
        $contactIndex = 0;

        foreach ($this->lists as $listIndex => $list) {
            $count = $listDistribution[$listIndex];

            for ($i = 0; $i < $count; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $domain = $domains[array_rand($domains)];
                $email = strtolower(Str::slug($firstName) . '.' . Str::slug($lastName) . $contactIndex . '@' . $domain);

                $contact = Contact::firstOrCreate(
                    ['tenant_id' => $this->tenant->id, 'email' => $email],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => '+226 7' . rand(0, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                        'company' => $companies[array_rand($companies)],
                        'status' => Contact::STATUS_SUBSCRIBED,
                        'custom_fields' => [
                            'source' => ['website', 'referral', 'event', 'social'][array_rand(['website', 'referral', 'event', 'social'])],
                            'city' => ['Ouagadougou', 'Bobo-Dioulasso', 'Bamako', 'Abidjan', 'Dakar'][array_rand(['Ouagadougou', 'Bobo-Dioulasso', 'Bamako', 'Abidjan', 'Dakar'])],
                        ],
                        'subscribed_at' => now()->subDays(rand(1, 365)),
                    ]
                );

                // Add to list
                if (!$list->contacts()->where('contact_id', $contact->id)->exists()) {
                    $list->contacts()->attach($contact->id);
                }

                // Add 1-3 random tags
                $randomTags = collect($this->tags)->random(rand(1, 3))->pluck('id');
                $contact->tags()->syncWithoutDetaching($randomTags);

                $this->contacts[] = $contact;
                $contactIndex++;
            }
        }

        $this->command->info("   Created " . count($this->contacts) . " contacts");
    }

    /**
     * Create 3 email templates.
     */
    protected function createTemplates(): void
    {
        $this->command->info('📝 Creating 3 email templates...');

        // Template 1: Newsletter
        $this->templates[] = EmailTemplate::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Newsletter Mensuelle'],
            [
                'created_by' => $this->user->id,
                'default_subject' => 'Notre newsletter du mois de {{month}}',
                'html_content' => $this->getNewsletterTemplate(),
                'text_content' => 'Newsletter du mois. Visitez notre site pour plus d\'informations.',
                'category' => 'newsletter',
                'is_active' => true,
            ]
        );

        // Template 2: Promotional
        $this->templates[] = EmailTemplate::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Offre Promotionnelle'],
            [
                'created_by' => $this->user->id,
                'default_subject' => '🎉 Offre exclusive pour vous, {{first_name}}!',
                'html_content' => $this->getPromotionalTemplate(),
                'text_content' => 'Offre exclusive! Profitez de nos promotions exceptionnelles.',
                'category' => 'promotional',
                'is_active' => true,
            ]
        );

        // Template 3: Manual/Custom
        $this->templates[] = EmailTemplate::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Invitation Événement'],
            [
                'created_by' => $this->user->id,
                'default_subject' => 'Vous êtes invité(e) à notre événement spécial',
                'html_content' => $this->getEventTemplate(),
                'text_content' => 'Vous êtes invité à notre événement. Réservez votre place dès maintenant!',
                'category' => 'event',
                'is_active' => true,
            ]
        );

        $this->command->info("   Created " . count($this->templates) . " templates");
    }

    /**
     * Create 3 campaigns.
     */
    protected function createCampaigns(): void
    {
        $this->command->info('📧 Creating 3 campaigns...');

        // Campaign 1: Newsletter to lists 1, 2 (115 contacts) using Template 1
        $campaign1 = Campaign::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Newsletter Janvier 2026'],
            [
                'created_by' => $this->user->id,
                'subject' => 'Notre newsletter de Janvier 2026',
                'from_email' => $this->tenant->default_from_email ?? 'noreply@slimail.local',
                'from_name' => $this->tenant->default_from_name ?? 'SliMail',
                'html_content' => str_replace('{{month}}', 'Janvier', $this->templates[0]->html_content),
                'text_content' => $this->templates[0]->text_content,
                'status' => Campaign::STATUS_SENT,
                'type' => Campaign::TYPE_REGULAR,
                'started_at' => now()->subDays(7),
                'completed_at' => now()->subDays(7)->addHours(2),
                'scheduled_at' => now()->subDays(7),
                'list_ids' => [$this->lists[0]->id, $this->lists[1]->id],
            ]
        );
        $this->campaigns[] = $campaign1;

        // Campaign 2: Promotional to lists 2, 3, 4 (150 contacts but we'll use 70) using Template 2
        $campaign2 = Campaign::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Promo Soldes Hiver'],
            [
                'created_by' => $this->user->id,
                'subject' => '🎉 Soldes exceptionnelles - Jusqu\'à -50%!',
                'from_email' => $this->tenant->default_from_email ?? 'noreply@slimail.local',
                'from_name' => $this->tenant->default_from_name ?? 'SliMail',
                'html_content' => $this->templates[1]->html_content,
                'text_content' => $this->templates[1]->text_content,
                'status' => Campaign::STATUS_SENT,
                'type' => Campaign::TYPE_REGULAR,
                'started_at' => now()->subDays(3),
                'completed_at' => now()->subDays(3)->addHours(1),
                'scheduled_at' => now()->subDays(3),
                'list_ids' => [$this->lists[1]->id, $this->lists[2]->id, $this->lists[3]->id],
            ]
        );
        $this->campaigns[] = $campaign2;

        // Campaign 3: Event invitation to lists 4, 5 (80 contacts but we'll use 30) using Template 3
        $campaign3 = Campaign::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Invitation Conférence Tech'],
            [
                'created_by' => $this->user->id,
                'subject' => 'Invitation: Conférence Tech 2026 - Places limitées!',
                'from_email' => $this->tenant->default_from_email ?? 'noreply@slimail.local',
                'from_name' => $this->tenant->default_from_name ?? 'SliMail',
                'html_content' => $this->templates[2]->html_content,
                'text_content' => $this->templates[2]->text_content,
                'status' => Campaign::STATUS_SENT,
                'type' => Campaign::TYPE_REGULAR,
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1)->addMinutes(30),
                'scheduled_at' => now()->subDays(1),
                'list_ids' => [$this->lists[3]->id, $this->lists[4]->id],
            ]
        );
        $this->campaigns[] = $campaign3;

        $this->command->info("   Created " . count($this->campaigns) . " campaigns");
    }

    /**
     * Simulate email sending and events.
     */
    protected function simulateEmailEvents(): void
    {
        $this->command->info('📨 Simulating email events...');

        // Campaign 1: 115 contacts (lists 1+2: 60+55)
        // 100% sent, 95% delivered, 45% opened, 15% clicked, 2% bounced, 1% unsubscribed
        $this->simulateCampaignEvents($this->campaigns[0], 115, [
            'sent' => 100,
            'delivered' => 95,
            'opened' => 45,
            'clicked' => 15,
            'bounced' => 2,
            'unsubscribed' => 1,
        ], now()->subDays(7));

        // Campaign 2: 70 contacts (subset of lists 2+3+4)
        // 100% sent, 92% delivered, 38% opened, 12% clicked, 3% bounced, 2% complained
        $this->simulateCampaignEvents($this->campaigns[1], 70, [
            'sent' => 100,
            'delivered' => 92,
            'opened' => 38,
            'clicked' => 12,
            'bounced' => 3,
            'complained' => 2,
        ], now()->subDays(3));

        // Campaign 3: 30 contacts (subset of lists 4+5)
        // 100% sent, 97% delivered, 55% opened, 25% clicked, 1% bounced
        $this->simulateCampaignEvents($this->campaigns[2], 30, [
            'sent' => 100,
            'delivered' => 97,
            'opened' => 55,
            'clicked' => 25,
            'bounced' => 1,
        ], now()->subDays(1));

        $this->command->info('   Email events simulated successfully');
    }

    /**
     * Simulate events for a campaign.
     */
    protected function simulateCampaignEvents(Campaign $campaign, int $totalContacts, array $percentages, Carbon $sentAt): void
    {
        // Get contacts from campaign lists
        $contactIds = [];
        $listIds = $campaign->list_ids ?? [];
        foreach ($listIds as $listId) {
            $list = ContactList::find($listId);
            if ($list) {
                $contactIds = array_merge($contactIds, $list->contacts()->pluck('contacts.id')->toArray());
            }
        }
        $contactIds = array_unique($contactIds);
        $contactIds = array_slice($contactIds, 0, $totalContacts);

        $counts = [
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'complained' => 0,
            'unsubscribed' => 0,
        ];

        $devices = ['desktop', 'mobile', 'tablet'];
        $userAgents = [
            'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            'tablet' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ];

        foreach ($contactIds as $index => $contactId) {
            $contact = Contact::find($contactId);
            if (!$contact) continue;

            // Create sent email
            $sentEmail = SentEmail::create([
                'tenant_id' => $this->tenant->id,
                'contact_id' => $contactId,
                'campaign_id' => $campaign->id,
                'message_id' => 'mock-' . Str::random(32),
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'to_email' => $contact->email,
                'to_name' => $contact->full_name,
                'subject' => $campaign->subject,
                'type' => SentEmail::TYPE_CAMPAIGN,
                'status' => SentEmail::STATUS_SENT,
                'sent_at' => $sentAt->copy()->addMinutes(rand(0, 60)),
            ]);

            $counts['sent']++;

            // Determine what happens to this email based on percentages
            $rand = rand(1, 100);
            $device = $devices[array_rand($devices)];
            $eventTime = $sentAt->copy();

            // Bounced?
            if ($rand <= ($percentages['bounced'] ?? 0)) {
                $sentEmail->update([
                    'status' => SentEmail::STATUS_BOUNCED,
                    'bounced_at' => $eventTime->addMinutes(rand(1, 5)),
                    'bounce_type' => 'Permanent',
                    'bounce_subtype' => 'General',
                ]);
                $counts['bounced']++;

                // Record bounce event
                EmailEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'sent_email_id' => $sentEmail->id,
                    'event_type' => EmailEvent::TYPE_BOUNCE,
                    'event_at' => $sentEmail->bounced_at,
                    'bounce_type' => 'Permanent',
                    'bounce_subtype' => 'General',
                ]);
                continue;
            }

            // Complained?
            if ($rand <= ($percentages['complained'] ?? 0) + ($percentages['bounced'] ?? 0)) {
                $sentEmail->update([
                    'status' => SentEmail::STATUS_COMPLAINED,
                    'delivered_at' => $eventTime->addMinutes(rand(1, 5)),
                    'complained_at' => $eventTime->addHours(rand(1, 24)),
                ]);
                $counts['delivered']++;
                $counts['complained']++;

                EmailEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'sent_email_id' => $sentEmail->id,
                    'event_type' => EmailEvent::TYPE_COMPLAINT,
                    'event_at' => $sentEmail->complained_at,
                ]);
                continue;
            }

            // Delivered
            if ($rand <= ($percentages['delivered'] ?? 0)) {
                $deliveredAt = $eventTime->addMinutes(rand(1, 10));
                $sentEmail->update([
                    'status' => SentEmail::STATUS_DELIVERED,
                    'delivered_at' => $deliveredAt,
                ]);
                $counts['delivered']++;

                EmailEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'sent_email_id' => $sentEmail->id,
                    'event_type' => EmailEvent::TYPE_DELIVERY,
                    'event_at' => $deliveredAt,
                ]);

                // Opened?
                $openRand = rand(1, 100);
                $openRate = ($percentages['opened'] ?? 0) / ($percentages['delivered'] ?? 1) * 100;

                if ($openRand <= $openRate) {
                    $openedAt = $deliveredAt->copy()->addMinutes(rand(5, 1440));
                    $sentEmail->update([
                        'status' => SentEmail::STATUS_OPENED,
                        'opened_at' => $openedAt,
                        'opens_count' => rand(1, 3),
                    ]);
                    $counts['opened']++;

                    EmailEvent::create([
                        'tenant_id' => $this->tenant->id,
                        'sent_email_id' => $sentEmail->id,
                        'event_type' => EmailEvent::TYPE_OPEN,
                        'event_at' => $openedAt,
                        'device_type' => $device,
                        'user_agent' => $userAgents[$device],
                        'ip_address' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    ]);

                    // Clicked?
                    $clickRand = rand(1, 100);
                    $clickRate = ($percentages['clicked'] ?? 0) / ($percentages['opened'] ?? 1) * 100;

                    if ($clickRand <= $clickRate) {
                        $clickedAt = $openedAt->copy()->addMinutes(rand(1, 30));
                        $sentEmail->update([
                            'status' => SentEmail::STATUS_CLICKED,
                            'clicked_at' => $clickedAt,
                            'clicks_count' => rand(1, 5),
                        ]);
                        $counts['clicked']++;

                        EmailEvent::create([
                            'tenant_id' => $this->tenant->id,
                            'sent_email_id' => $sentEmail->id,
                            'event_type' => EmailEvent::TYPE_CLICK,
                            'event_at' => $clickedAt,
                            'device_type' => $device,
                            'user_agent' => $userAgents[$device],
                            'ip_address' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                            'link_url' => 'https://example.com/promo?utm_campaign=' . $campaign->id,
                        ]);
                    }

                    // Unsubscribed?
                    $unsubRand = rand(1, 100);
                    if ($unsubRand <= ($percentages['unsubscribed'] ?? 0)) {
                        $counts['unsubscribed']++;
                        // Update contact status
                        $contact->update(['status' => Contact::STATUS_UNSUBSCRIBED]);
                    }
                }
            }
        }

        // Update campaign stats
        $campaign->update([
            'sent_count' => $counts['sent'],
            'delivered_count' => $counts['delivered'],
            'opened_count' => $counts['opened'],
            'clicked_count' => $counts['clicked'],
            'bounced_count' => $counts['bounced'],
            'complained_count' => $counts['complained'],
            'unsubscribed_count' => $counts['unsubscribed'],
        ]);

        $this->command->info("   Campaign '{$campaign->name}': Sent={$counts['sent']}, Delivered={$counts['delivered']}, Opened={$counts['opened']}, Clicked={$counts['clicked']}");
    }

    /**
     * Print summary of created data.
     */
    protected function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 SUMMARY');
        $this->command->info('══════════════════════════════════════════');
        $this->command->info("User: {$this->user->email}");
        $this->command->info("Tenant: {$this->tenant->name}");
        $this->command->info("Tags: " . count($this->tags));
        $this->command->info("Lists: " . count($this->lists));
        $this->command->info("Contacts: " . count($this->contacts));
        $this->command->info("Templates: " . count($this->templates));
        $this->command->info("Campaigns: " . count($this->campaigns));

        $totalSent = array_sum(array_map(fn($c) => $c->sent_count, $this->campaigns));
        $this->command->info("Total Emails Sent: {$totalSent}");

        $this->command->newLine();
        $this->command->info('🔐 Login credentials:');
        $this->command->info("   Email: ousweb51@gmail.com");
        $this->command->info("   Password: password123");
        $this->command->info('══════════════════════════════════════════');
    }

    /**
     * Get newsletter template HTML.
     */
    protected function getNewsletterTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">📬 Newsletter {{month}}</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour {{first_name}},</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Voici les dernières nouvelles de notre entreprise pour ce mois.</p>

                            <h2 style="color: #dc2626; font-size: 20px; margin-top: 30px;">🎯 Nos actualités</h2>
                            <ul style="color: #333333; line-height: 1.8;">
                                <li>Lancement de notre nouvelle plateforme</li>
                                <li>Nouveaux partenariats stratégiques</li>
                                <li>Événements à venir</li>
                            </ul>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="https://example.com/newsletter" style="background-color: #dc2626; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">En savoir plus</a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #666666;">
                            <p>© 2026 SliMail. Tous droits réservés.</p>
                            <p><a href="{{unsubscribe_url}}" style="color: #dc2626;">Se désabonner</a></p>
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
     * Get promotional template HTML.
     */
    protected function getPromotionalTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a2e; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #16213e; border-radius: 12px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px; text-align: center;">
                            <h1 style="color: #ffd700; margin: 0; font-size: 42px;">🎉 SOLDES</h1>
                            <p style="color: #ffffff; font-size: 24px; margin-top: 10px;">Jusqu'à <span style="color: #ff6b6b; font-weight: bold;">-50%</span></p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px; background-color: #0f3460;">
                            <p style="font-size: 18px; color: #ffffff; line-height: 1.6; text-align: center;">
                                Cher(e) {{first_name}},<br><br>
                                Profitez de nos offres exceptionnelles pendant une durée limitée !
                            </p>

                            <table width="100%" cellpadding="10" style="margin: 30px 0;">
                                <tr>
                                    <td style="background-color: #1a1a2e; border-radius: 8px; text-align: center; padding: 20px;">
                                        <p style="color: #ffd700; font-size: 32px; margin: 0; font-weight: bold;">-30%</p>
                                        <p style="color: #ffffff; margin: 5px 0;">Sur tout le site</p>
                                    </td>
                                    <td style="background-color: #1a1a2e; border-radius: 8px; text-align: center; padding: 20px;">
                                        <p style="color: #ff6b6b; font-size: 32px; margin: 0; font-weight: bold;">-50%</p>
                                        <p style="color: #ffffff; margin: 5px 0;">Articles sélectionnés</p>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="https://example.com/promo" style="background: linear-gradient(135deg, #ffd700 0%, #ff6b6b 100%); color: #1a1a2e; padding: 16px 40px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px; display: inline-block;">PROFITER MAINTENANT</a>
                            </div>

                            <p style="color: #888888; font-size: 12px; text-align: center;">Offre valable jusqu'au 31 janvier 2026</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px; text-align: center; font-size: 12px; color: #666666;">
                            <p><a href="{{unsubscribe_url}}" style="color: #888888;">Se désabonner</a></p>
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
     * Get event template HTML.
     */
    protected function getEventTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <!-- Header with image -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 30px; text-align: center;">
                            <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin: 0; text-transform: uppercase; letter-spacing: 2px;">Vous êtes invité(e)</p>
                            <h1 style="color: #ffffff; margin: 15px 0 0 0; font-size: 32px;">Conférence Tech 2026</h1>
                        </td>
                    </tr>
                    <!-- Event details -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Bonjour {{first_name}},</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Nous avons le plaisir de vous inviter à notre conférence annuelle sur les innovations technologiques.</p>

                            <table width="100%" style="margin: 30px 0; background-color: #f8f9fa; border-radius: 12px; padding: 20px;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="margin: 0; color: #667eea; font-weight: bold;">📅 Date</p>
                                        <p style="margin: 5px 0 0 0; color: #333;">15 Février 2026</p>
                                    </td>
                                    <td style="padding: 15px;">
                                        <p style="margin: 0; color: #667eea; font-weight: bold;">🕐 Heure</p>
                                        <p style="margin: 5px 0 0 0; color: #333;">09:00 - 17:00</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 15px;">
                                        <p style="margin: 0; color: #667eea; font-weight: bold;">📍 Lieu</p>
                                        <p style="margin: 5px 0 0 0; color: #333;">Centre des Congrès, Ouagadougou</p>
                                    </td>
                                </tr>
                            </table>

                            <h3 style="color: #333333; margin-top: 30px;">Au programme :</h3>
                            <ul style="color: #555555; line-height: 2;">
                                <li>Intelligence Artificielle et Business</li>
                                <li>Cybersécurité : Enjeux 2026</li>
                                <li>Cloud Computing avancé</li>
                                <li>Networking & Cocktail</li>
                            </ul>

                            <div style="text-align: center; margin: 40px 0;">
                                <a href="https://example.com/event-register" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;">Confirmer ma présence</a>
                            </div>

                            <p style="color: #888888; font-size: 14px; text-align: center;">Places limitées - Inscription gratuite</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666666;">
                            <p>© 2026 SliMail. Tous droits réservés.</p>
                            <p><a href="{{unsubscribe_url}}" style="color: #667eea;">Se désabonner</a></p>
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
