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
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('automation_enrollments')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('automation_steps')->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            // Action details
            $table->string('action'); // enrolled, step_started, step_completed, step_failed, condition_evaluated, exited, completed
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->text('message')->nullable();
            $table->json('data')->nullable(); // Additional context data

            // For email steps
            $table->foreignId('sent_email_id')->nullable()->constrained('sent_emails')->nullOnDelete();

            $table->timestamp('created_at');

            // Indexes
            $table->index(['automation_id', 'created_at']);
            $table->index(['enrollment_id', 'created_at']);
            $table->index(['contact_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
