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
        Schema::create('automation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('automation_steps')->nullOnDelete();

            // Status
            $table->enum('status', ['active', 'waiting', 'completed', 'exited', 'failed'])->default('active');
            $table->string('exit_reason')->nullable(); // goal_reached, manual, unsubscribed, error, etc.

            // Timing
            $table->timestamp('enrolled_at');
            $table->timestamp('next_action_at')->nullable(); // When to process the next step
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('exited_at')->nullable();

            // Tracking
            $table->json('step_history')->nullable(); // Array of completed steps with timestamps
            $table->json('metadata')->nullable(); // Additional data (trigger source, etc.)

            // For wait steps
            $table->unsignedInteger('wait_until')->nullable(); // Unix timestamp

            $table->timestamps();

            // Indexes
            $table->index(['automation_id', 'status']);
            $table->index(['automation_id', 'contact_id']);
            $table->index(['status', 'next_action_at']);
            $table->index('contact_id');

            // Unique constraint to prevent duplicate active enrollments (unless reentry is allowed)
            $table->unique(['automation_id', 'contact_id', 'enrolled_at'], 'enrollments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_enrollments');
    }
};
