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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            // Invoice number (OHADA compliant: FA-YYYY-NNNNNN)
            $table->string('number')->unique();
            $table->integer('sequence_number'); // For generating next number

            // Status
            $table->enum('status', [
                'draft',
                'pending',
                'paid',
                'partial',
                'overdue',
                'canceled',
                'refunded'
            ])->default('draft');

            // Amounts
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0); // VAT percentage
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2);
            $table->string('currency', 3)->default('XOF');

            // Dates
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Billing details (snapshot at invoice time)
            $table->string('billing_name');
            $table->string('billing_email');
            $table->string('billing_phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_tax_id')->nullable(); // NIF/IFU

            // Line items (JSON for flexibility)
            $table->json('line_items');
            /*
             * Line items structure:
             * [
             *   {
             *     "description": "Abonnement Pro - Janvier 2026",
             *     "quantity": 1,
             *     "unit_price": 25000,
             *     "total": 25000
             *   }
             * ]
             */

            // Notes
            $table->text('notes')->nullable();
            $table->text('footer')->nullable();

            // PDF storage
            $table->string('pdf_path')->nullable();

            // Payment reminders
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
