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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable(); // Hex color for UI
            $table->text('description')->nullable();

            // Stats (cached)
            $table->unsignedInteger('contacts_count')->default(0);

            $table->timestamps();

            // Unique name per tenant
            $table->unique(['tenant_id', 'slug']);

            // Index for lookups
            $table->index(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
