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
        // Modifier l'enum pour inclure 'available'
        DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show', 'available') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'available' de l'enum
        DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending'");
    }
};
