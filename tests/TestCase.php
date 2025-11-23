<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * Configuration de la base de données pour les tests
     * Utilise une base SQLite persistante pour permettre à RefreshDatabase de fonctionner correctement
     * avec les transactions. La base est nettoyée automatiquement après chaque test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Utiliser une base SQLite persistante au lieu de :memory: pour permettre
        // à RefreshDatabase d'utiliser les transactions correctement
        $databasePath = database_path('testing.sqlite');
        
        // Créer le fichier de base de données s'il n'existe pas
        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
        
        // S'assurer que SQLite est bien configuré
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
    }

    /**
     * Authentifie un utilisateur admin et retourne l'instance.
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Créer un token Sanctum pour l'admin
        $token = $admin->createToken('test-token')->plainTextToken;
        
        // Définir l'en-tête Authorization pour le middleware admin
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        return $admin;
    }

    /**
     * Authentifie un utilisateur club et retourne l'instance.
     */
    protected function actingAsClub(): User
    {
        $user = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);

        $club = \App\Models\Club::factory()->create();
        
        // Créer l'entrée dans club_user (table correcte)
        \Illuminate\Support\Facades\DB::table('club_user')->insert([
            'user_id' => $user->id,
            'club_id' => $club->id,
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Ajouter une propriété dynamique pour accéder au club facilement dans les tests
        $user->club_id = $club->id;

        Sanctum::actingAs($user);
        
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        return $user;
    }

    /**
     * Authentifie un utilisateur enseignant et retourne l'instance.
     */
    protected function actingAsTeacher(): User
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
        ]);

        \App\Models\Teacher::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);
        
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        return $user;
    }

    /**
     * Authentifie un utilisateur élève et retourne l'instance.
     */
    protected function actingAsStudent(): User
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'is_active' => true,
        ]);

        \App\Models\Student::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);
        
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);

        return $user;
    }
}
