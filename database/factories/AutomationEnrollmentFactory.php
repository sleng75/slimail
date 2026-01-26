<?php

namespace Database\Factories;

use App\Models\Automation;
use App\Models\AutomationEnrollment;
use App\Models\AutomationStep;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationEnrollmentFactory extends Factory
{
    protected $model = AutomationEnrollment::class;

    public function definition(): array
    {
        return [
            'automation_id' => Automation::factory(),
            'contact_id' => Contact::factory(),
            'current_step_id' => null,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
            'next_action_at' => now(),
            'completed_at' => null,
            'exited_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AutomationEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AutomationEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function exited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AutomationEnrollment::STATUS_EXITED,
            'exited_at' => now(),
        ]);
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AutomationEnrollment::STATUS_WAITING,
        ]);
    }
}
