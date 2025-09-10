<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubTestSuite extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_runs_complete_club_test_suite()
    {
        $this->markTestSkipped('This is a test suite runner - individual tests should be run separately');
    }

    #[Test]
    public function it_creates_test_environment()
    {
        // Créer un environnement de test complet
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clubUser = User::factory()->create(['role' => 'club']);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club = Club::factory()->create([
            'name' => 'Club de Test',
            'description' => 'Club pour les tests',
            'address' => '123 Rue de Test',
            'phone' => '01 23 45 67 89',
            'email' => 'test@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00,
            'is_active' => true
        ]);

        // Associer les utilisateurs au club
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        // Vérifier que l'environnement de test est correctement configuré
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
        $this->assertDatabaseHas('users', ['id' => $clubUser->id, 'role' => 'club']);
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'role' => User::ROLE_TEACHER]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'role' => User::ROLE_STUDENT]);
        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'name' => 'Club de Test']);
        $this->assertDatabaseHas('club_user', ['club_id' => $club->id, 'user_id' => $clubUser->id]);
        $this->assertDatabaseHas('club_user', ['club_id' => $club->id, 'user_id' => $teacher->id]);
        $this->assertDatabaseHas('club_user', ['club_id' => $club->id, 'user_id' => $student->id]);
    }

    #[Test]
    public function it_validates_test_data_integrity()
    {
        // Créer des données de test
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Vérifier l'intégrité des données
        $this->assertTrue($clubUser->isClub());
        $this->assertTrue($clubUser->clubs()->exists());
        $this->assertEquals(1, $club->users()->count());
        $this->assertEquals('owner', $club->users()->where('user_id', $clubUser->id)->first()->pivot->role);
    }

    #[Test]
    public function it_tests_all_club_endpoints()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Tester tous les endpoints du club
        $endpoints = [
            'GET /api/club/dashboard',
            'GET /api/club/teachers',
            'GET /api/club/students',
            'POST /api/club/teachers',
            'POST /api/club/students',
            'PUT /api/club/profile'
        ];

        foreach ($endpoints as $endpoint) {
            $method = explode(' ', $endpoint)[0];
            $path = explode(' ', $endpoint)[1];
            
            if ($method === 'GET') {
                $response = $this->actingAs($clubUser)->getJson($path);
            } elseif ($method === 'POST') {
                $response = $this->actingAs($clubUser)->postJson($path, []);
            } elseif ($method === 'PUT') {
                $response = $this->actingAs($clubUser)->putJson($path, []);
            }

            // Vérifier que l'endpoint répond (pas forcément 200, mais pas d'erreur 500)
            $this->assertLessThan(500, $response->getStatusCode(), "Endpoint {$endpoint} should not return server error");
        }
    }

    #[Test]
    public function it_tests_all_admin_endpoints()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        // Tester tous les endpoints admin pour les clubs
        $endpoints = [
            'GET /api/admin/clubs',
            'POST /api/admin/clubs',
            'GET /api/admin/clubs/{id}',
            'PUT /api/admin/clubs/{id}',
            'DELETE /api/admin/clubs/{id}',
            'PUT /api/admin/clubs/{id}/toggle-status'
        ];

        foreach ($endpoints as $endpoint) {
            $method = explode(' ', $endpoint)[0];
            $path = str_replace('{id}', $club->id, explode(' ', $endpoint)[1]);
            
            if ($method === 'GET') {
                $response = $this->actingAs($admin)->getJson($path);
            } elseif ($method === 'POST') {
                $response = $this->actingAs($admin)->postJson($path, []);
            } elseif ($method === 'PUT') {
                $response = $this->actingAs($admin)->putJson($path, []);
            } elseif ($method === 'DELETE') {
                $response = $this->actingAs($admin)->deleteJson($path);
            }

            // Vérifier que l'endpoint répond (pas forcément 200, mais pas d'erreur 500)
            $this->assertLessThan(500, $response->getStatusCode(), "Endpoint {$endpoint} should not return server error");
        }
    }

    #[Test]
    public function it_tests_middleware_protection()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // Tester la protection du middleware
        $this->actingAs($clubUser)->getJson('/api/club/dashboard')->assertStatus(200);
        $this->actingAs($student)->getJson('/api/club/dashboard')->assertStatus(403);
        $this->getJson('/api/club/dashboard')->assertStatus(403);
    }

    #[Test]
    public function it_tests_data_relationships()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        // Vérifier les relations
        $this->assertTrue($clubUser->clubs()->exists());
        $this->assertTrue($teacher->clubs()->exists());
        $this->assertTrue($student->clubs()->exists());
        $this->assertEquals(3, $club->users()->count());
        $this->assertEquals(1, $club->users()->wherePivot('role', 'teacher')->count());
        $this->assertEquals(1, $club->users()->wherePivot('role', 'student')->count());
    }

    #[Test]
    public function it_tests_performance_benchmarks()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de performance
        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la performance est acceptable
        $this->assertLessThan(2.0, $executionTime, 'Dashboard should load in less than 2 seconds');
    }

    #[Test]
    public function it_tests_error_handling()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de gestion d'erreurs
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_tests_security_measures()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de sécurité
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
        
        // Vérifier que les données sensibles ne sont pas exposées
        $data = $response->json();
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    #[Test]
    public function it_tests_scalability()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'utilisateurs
        $teachers = User::factory()->count(100)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(500)->create(['role' => User::ROLE_STUDENT]);

        foreach ($teachers as $teacher) {
            $club->users()->attach($teacher->id, [
                'role' => 'teacher',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        foreach ($students as $student) {
            $club->users()->attach($student->id, [
                'role' => 'student',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        // Test de scalabilité
        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la performance reste acceptable
        $this->assertLessThan(3.0, $executionTime, 'Dashboard should load in less than 3 seconds with large dataset');
        
        $data = $response->json();
        $this->assertEquals(100, $data['stats']['total_teachers']);
        $this->assertEquals(500, $data['stats']['total_students']);
        $this->assertEquals(601, $data['stats']['total_members']); // 1 club user + 100 teachers + 500 students
    }
}
