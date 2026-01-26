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
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Trigger configuration
            $table->string('trigger_type'); // list_subscription, tag_added, email_opened, link_clicked, date_field, inactivity, webhook
            $table->json('trigger_config')->nullable(); // Specific configuration for the trigger

            // Status
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');

            // Statistics
            $table->unsignedInteger('total_enrolled')->default(0);
            $table->unsignedInteger('currently_active')->default(0);
            $table->unsignedInteger('completed')->default(0);
            $table->unsignedInteger('exited')->default(0);

            // Settings
            $table->boolean('allow_reentry')->default(false); // Can a contact enter multiple times
            $table->unsignedInteger('reentry_delay_days')->nullable(); // Minimum days before re-entry
            $table->boolean('exit_on_goal')->default(false); // Exit when goal is reached
            $table->json('goal_config')->nullable(); // Goal configuration (e.g., tag added, email opened)

            // Schedule
            $table->json('schedule')->nullable(); // Days/hours when automation can run
            $table->string('timezone')->default('Africa/Abidjan');

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'trigger_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
