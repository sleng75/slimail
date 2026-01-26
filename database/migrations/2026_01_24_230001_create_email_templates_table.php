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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // newsletter, promotional, transactional
            $table->string('thumbnail')->nullable();

            // Content
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->json('design_json')->nullable(); // GrapesJS JSON

            // Default values
            $table->string('default_subject')->nullable();
            $table->string('default_from_name')->nullable();
            $table->string('default_from_email')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates can't be deleted

            // Stats
            $table->unsignedInteger('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
