<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $tenant = Tenant::factory();

        return [
            'tenant_id' => $tenant,
            'created_by' => User::factory()->state(['tenant_id' => $tenant]),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'subject' => fake()->sentence(),
            'preview_text' => fake()->optional()->sentence(),
            'from_email' => fake()->email(),
            'from_name' => fake()->name(),
            'reply_to' => null,
            'html_content' => '<p>' . fake()->paragraph() . '</p>',
            'text_content' => fake()->paragraph(),
            'template_id' => null,
            'type' => Campaign::TYPE_REGULAR,
            'status' => Campaign::STATUS_DRAFT,
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'timezone' => 'Africa/Ouagadougou',
            'send_by_timezone' => false,
            'list_ids' => [],
            'segment_ids' => [],
            'excluded_list_ids' => [],
            'recipients_count' => 0,
            'track_opens' => true,
            'track_clicks' => true,
            'google_analytics' => false,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'bounced_count' => 0,
            'complained_count' => 0,
            'unsubscribed_count' => 0,
            'ab_test_config' => null,
            'winning_variant_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_DRAFT,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SENDING,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SENT,
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
            'sent_count' => fake()->numberBetween(100, 1000),
            'delivered_count' => fake()->numberBetween(90, 950),
            'opened_count' => fake()->numberBetween(20, 400),
            'clicked_count' => fake()->numberBetween(5, 100),
        ]);
    }

    public function abTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_AB_TEST,
            'ab_test_config' => [
                'test_type' => 'subject',
                'test_percentage' => 20,
                'winning_criteria' => 'opens',
                'test_duration_hours' => 4,
            ],
        ]);
    }
}
