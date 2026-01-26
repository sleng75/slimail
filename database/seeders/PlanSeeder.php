<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Gratuit',
                'slug' => 'gratuit',
                'description' => 'Parfait pour découvrir SliMail et envoyer vos premiers emails.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'XOF',
                'emails_per_month' => 500,
                'contacts_limit' => 250,
                'campaigns_per_month' => 5,
                'templates_limit' => 3,
                'users_limit' => 1,
                'api_requests_per_day' => 0,
                'features' => [
                    'email_editor' => true,
                    'custom_domain' => false,
                    'api_access' => false,
                    'automation' => false,
                    'ab_testing' => false,
                    'advanced_analytics' => false,
                    'priority_support' => false,
                    'dedicated_ip' => false,
                    'white_label' => false,
                    'custom_branding' => false,
                ],
                'sort_order' => 1,
                'is_popular' => false,
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 0,
                'color' => 'gray',
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Idéal pour les petites entreprises et les indépendants.',
                'price_monthly' => 9900,
                'price_yearly' => 99000,
                'currency' => 'XOF',
                'emails_per_month' => 5000,
                'contacts_limit' => 1000,
                'campaigns_per_month' => 20,
                'templates_limit' => 10,
                'users_limit' => 2,
                'api_requests_per_day' => 1000,
                'features' => [
                    'email_editor' => true,
                    'custom_domain' => false,
                    'api_access' => true,
                    'automation' => false,
                    'ab_testing' => false,
                    'advanced_analytics' => false,
                    'priority_support' => false,
                    'dedicated_ip' => false,
                    'white_label' => false,
                    'custom_branding' => false,
                ],
                'sort_order' => 2,
                'is_popular' => false,
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 14,
                'color' => 'blue',
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les équipes marketing qui veulent des fonctionnalités avancées.',
                'price_monthly' => 24900,
                'price_yearly' => 249000,
                'currency' => 'XOF',
                'emails_per_month' => 25000,
                'contacts_limit' => 5000,
                'campaigns_per_month' => 0, // Unlimited
                'templates_limit' => 0, // Unlimited
                'users_limit' => 5,
                'api_requests_per_day' => 10000,
                'features' => [
                    'email_editor' => true,
                    'custom_domain' => true,
                    'api_access' => true,
                    'automation' => true,
                    'ab_testing' => true,
                    'advanced_analytics' => true,
                    'priority_support' => false,
                    'dedicated_ip' => false,
                    'white_label' => false,
                    'custom_branding' => true,
                ],
                'sort_order' => 3,
                'is_popular' => true,
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 14,
                'color' => 'violet',
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Solution complète pour les entreprises exigeantes.',
                'price_monthly' => 49900,
                'price_yearly' => 499000,
                'currency' => 'XOF',
                'emails_per_month' => 100000,
                'contacts_limit' => 25000,
                'campaigns_per_month' => 0, // Unlimited
                'templates_limit' => 0, // Unlimited
                'users_limit' => 15,
                'api_requests_per_day' => 50000,
                'features' => [
                    'email_editor' => true,
                    'custom_domain' => true,
                    'api_access' => true,
                    'automation' => true,
                    'ab_testing' => true,
                    'advanced_analytics' => true,
                    'priority_support' => true,
                    'dedicated_ip' => false,
                    'white_label' => true,
                    'custom_branding' => true,
                ],
                'sort_order' => 4,
                'is_popular' => false,
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 14,
                'color' => 'emerald',
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Pour les grandes organisations avec des besoins personnalisés.',
                'price_monthly' => 149900,
                'price_yearly' => 1499000,
                'currency' => 'XOF',
                'emails_per_month' => 0, // Unlimited
                'contacts_limit' => 0, // Unlimited
                'campaigns_per_month' => 0, // Unlimited
                'templates_limit' => 0, // Unlimited
                'users_limit' => 0, // Unlimited
                'api_requests_per_day' => 0, // Unlimited
                'features' => [
                    'email_editor' => true,
                    'custom_domain' => true,
                    'api_access' => true,
                    'automation' => true,
                    'ab_testing' => true,
                    'advanced_analytics' => true,
                    'priority_support' => true,
                    'dedicated_ip' => true,
                    'white_label' => true,
                    'custom_branding' => true,
                ],
                'sort_order' => 5,
                'is_popular' => false,
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
                'color' => 'amber',
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Plans seeded successfully!');
    }
}
