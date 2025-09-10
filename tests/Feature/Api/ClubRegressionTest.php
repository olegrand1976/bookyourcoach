<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubRegressionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tests_club_creation_regression()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $clubData = [
            'name' => 'Club de Régression',
            'description' => 'Club pour les tests de régression',
            'address' => '123 Rue de Régression',
            'phone' => '01 23 45 67 89',
            'email' => 'regression@club.fr',
            'max_students' => 50,
            'subscription_price' => 100.00,
            'is_active' => true
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/clubs', $clubData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('clubs', [
            'name' => 'Club de Régression',
            'email' => 'regression@club.fr'
        ]);
    }

    #[Test]
    public function it_tests_club_user_association_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $this->assertTrue($clubUser->clubs()->exists());
        $this->assertTrue($club->users()->exists());
        $this->assertEquals('owner', $club->users()->where('user_id', $clubUser->id)->first()->pivot->role);
    }

    #[Test]
    public function it_tests_club_dashboard_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'club' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'phone',
                    'email',
                    'max_students',
                    'subscription_price',
                    'is_active'
                ],
                'stats' => [
                    'total_teachers',
                    'total_students',
                    'total_members',
                    'active_teachers',
                    'active_students',
                    'max_students',
                    'subscription_price',
                    'occupancy_rate'
                ],
                'recentTeachers',
                'recentStudents'
            ]);
    }

    #[Test]
    public function it_tests_club_teacher_management_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        // Ajouter l'enseignant
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => $teacher->email
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $teacher->id,
            'role' => 'teacher'
        ]);

        // Vérifier la liste des enseignants
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/teachers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at'
                    ]
                ],
                'links',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    #[Test]
    public function it_tests_club_student_management_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // Ajouter l'étudiant
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => $student->email
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $student->id,
            'role' => 'student'
        ]);

        // Vérifier la liste des étudiants
        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at'
                    ]
                ],
                'links',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    #[Test]
    public function it_tests_club_profile_update_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $updateData = [
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'modifie@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ];

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'modifie@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ]);
    }

    #[Test]
    public function it_tests_club_middleware_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // Test d'accès autorisé
        $this->actingAs($clubUser)->getJson('/api/club/dashboard')->assertStatus(200);

        // Test d'accès refusé
        $this->actingAs($student)->getJson('/api/club/dashboard')->assertStatus(403);

        // Test d'accès non authentifié
        $this->getJson('/api/club/dashboard')->assertStatus(403);
    }

    #[Test]
    public function it_tests_club_admin_regression()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();

        // Test de récupération des clubs
        $response = $this->actingAs($admin)
            ->getJson('/api/admin/clubs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'phone',
                        'email',
                        'max_students',
                        'subscription_price',
                        'is_active'
                    ]
                ]
            ]);

        // Test de mise à jour du club
        $updateData = [
            'name' => 'Club Admin Modifié',
            'description' => 'Description modifiée par admin',
            'address' => 'Nouvelle adresse admin',
            'phone' => '01 98 76 54 32',
            'email' => 'admin@club.fr',
            'max_students' => 200,
            'subscription_price' => 250.00,
            'is_active' => true
        ];

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clubs/{$club->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Admin Modifié',
            'description' => 'Description modifiée par admin',
            'address' => 'Nouvelle adresse admin',
            'phone' => '01 98 76 54 32',
            'email' => 'admin@club.fr',
            'max_students' => 200,
            'subscription_price' => 250.00
        ]);
    }

    #[Test]
    public function it_tests_club_data_consistency_regression()
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

        // Ajouter des utilisateurs
        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher->email])
            ->assertStatus(200);

        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $student->email])
            ->assertStatus(200);

        // Vérifier la cohérence des données
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(1, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(1, $dashboardData['stats']['total_students']);
        $this->assertEquals(3, $dashboardData['stats']['total_members']); // 1 club user + 1 teacher + 1 student

        // Vérifier les relations
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $teacher->id,
            'role' => 'teacher'
        ]);

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $student->id,
            'role' => 'student'
        ]);
    }

    #[Test]
    public function it_tests_club_performance_regression()
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
        
        // Vérifier que la performance reste acceptable
        $this->assertLessThan(2.0, $executionTime, 'Dashboard should load in less than 2 seconds');
    }

    #[Test]
    public function it_tests_club_security_regression()
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
        $this->assertArrayNotHasKey('email_verified_at', $data);
    }

    #[Test]
    public function it_tests_club_error_handling_regression()
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

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test d'utilisateur non trouvé
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'nonexistent@example.com'
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Utilisateur non trouvé']);
    }

    #[Test]
    public function it_tests_club_scalability_regression()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Créer un grand nombre d'utilisateurs
        $teachers = User::factory()->count(50)->create(['role' => User::ROLE_TEACHER]);
        $students = User::factory()->count(100)->create(['role' => User::ROLE_STUDENT]);

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
        $this->assertEquals(50, $data['stats']['total_teachers']);
        $this->assertEquals(100, $data['stats']['total_students']);
        $this->assertEquals(151, $data['stats']['total_members']); // 1 club user + 50 teachers + 100 students
    }
}
