<?php

namespace Database\Factories;

use App\Models\AutomationEnrollment;
use App\Models\AutomationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationLogFactory extends Factory
{
    protected $model = AutomationLog::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => AutomationEnrollment::factory(),
            'action' => AutomationLog::ACTION_ENROLLED,
            'data' => [],
            'created_at' => now(),
        ];
    }
}
