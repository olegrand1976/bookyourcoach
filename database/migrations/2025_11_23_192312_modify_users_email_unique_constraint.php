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
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la contrainte unique sur email seule
            // Pour permettre le même email avec des rôles différents
            $table->dropUnique(['email']);
        });

        // Créer une contrainte unique composite sur (email, role)
        // Cela permet d'avoir le même email avec des rôles différents
        // mais pas le même email avec le même rôle
        DB::statement('CREATE UNIQUE INDEX users_email_role_unique ON users(email, role)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la contrainte unique composite
            DB::statement('DROP INDEX IF EXISTS users_email_role_unique');
            
            // Restaurer la contrainte unique simple sur email
            $table->unique('email');
        });
    }
};
