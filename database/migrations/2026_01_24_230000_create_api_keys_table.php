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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->string('secret_hash'); // Hashed secret
            $table->text('description')->nullable();
            $table->json('permissions')->nullable(); // ['send', 'contacts', 'campaigns', etc.]
            $table->json('ip_whitelist')->nullable(); // Allowed IPs
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('requests_count')->default(0);
            $table->unsignedInteger('rate_limit')->default(100); // Requests per minute
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
