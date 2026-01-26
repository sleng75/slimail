<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailEventFactory extends Factory
{
    protected $model = EmailEvent::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sent_email_id' => SentEmail::factory(),
            'contact_id' => null,
            'message_id' => null,
            'event_type' => 'open',
            'event_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'client_name' => fake()->randomElement(['Gmail', 'Outlook', 'Apple Mail', 'Yahoo Mail']),
            'link_url' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'open',
        ]);
    }

    public function click(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'click',
            'link_url' => fake()->url(),
        ]);
    }

    public function bounce(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'bounce',
        ]);
    }

    public function complaint(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'complaint',
        ]);
    }

    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'desktop',
        ]);
    }

    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'mobile',
        ]);
    }
}
