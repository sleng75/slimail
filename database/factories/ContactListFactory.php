<?php

namespace Database\Factories;

use App\Models\ContactList;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactListFactory extends Factory
{
    protected $model = ContactList::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->randomElement(ContactList::COLORS),
            'type' => ContactList::TYPE_STATIC,
            'segment_criteria' => null,
            'double_optin' => false,
            'welcome_email_content' => null,
            'default_from_name' => null,
            'default_from_email' => null,
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactList::TYPE_DYNAMIC,
            'segment_criteria' => [
                'conditions' => [],
            ],
        ]);
    }

    public function withDoubleOptin(): static
    {
        return $this->state(fn (array $attributes) => [
            'double_optin' => true,
        ]);
    }
}
