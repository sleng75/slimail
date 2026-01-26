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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Plan identification
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('XOF'); // Franc CFA

            // Limits
            $table->integer('emails_per_month')->default(0); // 0 = unlimited
            $table->integer('contacts_limit')->default(0); // 0 = unlimited
            $table->integer('campaigns_per_month')->default(0); // 0 = unlimited
            $table->integer('templates_limit')->default(0); // 0 = unlimited
            $table->integer('users_limit')->default(1);
            $table->integer('api_requests_per_day')->default(0); // 0 = unlimited

            // Features (JSON for flexibility)
            $table->json('features')->nullable();

            // Display
            $table->integer('sort_order')->default(0);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);

            // Trial
            $table->integer('trial_days')->default(0);

            // Metadata
            $table->string('stripe_price_id')->nullable();
            $table->string('color')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
