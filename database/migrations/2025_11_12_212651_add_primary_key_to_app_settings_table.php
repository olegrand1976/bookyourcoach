<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la colonne id existe déjà
        if (!Schema::hasColumn('app_settings', 'id')) {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->id()->first();
            });
        } else {
            // Si la colonne existe, vérifier si elle a déjà une clé primaire
            $hasPrimaryKey = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'app_settings'
                AND CONSTRAINT_TYPE = 'PRIMARY KEY'
            ");
            
            if ($hasPrimaryKey[0]->count == 0) {
                // Ajouter la clé primaire sur la colonne id existante
                DB::statement('ALTER TABLE app_settings ADD PRIMARY KEY (id)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne pas supprimer la colonne id car elle peut être utilisée ailleurs
        // On peut seulement supprimer la clé primaire si nécessaire
        DB::statement('ALTER TABLE app_settings DROP PRIMARY KEY');
    }
};
