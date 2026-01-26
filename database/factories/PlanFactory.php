<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Starter', 'Pro', 'Business', 'Enterprise']);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([10000, 25000, 50000, 100000]),
            'currency' => 'XOF',
            'billing_period' => 'monthly',
            'email_limit' => fake()->randomElement([1000, 10000, 50000, 100000]),
            'contact_limit' => fake()->randomElement([500, 5000, 25000, 100000]),
            'team_members_limit' => fake()->randomElement([1, 5, 10, 50]),
            'features' => [
                'email_editor' => true,
                'templates' => true,
                'automations' => true,
                'api_access' => true,
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 10000,
            'email_limit' => 1000,
            'contact_limit' => 500,
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 25000,
            'email_limit' => 10000,
            'contact_limit' => 5000,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Business',
            'slug' => 'business',
            'price' => 50000,
            'email_limit' => 50000,
            'contact_limit' => 25000,
        ]);
    }
}
