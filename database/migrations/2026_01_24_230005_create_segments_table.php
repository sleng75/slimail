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
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria')->comment('Segment criteria/rules as JSON');
            $table->enum('match_type', ['all', 'any'])->default('all')->comment('all = AND, any = OR');
            $table->unsignedInteger('cached_count')->default(0)->comment('Cached contact count');
            $table->timestamp('count_cached_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });

        // Pivot table for campaigns using segments
        Schema::create('campaign_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('segment_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['campaign_id', 'segment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_segment');
        Schema::dropIfExists('segments');
    }
};
