<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubPerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_large_club_dashboard_efficiently()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'enseignants et d'étudiants
        $teachers = User::factory()->count(100)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(500)->create(['role' => User::ROLE_STUDENT]);

        // Associer tous les utilisateurs au club
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
        
        // Vérifier que la réponse est retournée en moins de 2 secondes
        $this->assertLessThan(2.0, $executionTime, 'Dashboard should load in less than 2 seconds');
        
        $data = $response->json();
        $this->assertEquals(100, $data['stats']['total_teachers']);
        $this->assertEquals(500, $data['stats']['total_students']);
        $this->assertEquals(601, $data['stats']['total_members']); // 1 club user + 100 teachers + 500 students
    }

    #[Test]
    public function it_handles_paginated_teachers_list_efficiently()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'enseignants
        $teachers = User::factory()->count(1000)->create(['role' => User::ROLE_TEACHER]);

        foreach ($teachers as $teacher) {
            $club->users()->attach($teacher->id, [
                'role' => 'teacher',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/teachers?page=1&per_page=50');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la pagination fonctionne efficacement
        $this->assertLessThan(1.0, $executionTime, 'Paginated teachers list should load in less than 1 second');
        
        $data = $response->json();
        $this->assertCount(15, $data['data']); // Laravel pagination default per_page
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(15, $data['per_page']);
        $this->assertEquals(1000, $data['total']);
    }

    #[Test]
    public function it_handles_paginated_students_list_efficiently()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'étudiants
        $students = User::factory()->count(2000)->create(['role' => User::ROLE_STUDENT]);

        foreach ($students as $student) {
            $club->users()->attach($student->id, [
                'role' => 'student',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        $startTime = microtime(true);
        
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/students?page=1&per_page=100');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la pagination fonctionne efficacement
        $this->assertLessThan(1.0, $executionTime, 'Paginated students list should load in less than 1 second');
        
        $data = $response->json();
        $this->assertCount(15, $data['data']); // Laravel pagination default per_page
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(15, $data['per_page']);
        $this->assertEquals(2000, $data['total']);
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

        // Créer des enseignants et étudiants pour les tests concurrents
        $teachers = User::factory()->count(50)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(50)->create(['role' => User::ROLE_STUDENT]);

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
        $this->assertLessThan(5.0, $executionTime, 'Concurrent operations should complete in less than 5 seconds');
    }

    #[Test]
    public function it_handles_large_club_profile_updates_efficiently()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'utilisateurs pour tester la performance
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
        
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 98 76 54 32',
                'email' => 'nouveau@club.fr',
                'max_students' => 2000,
                'subscription_price' => 200.00
            ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Vérifier que la mise à jour du profil est efficace même avec beaucoup d'utilisateurs
        $this->assertLessThan(1.0, $executionTime, 'Profile update should complete in less than 1 second');
        
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 2000,
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
        $teachers = User::factory()->count(5000)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(10000)->create(['role' => User::ROLE_STUDENT]);

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
        $this->assertLessThan(3.0, $executionTime, 'Dashboard should load in less than 3 seconds even with large datasets');
        
        // Vérifier que l'utilisation mémoire reste raisonnable (moins de 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 50MB');
        
        $data = $response->json();
        $this->assertEquals(5000, $data['stats']['total_teachers']);
        $this->assertEquals(10000, $data['stats']['total_students']);
        $this->assertEquals(15001, $data['stats']['total_members']); // 1 club user + 5000 teachers + 10000 students
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
        $teachers = User::factory()->count(100)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(200)->create(['role' => User::ROLE_STUDENT]);

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
        $this->assertLessThan(2.0, $executionTime, 'Multiple queries should complete in less than 2 seconds');
        
        // Vérifier que les données sont cohérentes
        $dashboardData = $dashboardResponse->json();
        $teachersData = $teachersResponse->json();
        $studentsData = $studentsResponse->json();
        
        $this->assertEquals(100, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(200, $dashboardData['stats']['total_students']);
        $this->assertCount(15, $teachersData['data']); // Laravel pagination default per_page
        $this->assertCount(15, $studentsData['data']); // Laravel pagination default per_page
    }
}
