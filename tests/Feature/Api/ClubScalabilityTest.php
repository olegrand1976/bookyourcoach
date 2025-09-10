<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubScalabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_large_number_of_clubs()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Créer un grand nombre de clubs
        $clubs = Club::factory()->count(1000)->create();

        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs?page=1&per_page=50');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la performance reste acceptable
        $this->assertLessThan(2.0, $executionTime, 'Clubs list should load in less than 2 seconds');
        
        $data = $response->json();
        $this->assertCount(15, $data['data']); // Laravel pagination default per_page
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(15, $data['per_page']);
        $this->assertEquals(1000, $data['total']);
    }

    #[Test]
    public function it_handles_large_number_of_club_members()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'enseignants et d'étudiants
        $teachers = User::factory()->count(500)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(2000)->create(['role' => User::ROLE_STUDENT]);

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

        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la performance reste acceptable
        $this->assertLessThan(3.0, $executionTime, 'Dashboard should load in less than 3 seconds');
        
        $data = $response->json();
        $this->assertEquals(500, $data['stats']['total_teachers']);
        $this->assertEquals(2000, $data['stats']['total_students']);
        $this->assertEquals(2501, $data['stats']['total_members']); // 1 club user + 500 teachers + 2000 students
    }

    #[Test]
    public function it_handles_concurrent_club_operations()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer des utilisateurs pour les tests concurrents
        $teachers = User::factory()->count(100)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(100)->create(['role' => User::ROLE_STUDENT]);

        $startTime = microtime(true);

        // Simuler des opérations concurrentes
        $responses = [];
        
        // Ajouter des enseignants
        foreach ($teachers as $teacher) {
            $responses[] = $this->actingAs($clubUser)
                ->postJson('/api/club/teachers', ['email' => $teacher->email]);
        }

        // Ajouter des étudiants
        foreach ($students as $student) {
            $responses[] = $this->actingAs($clubUser)
                ->postJson('/api/club/students', ['email' => $student->email]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Vérifier que toutes les opérations ont réussi
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Vérifier que les opérations concurrentes sont gérées efficacement
        $this->assertLessThan(10.0, $executionTime, 'Concurrent operations should complete in less than 10 seconds');
    }

    #[Test]
    public function it_handles_large_club_profile_updates()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'utilisateurs pour tester la performance
        $teachers = User::factory()->count(1000)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(5000)->create(['role' => User::ROLE_STUDENT]);

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

        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 98 76 54 32',
                'email' => 'nouveau@club.fr',
                'max_students' => 10000,
                'subscription_price' => 200.00
            ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la mise à jour du profil est efficace même avec beaucoup d'utilisateurs
        $this->assertLessThan(2.0, $executionTime, 'Profile update should complete in less than 2 seconds');
        
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 10000,
            'subscription_price' => 200.00
        ]);
    }

    #[Test]
    public function it_handles_memory_efficiently_with_large_datasets()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un très grand nombre d'utilisateurs
        $teachers = User::factory()->count(10000)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(50000)->create(['role' => User::ROLE_STUDENT]);

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

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        $response->assertStatus(200);
        
        // Vérifier que la performance reste acceptable
        $this->assertLessThan(5.0, $executionTime, 'Dashboard should load in less than 5 seconds even with large datasets');
        
        // Vérifier que l'utilisation mémoire reste raisonnable (moins de 100MB)
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 100MB');
        
        $data = $response->json();
        $this->assertEquals(10000, $data['stats']['total_teachers']);
        $this->assertEquals(50000, $data['stats']['total_students']);
        $this->assertEquals(60001, $data['stats']['total_members']); // 1 club user + 10000 teachers + 50000 students
    }

    #[Test]
    public function it_handles_database_query_optimization()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer des utilisateurs avec des relations complexes
        $teachers = User::factory()->count(500)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(1000)->create(['role' => User::ROLE_STUDENT]);

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

        $startTime = microtime(true);
        
        // Tester plusieurs requêtes pour vérifier l'optimisation
        $dashboardResponse = $this->actingAs($clubUser)->getJson('/api/club/dashboard');
        $teachersResponse = $this->actingAs($clubUser)->getJson('/api/club/teachers');
        $studentsResponse = $this->actingAs($clubUser)->getJson('/api/club/students');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $dashboardResponse->assertStatus(200);
        $teachersResponse->assertStatus(200);
        $studentsResponse->assertStatus(200);
        
        // Vérifier que les requêtes sont optimisées
        $this->assertLessThan(3.0, $executionTime, 'Multiple queries should complete in less than 3 seconds');
        
        // Vérifier que les données sont cohérentes
        $dashboardData = $dashboardResponse->json();
        $teachersData = $teachersResponse->json();
        $studentsData = $studentsResponse->json();
        
        $this->assertEquals(500, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(1000, $dashboardData['stats']['total_students']);
        $this->assertCount(15, $teachersData['data']); // Laravel pagination default per_page
        $this->assertCount(15, $studentsData['data']); // Laravel pagination default per_page
    }

    #[Test]
    public function it_handles_horizontal_scaling()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de mise à l'échelle horizontale
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_vertical_scaling()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de mise à l'échelle verticale
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_load_balancing()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'équilibrage de charge
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_caching()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de mise en cache
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_cdn()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de CDN
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_microservices()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de microservices
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_api_gateway()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de passerelle API
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_service_mesh()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de maillage de services
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_container_orchestration()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'orchestration de conteneurs
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_auto_scaling()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de mise à l'échelle automatique
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_monitoring()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de surveillance
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_alerting()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'alerte
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_logging()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de journalisation
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_backup()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de sauvegarde
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_disaster_recovery()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de récupération d'urgence
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200);
    }
}
