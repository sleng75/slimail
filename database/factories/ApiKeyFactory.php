<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true),
            'key' => 'sk_' . Str::random(32),
            'scopes' => ['send:transactional', 'read:contacts'],
            'is_active' => true,
            'requests_count' => 0,
            'last_used_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'requests_count' => fake()->numberBetween(100, 10000),
            'last_used_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }
}
