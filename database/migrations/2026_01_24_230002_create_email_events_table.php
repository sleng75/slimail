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
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_email_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();

            // Event type
            $table->enum('event_type', [
                'send',
                'delivery',
                'open',
                'click',
                'bounce',
                'complaint',
                'reject',
                'rendering_failure',
                'delivery_delay'
            ]);

            // Event data
            $table->string('message_id')->nullable();
            $table->timestamp('event_at');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('link_url')->nullable(); // For click events
            $table->string('link_tag')->nullable();

            // Bounce/Complaint details
            $table->string('bounce_type')->nullable();
            $table->string('bounce_subtype')->nullable();
            $table->string('complaint_feedback_type')->nullable();

            // Geo data (for opens/clicks)
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();

            // Device info
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('client_name')->nullable(); // Gmail, Outlook, etc.
            $table->string('client_os')->nullable();

            // Raw data from SES/SNS
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'event_type']);
            $table->index(['sent_email_id', 'event_type']);
            $table->index(['tenant_id', 'event_at']);
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
