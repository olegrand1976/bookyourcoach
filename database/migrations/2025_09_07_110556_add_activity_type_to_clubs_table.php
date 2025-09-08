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
        Schema::table('clubs', function (Blueprint $table) {
            // Ajouter la colonne activity_type_id si elle n'existe pas
            if (!Schema::hasColumn('clubs', 'activity_type_id')) {
                $table->foreignId('activity_type_id')->nullable()->constrained('activity_types')->onDelete('set null');
            }
            
            // Ajouter les colonnes seulement si elles n'existent pas
            if (!Schema::hasColumn('clubs', 'seasonal_variation')) {
                $table->decimal('seasonal_variation', 5, 2)->default(0.00); // Variation saisonnière en %
            }
            
            if (!Schema::hasColumn('clubs', 'weather_dependency')) {
                $table->boolean('weather_dependency')->default(false); // Dépendance météo
            }
            
            // Ajouter l'index seulement si la colonne activity_type_id existe
            if (Schema::hasColumn('clubs', 'activity_type_id')) {
                $table->index(['activity_type_id', 'is_active']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'seasonal_variation',
                'weather_dependency'
            ]);
        });
    }
};