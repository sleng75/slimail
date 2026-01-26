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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            // Payment details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF');

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'canceled',
                'refunded',
                'expired'
            ])->default('pending');

            // Payment method
            $table->enum('payment_method', [
                'orange_money',
                'moov_money',
                'mtn_money',
                'wave',
                'card',
                'bank_transfer',
                'cash',
                'other'
            ])->nullable();

            // CinetPay specific fields
            $table->string('cinetpay_transaction_id')->nullable();
            $table->string('cinetpay_payment_token')->nullable();
            $table->string('cinetpay_payment_url')->nullable();

            // Transaction details
            $table->string('transaction_id')->nullable(); // Our internal reference
            $table->string('external_reference')->nullable(); // Provider's reference
            $table->string('operator_id')->nullable(); // Mobile money operator transaction ID

            // Customer details (for mobile money)
            $table->string('phone_number')->nullable();
            $table->string('customer_name')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Store any additional data
            $table->text('failure_reason')->nullable();

            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Refund tracking
            $table->boolean('is_refund')->default(false);
            $table->foreignId('refund_for_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('refund_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('cinetpay_transaction_id');
            $table->index('transaction_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
