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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de l'entreprise
            $table->string('slug')->unique(); // Identifiant unique pour l'URL
            $table->string('domain')->nullable()->unique(); // Domaine personnalisé (optionnel)
            $table->string('email'); // Email principal de l'entreprise
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Burkina Faso');
            $table->string('logo')->nullable(); // Chemin vers le logo
            $table->string('timezone')->default('Africa/Ouagadougou');
            $table->string('locale')->default('fr');
            $table->json('settings')->nullable(); // Paramètres personnalisés
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
