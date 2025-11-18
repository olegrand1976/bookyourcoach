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
        // Vérifier que la table users existe avant de la modifier
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Vérifier que les colonnes n'existent pas déjà
                if (!Schema::hasColumn('users', 'role')) {
                    // SQLite ne supporte pas bien les ENUMs avec default
                    // On utilise string avec default pour compatibilité
                    $table->string('role')->default('student');
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (!Schema::hasColumn('users', 'status')) {
                    // Même chose pour status
                    $table->string('status')->default('active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'status']);
        });
    }
};
