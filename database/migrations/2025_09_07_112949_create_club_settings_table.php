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
        Schema::create('club_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('feature_key')->index(); // Clé unique de la fonctionnalité
            $table->string('feature_name'); // Nom affiché de la fonctionnalité
            $table->string('feature_category'); // Catégorie (financial, management, communication, etc.)
            $table->boolean('is_enabled')->default(false); // Fonctionnalité activée ou non
            $table->json('configuration')->nullable(); // Configuration spécifique de la fonctionnalité
            $table->text('description')->nullable(); // Description de la fonctionnalité
            $table->string('icon')->nullable(); // Icône de la fonctionnalité
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            $table->timestamps();

            $table->unique(['club_id', 'feature_key']);
            $table->index(['club_id', 'feature_category']);
            $table->index(['feature_category', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_settings');
    }
};