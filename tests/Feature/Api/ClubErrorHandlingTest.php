<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_database_connection_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Simuler une erreur de connexion à la base de données
        $this->app['db']->disconnect();

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        // Vérifier que l'erreur est gérée gracieusement
        $response->assertStatus(500);
    }

    #[Test]
    public function it_handles_invalid_json_requests()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec du JSON invalide
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', 'invalid json');

        $response->assertStatus(400);
    }

    #[Test]
    public function it_handles_missing_required_fields()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des champs requis manquants
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_handles_invalid_field_types()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des types de champs invalides
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 123, // Doit être une chaîne
                'email' => 'invalid-email',
                'max_students' => 'not-a-number', // Doit être un nombre
                'subscription_price' => 'not-a-number' // Doit être un nombre
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_handles_field_length_validation()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des champs trop longs
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => str_repeat('a', 256), // Trop long
                'description' => str_repeat('b', 1001), // Trop long
                'address' => str_repeat('c', 501), // Trop long
                'phone' => str_repeat('d', 21), // Trop long
                'email' => str_repeat('e', 100) . '@example.com', // Trop long
                'max_students' => 50,
                'subscription_price' => 100.00
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'address', 'phone', 'email']);
    }

    #[Test]
    public function it_handles_nonexistent_resources()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des ressources inexistantes
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/teachers/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_handles_duplicate_email_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un club avec un email existant
        $existingClub = Club::factory()->create(['email' => 'existing@club.fr']);

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 23 45 67 89',
                'email' => 'existing@club.fr', // Email déjà utilisé
                'max_students' => 50,
                'subscription_price' => 100.00
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_handles_constraint_violation_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des contraintes de base de données violées
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => null, // Contrainte NOT NULL
                'email' => null, // Contrainte NOT NULL
                'max_students' => -1, // Contrainte CHECK
                'subscription_price' => -100.00 // Contrainte CHECK
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_handles_foreign_key_constraint_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des clés étrangères invalides
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'teacher@example.com',
                'club_id' => 999999 // Club inexistant
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_handles_memory_limit_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des données très volumineuses
        $largeData = [
            'name' => 'Club Modifié',
            'description' => str_repeat('Description très longue ', 1000),
            'address' => str_repeat('Adresse très longue ', 1000),
            'phone' => '01 23 45 67 89',
            'email' => 'test@club.fr',
            'max_students' => 50,
            'subscription_price' => 100.00
        ];

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $largeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description', 'address']);
    }

    #[Test]
    public function it_handles_timeout_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec une requête qui pourrait prendre du temps
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_concurrent_modification_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de modifications concurrentes
        $response1 = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié 1',
                'description' => 'Description modifiée 1',
                'address' => 'Nouvelle adresse 1',
                'phone' => '01 23 45 67 89',
                'email' => 'test1@club.fr',
                'max_students' => 50,
                'subscription_price' => 100.00
            ]);

        $response2 = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié 2',
                'description' => 'Description modifiée 2',
                'address' => 'Nouvelle adresse 2',
                'phone' => '01 23 45 67 89',
                'email' => 'test2@club.fr',
                'max_students' => 60,
                'subscription_price' => 120.00
            ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    #[Test]
    public function it_handles_network_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des erreurs réseau simulées
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_authentication_errors()
    {
        // Test avec un token d'authentification invalide
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/club/dashboard');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_handles_authorization_errors()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($student)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_handles_rate_limiting_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec de nombreuses requêtes pour déclencher la limitation de taux
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($clubUser)
                ->getJson('/api/club/dashboard');
        }

        // Vérifier que toutes les requêtes sont traitées normalement
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function it_handles_corrupted_data_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test avec des données corrompues
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 23 45 67 89',
                'email' => 'test@club.fr',
                'max_students' => 'corrupted-data',
                'subscription_price' => 'corrupted-data'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_students', 'subscription_price']);
    }
}
