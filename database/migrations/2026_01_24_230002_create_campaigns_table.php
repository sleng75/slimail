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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Campaign info
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['regular', 'ab_test', 'automated'])->default('regular');

            // Sender info
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();

            // Subject
            $table->string('subject');
            $table->string('preview_text')->nullable();

            // Content
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();

            // Status
            $table->enum('status', [
                'draft',
                'scheduled',
                'sending',
                'sent',
                'paused',
                'cancelled'
            ])->default('draft');

            // Schedule
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('timezone')->default('Africa/Abidjan');
            $table->boolean('send_by_timezone')->default(false);

            // Recipients
            $table->json('list_ids')->nullable(); // Contact list IDs
            $table->json('segment_ids')->nullable(); // Segment IDs
            $table->json('excluded_list_ids')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);

            // Stats
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);

            // A/B Testing
            $table->json('ab_test_config')->nullable();
            $table->foreignId('winning_variant_id')->nullable();

            // Settings
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->boolean('google_analytics')->default(false);
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
