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
        // Supprimer la contrainte unique composite sur (email, role)
        DB::statement('DROP INDEX IF EXISTS users_email_role_unique');
        
        // Nettoyer les doublons éventuels : garder le premier utilisateur créé pour chaque email
        // Supprimer les doublons en gardant l'ID le plus petit (premier créé)
        $duplicates = DB::table('users')
            ->select('email', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();
        
        foreach ($duplicates as $duplicate) {
            // Supprimer tous les utilisateurs avec cet email sauf celui à garder
            DB::table('users')
                ->where('email', $duplicate->email)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }
        
        // Créer une contrainte unique simple sur email
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la contrainte unique simple sur email
            $table->dropUnique(['email']);
        });
        
        // Restaurer la contrainte unique composite sur (email, role)
        DB::statement('CREATE UNIQUE INDEX users_email_role_unique ON users(email, role)');
    }
};
