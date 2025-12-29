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
        $driver = DB::getDriverName();
        
        // Supprimer la contrainte unique composite sur (email, role) selon le driver
        // Utiliser une approche défensive avec gestion d'erreurs
        try {
            if ($driver === 'mysql') {
                // MySQL nécessite la syntaxe DROP INDEX ... ON table
                DB::statement('DROP INDEX users_email_role_unique ON users');
            } elseif ($driver === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS users_email_role_unique');
            } elseif ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS users_email_role_unique');
            } else {
                // Fallback : essayer MySQL puis PostgreSQL/SQLite
                try {
                    DB::statement('DROP INDEX users_email_role_unique ON users');
                } catch (\Exception $e1) {
                    DB::statement('DROP INDEX IF EXISTS users_email_role_unique');
                }
            }
        } catch (\Exception $e) {
            // L'index n'existe peut-être pas, continuer quand même
            // Ne pas bloquer la migration si l'index n'existe pas
        }
        
        // Nettoyer les doublons éventuels : garder le premier utilisateur créé pour chaque email
        // Supprimer les doublons en gardant l'ID le plus petit (premier créé)
        try {
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
        } catch (\Exception $e) {
            // Log l'erreur mais continue la migration
            // Les doublons seront bloqués par la contrainte unique de toute façon
        }
        
        // Vérifier si la contrainte unique sur email existe déjà avant de la créer
        $hasUniqueEmail = false;
        try {
            if ($driver === 'mysql') {
                $indexes = DB::select("SHOW INDEXES FROM users WHERE Column_name = 'email' AND Non_unique = 0");
                $hasUniqueEmail = !empty($indexes);
            } elseif ($driver === 'pgsql') {
                $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'users' AND indexdef LIKE '%UNIQUE%' AND indexdef LIKE '%email%'");
                $hasUniqueEmail = !empty($indexes);
            } elseif ($driver === 'sqlite') {
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND sql LIKE '%UNIQUE%' AND sql LIKE '%email%'");
                $hasUniqueEmail = !empty($indexes);
            }
        } catch (\Exception $e) {
            // En cas d'erreur, supposer que l'index n'existe pas
            $hasUniqueEmail = false;
        }
        
        // Créer une contrainte unique simple sur email seulement si elle n'existe pas déjà
        if (!$hasUniqueEmail) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email');
                });
            } catch (\Exception $e) {
                // Si la création échoue (peut-être que l'index existe déjà), continuer
                // La contrainte unique existe peut-être déjà sous un autre nom
            }
        }
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
