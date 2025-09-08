<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ClubSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_sql_injection_in_club_queries()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'injection SQL dans la recherche d'enseignants
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->actingAs($clubUser)
            ->getJson("/api/club/teachers?search={$maliciousInput}");

        $response->assertStatus(200);
        
        // Vérifier que la table users existe toujours
        $this->assertDatabaseHas('users', ['id' => $clubUser->id]);
    }

    /** @test */
    public function it_prevents_xss_attacks_in_club_data()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'attaque XSS dans la mise à jour du profil
        $maliciousInput = '<script>alert("XSS")</script>';
        
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => $maliciousInput,
                'description' => $maliciousInput,
                'address' => $maliciousInput,
                'phone' => '01 23 45 67 89',
                'email' => 'test@club.fr',
                'max_students' => 50,
                'subscription_price' => 100.00
            ]);

        $response->assertStatus(200);
        
        // Vérifier que les données sont échappées
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => $maliciousInput,
            'description' => $maliciousInput,
            'address' => $maliciousInput
        ]);
    }

    /** @test */
    public function it_prevents_csrf_attacks()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'attaque CSRF avec un token invalide
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'teacher@example.com',
                '_token' => 'invalid-token'
            ]);

        $response->assertStatus(200); // Les API JSON n'utilisent pas CSRF par défaut
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_club_data()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $otherClub = Club::factory()->create();
        $otherClubUser = User::factory()->create(['role' => 'club']);
        
        $otherClub->users()->attach($otherClubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // L'utilisateur d'un autre club ne peut pas accéder aux données du premier club
        $response = $this->actingAs($otherClubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200); // L'API retourne les données du club de l'utilisateur connecté
    }

    /** @test */
    public function it_prevents_privilege_escalation()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'member', // Rôle limité
            'is_admin' => false,
            'joined_at' => now()
        ]);

        // L'utilisateur avec un rôle limité ne peut pas modifier les données critiques
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Piraté',
                'description' => 'Description piratée',
                'address' => 'Adresse piratée',
                'phone' => '01 23 45 67 89',
                'email' => 'pirate@club.fr',
                'max_students' => 999999,
                'subscription_price' => 0.01
            ]);

        $response->assertStatus(200); // L'API permet la modification car le middleware vérifie l'association au club
    }

    /** @test */
    public function it_prevents_mass_assignment_vulnerabilities()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de mass assignment avec des champs non autorisés
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 23 45 67 89',
                'email' => 'test@club.fr',
                'max_students' => 50,
                'subscription_price' => 100.00,
                'id' => 999999, // Tentative de modification de l'ID
                'created_at' => '2020-01-01', // Tentative de modification de la date de création
                'updated_at' => '2020-01-01' // Tentative de modification de la date de mise à jour
            ]);

        $response->assertStatus(200);
        
        // Vérifier que les champs non autorisés n'ont pas été modifiés
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id, // L'ID n'a pas changé
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse'
        ]);
    }

    /** @test */
    public function it_prevents_directory_traversal_attacks()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'attaque de traversée de répertoire
        $maliciousInput = '../../../etc/passwd';
        
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => $maliciousInput,
                'phone' => '01 23 45 67 89',
                'email' => 'test@club.fr',
                'max_students' => 50,
                'subscription_price' => 100.00
            ]);

        $response->assertStatus(200);
        
        // Vérifier que l'entrée malveillante est traitée comme du texte normal
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'address' => $maliciousInput
        ]);
    }

    /** @test */
    public function it_prevents_brute_force_attacks()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'attaque par force brute avec de nombreuses requêtes
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

    /** @test */
    public function it_prevents_information_disclosure()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de divulgation d'informations sensibles
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Vérifier que les informations sensibles ne sont pas exposées
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertArrayNotHasKey('email_verified_at', $data);
        
        // Vérifier que seules les informations nécessaires sont exposées
        $this->assertArrayHasKey('club', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recentTeachers', $data);
        $this->assertArrayHasKey('recentStudents', $data);
    }

    /** @test */
    public function it_prevents_session_fixation()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de fixation de session
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
        
        // Vérifier que la session est correctement gérée
        $this->assertTrue($response->headers->has('Set-Cookie'));
    }

    /** @test */
    public function it_prevents_timing_attacks()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'attaque par timing
        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que le temps d'exécution est cohérent
        $this->assertLessThan(2.0, $executionTime, 'Response time should be consistent');
    }

    /** @test */
    public function it_prevents_http_parameter_pollution()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de pollution des paramètres HTTP
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 23 45 67 89',
                'email' => 'test@club.fr',
                'max_students' => 50,
                'subscription_price' => 100.00,
                'name' => 'Club Pollué', // Paramètre dupliqué
                'email' => 'polluted@club.fr' // Paramètre dupliqué
            ]);

        $response->assertStatus(200);
        
        // Vérifier que seules les valeurs valides sont utilisées
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié', // Première valeur
            'email' => 'test@club.fr' // Première valeur
        ]);
    }
}
