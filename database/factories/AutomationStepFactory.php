<?php

namespace Database\Factories;

use App\Models\Automation;
use App\Models\AutomationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationStepFactory extends Factory
{
    protected $model = AutomationStep::class;

    public function definition(): array
    {
        return [
            'automation_id' => Automation::factory(),
            'type' => AutomationStep::TYPE_WAIT,
            'name' => fake()->sentence(2),
            'config' => ['duration' => 1, 'unit' => 'days'],
            'position' => 0,
        ];
    }

    public function wait(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration' => 1, 'unit' => 'days'],
        ]);
    }

    public function sendEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_SEND_EMAIL,
            'config' => [
                'subject' => fake()->sentence(),
                'html_content' => '<p>' . fake()->paragraph() . '</p>',
            ],
        ]);
    }

    public function addTag(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_ADD_TAG,
            'config' => ['tag_id' => null],
        ]);
    }

    public function removeTag(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_REMOVE_TAG,
            'config' => ['tag_id' => null],
        ]);
    }

    public function addToList(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_ADD_TO_LIST,
            'config' => ['list_id' => null],
        ]);
    }

    public function updateField(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_UPDATE_FIELD,
            'config' => ['field' => 'company', 'value' => fake()->company()],
        ]);
    }

    public function webhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AutomationStep::TYPE_WEBHOOK,
            'config' => ['url' => fake()->url(), 'method' => 'POST'],
        ]);
    }
}
