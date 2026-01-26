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
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();

            // Email details
            $table->string('message_id')->unique()->nullable(); // SES Message ID
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject');
            $table->text('html_content')->nullable();
            $table->text('text_content')->nullable();

            // Type: transactional, campaign, automation
            $table->enum('type', ['transactional', 'campaign', 'automation'])->default('transactional');

            // Status
            $table->enum('status', [
                'queued',
                'sending',
                'sent',
                'delivered',
                'opened',
                'clicked',
                'bounced',
                'complained',
                'failed',
                'rejected'
            ])->default('queued');

            // Tracking
            $table->unsignedInteger('opens_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();

            // Bounce/Error info
            $table->string('bounce_type')->nullable(); // Permanent, Transient
            $table->string('bounce_subtype')->nullable();
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('headers')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('contact_id');
            $table->index('campaign_id');
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
    }
};
