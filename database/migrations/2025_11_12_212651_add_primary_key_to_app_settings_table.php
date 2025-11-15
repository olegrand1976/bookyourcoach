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
            // Utiliser une méthode compatible avec MySQL et SQLite
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // Pour SQLite, vérifier si la table a déjà une clé primaire
                $tableInfo = DB::select("PRAGMA table_info(app_settings)");
                $hasPrimaryKey = false;
                foreach ($tableInfo as $column) {
                    if ($column->name === 'id' && $column->pk == 1) {
                        $hasPrimaryKey = true;
                        break;
                    }
                }
                
                if (!$hasPrimaryKey) {
                    // SQLite ne supporte pas ALTER TABLE ADD PRIMARY KEY directement
                    // On doit recréer la table (mais c'est complexe, donc on skip pour SQLite)
                    // En production avec MySQL, cela fonctionnera
                }
            } else {
                // Pour MySQL/MariaDB
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
