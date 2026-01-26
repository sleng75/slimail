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
        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();

            // Step type and configuration
            $table->string('type'); // send_email, wait, condition, add_tag, remove_tag, add_to_list, remove_from_list, update_field, webhook, goal
            $table->string('name')->nullable();
            $table->json('config'); // Step-specific configuration

            // Position in the workflow
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('parent_step_id')->nullable()->constrained('automation_steps')->cascadeOnDelete();
            $table->string('branch')->nullable(); // 'yes', 'no' for conditions, null for linear steps

            // Statistics
            $table->unsignedInteger('entered_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            // For email steps - performance metrics
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('emails_opened')->default(0);
            $table->unsignedInteger('emails_clicked')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['automation_id', 'position']);
            $table->index(['automation_id', 'parent_step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_steps');
    }
};
