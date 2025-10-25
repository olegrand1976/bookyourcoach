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
        Schema::table('students', function (Blueprint $table) {
            // Modifier la colonne level pour permettre les valeurs nulles
            $table->enum('level', ['debutant', 'intermediaire', 'avance', 'expert'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Remettre la colonne level comme non-nullable avec une valeur par défaut
            $table->enum('level', ['debutant', 'intermediaire', 'avance', 'expert'])->default('debutant')->change();
        });
    }
};
