<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SentEmailFactory extends Factory
{
    protected $model = SentEmail::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'campaign_id' => null,
            'contact_id' => null,
            'api_key_id' => null,
            'message_id' => 'msg-' . Str::uuid(),
            'from_email' => fake()->email(),
            'from_name' => fake()->name(),
            'to_email' => fake()->email(),
            'to_name' => fake()->name(),
            'subject' => fake()->sentence(),
            'type' => SentEmail::TYPE_CAMPAIGN,
            'status' => 'sent',
            'metadata' => [],
        ];
    }

    public function transactional(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SentEmail::TYPE_TRANSACTIONAL,
            'campaign_id' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'opened',
            'delivered_at' => now()->subHours(2),
            'opened_at' => now(),
        ]);
    }

    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'clicked',
            'delivered_at' => now()->subHours(2),
            'opened_at' => now()->subHour(),
            'clicked_at' => now(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'bounced',
            'bounced_at' => now(),
        ]);
    }

    public function complained(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'complained',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Delivery failed',
        ]);
    }
}
