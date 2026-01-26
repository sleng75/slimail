<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional()->company(),
            'job_title' => fake()->optional()->jobTitle(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'country' => 'BF',
            'postal_code' => null,
            'status' => Contact::STATUS_SUBSCRIBED,
            'source' => Contact::SOURCE_MANUAL,
            'source_details' => null,
            'custom_fields' => [],
            'engagement_score' => 0,
            'subscribed_at' => now(),
        ];
    }

    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'subscribed',
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
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

    public function withEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'engagement_score' => fake()->numberBetween(10, 90),
        ]);
    }
}
