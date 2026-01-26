<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    protected static int $invoiceNumber = 1;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => null,
            'number' => 'INV-' . date('Y') . '-' . str_pad(self::$invoiceNumber++, 4, '0', STR_PAD_LEFT),
            'amount' => fake()->numberBetween(10000, 100000),
            'tax_amount' => 0,
            'total_amount' => fake()->numberBetween(10000, 100000),
            'currency' => 'XOF',
            'status' => Invoice::STATUS_PENDING,
            'line_items' => [
                [
                    'description' => 'Abonnement mensuel',
                    'quantity' => 1,
                    'unit_price' => 25000,
                    'total' => 25000,
                ],
            ],
            'billing_address' => [
                'name' => fake()->company(),
                'address' => fake()->address(),
                'city' => fake()->city(),
                'country' => 'BF',
            ],
            'due_date' => now()->addDays(30),
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PENDING,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_OVERDUE,
            'due_date' => now()->subDays(7),
        ]);
    }
}
