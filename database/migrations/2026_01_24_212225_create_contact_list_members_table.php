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
        Schema::create('contact_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_list_id')->constrained()->onDelete('cascade');

            // Subscription status within this list
            $table->enum('status', ['active', 'unsubscribed'])->default('active');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();

            // A contact can only be in a list once
            $table->unique(['contact_id', 'contact_list_id']);

            // Index for list queries
            $table->index(['contact_list_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_list_members');
    }
};
