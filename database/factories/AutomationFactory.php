<?php

namespace Database\Factories;

use App\Models\Automation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationFactory extends Factory
{
    protected $model = Automation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
            'trigger_config' => [],
            'status' => Automation::STATUS_DRAFT,
            'allow_re_entry' => false,
            'total_enrolled' => 0,
            'active' => 0,
            'completed' => 0,
            'exited' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Automation::STATUS_ACTIVE,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Automation::STATUS_PAUSED,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => Automation::TRIGGER_MANUAL,
        ]);
    }

    public function onListSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
        ]);
    }

    public function onTagAdded(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => Automation::TRIGGER_TAG_ADDED,
        ]);
    }
}
