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
        Schema::create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // For UI differentiation

            // List type
            $table->enum('type', ['static', 'dynamic'])->default('static');

            // For dynamic lists - segment criteria
            $table->json('segment_criteria')->nullable();

            // Stats (cached for performance)
            $table->unsignedInteger('contacts_count')->default(0);
            $table->unsignedInteger('subscribed_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);

            // Double opt-in settings
            $table->boolean('double_optin')->default(false);
            $table->text('welcome_email_content')->nullable();

            // Default sender for campaigns using this list
            $table->string('default_from_name')->nullable();
            $table->string('default_from_email')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_lists');
    }
};
