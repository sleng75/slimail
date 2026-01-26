<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            // Billing cycle
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('XOF');

            // Status
            $table->enum('status', [
                'trialing',
                'active',
                'past_due',
                'canceled',
                'suspended',
                'expired'
            ])->default('trialing');

            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            // Usage tracking for current period
            $table->integer('emails_used')->default(0);
            $table->integer('contacts_count')->default(0);
            $table->integer('campaigns_used')->default(0);
            $table->integer('api_requests_today')->default(0);
            $table->date('api_requests_reset_date')->nullable();

            // Cancellation
            $table->string('cancellation_reason')->nullable();
            $table->text('cancellation_feedback')->nullable();

            // External references
            $table->string('external_id')->nullable(); // CinetPay subscription ID

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
