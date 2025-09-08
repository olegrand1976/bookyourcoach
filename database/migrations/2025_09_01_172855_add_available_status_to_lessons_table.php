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
        // Modifier l'enum pour inclure 'available' - Compatible SQLite et MySQL
        if (DB::getDriverName() === 'sqlite') {
            // Pour SQLite, on recrée la table avec la nouvelle colonne
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        } else {
            // Pour MySQL, utiliser MODIFY COLUMN
            DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show', 'available') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'available' de l'enum - Compatible SQLite et MySQL
        if (DB::getDriverName() === 'sqlite') {
            // Pour SQLite, on recrée la table avec l'ancienne colonne
            Schema::table('lessons', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        } else {
            // Pour MySQL, utiliser MODIFY COLUMN
            DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending'");
        }
    }
};
