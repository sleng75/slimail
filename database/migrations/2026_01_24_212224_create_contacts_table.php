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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Core contact info
            $table->string('email')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();

            // Custom fields (JSON)
            $table->json('custom_fields')->nullable();

            // Subscription status
            $table->enum('status', ['subscribed', 'unsubscribed', 'bounced', 'complained'])->default('subscribed');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_reason')->nullable();

            // Email metrics
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('emails_opened')->default(0);
            $table->unsignedInteger('emails_clicked')->default(0);
            $table->timestamp('last_email_sent_at')->nullable();
            $table->timestamp('last_email_opened_at')->nullable();
            $table->timestamp('last_email_clicked_at')->nullable();

            // Source tracking
            $table->string('source')->nullable(); // manual, import, api, form
            $table->string('source_details')->nullable();

            // Engagement score
            $table->decimal('engagement_score', 5, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Unique email per tenant
            $table->unique(['tenant_id', 'email']);

            // Indexes for common queries
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
