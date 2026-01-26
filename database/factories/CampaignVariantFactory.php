<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignVariantFactory extends Factory
{
    protected $model = CampaignVariant::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'variant_key' => fake()->randomElement(['A', 'B', 'C']),
            'subject' => fake()->sentence(),
            'from_name' => fake()->optional()->name(),
            'html_content' => '<p>' . fake()->paragraph() . '</p>',
            'percentage' => 50,
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
        ];
    }

    public function variantA(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_key' => 'A',
            'percentage' => 50,
        ]);
    }

    public function variantB(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_key' => 'B',
            'percentage' => 50,
        ]);
    }
}
