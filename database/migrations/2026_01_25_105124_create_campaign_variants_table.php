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
        Schema::create('campaign_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            // Variant identifier (A, B, C...)
            $table->string('variant_key', 1)->default('A');
            $table->string('name')->nullable();

            // Content that varies
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->longText('html_content')->nullable();
            $table->string('preview_text')->nullable();

            // Test configuration
            $table->unsignedInteger('percentage')->default(50); // % of recipients
            $table->boolean('is_winner')->default(false);

            // Stats for this variant
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);

            $table->timestamps();

            $table->unique(['campaign_id', 'variant_key']);
            $table->index('is_winner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_variants');
    }
};
