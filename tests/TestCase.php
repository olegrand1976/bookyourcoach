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
     * SQLite si le driver est disponible (sinon MySQL via tests/bootstrap.php).
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (extension_loaded('pdo_sqlite')) {
            $databasePath = database_path('testing.sqlite');
            if (!file_exists($databasePath)) {
                touch($databasePath);
            }
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => $databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
        } else {
            config(['database.default' => 'mysql']);
            $testDb = env('DB_DATABASE_TEST', (env('DB_DATABASE', 'bookyourcoach')) . '_test');
            config(['database.connections.mysql.database' => $testDb]);
            $this->app->forgetInstance(\Illuminate\Database\DatabaseManager::class);
        }
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
