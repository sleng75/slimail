<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => null,
            'subscription_id' => null,
            'transaction_id' => 'TXN-' . Str::uuid(),
            'amount' => fake()->numberBetween(5000, 50000),
            'currency' => 'XOF',
            'status' => Payment::STATUS_PENDING,
            'payment_method' => null,
            'customer_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'cinetpay_payment_token' => null,
            'cinetpay_payment_url' => null,
            'cinetpay_transaction_id' => null,
            'error_message' => null,
            'initiated_at' => now(),
            'completed_at' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PROCESSING,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_COMPLETED,
            'payment_method' => Payment::METHOD_ORANGE_MONEY,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'error_message' => 'Payment failed',
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_CANCELED,
        ]);
    }
}
