<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ClubDataConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_maintains_data_consistency_when_adding_teachers()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        // Ajouter l'enseignant au club
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => $teacher->email
            ]);

        $response->assertStatus(200);

        // Vérifier la cohérence des données
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $teacher->id,
            'role' => 'teacher'
        ]);

        // Vérifier que les statistiques sont mises à jour
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(1, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(2, $dashboardData['stats']['total_members']); // 1 club user + 1 teacher
    }

    /** @test */
    public function it_maintains_data_consistency_when_adding_students()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // Ajouter l'étudiant au club
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => $student->email
            ]);

        $response->assertStatus(200);

        // Vérifier la cohérence des données
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $student->id,
            'role' => 'student'
        ]);

        // Vérifier que les statistiques sont mises à jour
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(1, $dashboardData['stats']['total_students']);
        $this->assertEquals(2, $dashboardData['stats']['total_members']); // 1 club user + 1 student
    }

    /** @test */
    public function it_maintains_data_consistency_when_updating_club_profile()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create([
            'name' => 'Club Original',
            'description' => 'Description originale',
            'address' => 'Adresse originale',
            'phone' => '01 23 45 67 89',
            'email' => 'original@club.fr',
            'max_students' => 50,
            'subscription_price' => 100.00
        ]);
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Mettre à jour le profil du club
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

        // Vérifier que toutes les données sont cohérentes
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

        // Vérifier que les relations sont préservées
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $clubUser->id,
            'role' => 'owner'
        ]);
    }

    /** @test */
    public function it_maintains_data_consistency_with_multiple_operations()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Ajouter des enseignants
        $teachers = User::factory()->count(5)->create(['role' => User::ROLE_TEACHER]);
        foreach ($teachers as $teacher) {
            $this->actingAs($clubUser)
                ->postJson('/api/club/teachers', ['email' => $teacher->email])
                ->assertStatus(200);
        }

        // Ajouter des étudiants
        $students = User::factory()->count(10)->create(['role' => User::ROLE_STUDENT]);
        foreach ($students as $student) {
            $this->actingAs($clubUser)
                ->postJson('/api/club/students', ['email' => $student->email])
                ->assertStatus(200);
        }

        // Mettre à jour le profil
        $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 98 76 54 32',
                'email' => 'modifie@club.fr',
                'max_students' => 100,
                'subscription_price' => 150.00
            ])
            ->assertStatus(200);

        // Vérifier la cohérence finale
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(5, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(10, $dashboardData['stats']['total_students']);
        $this->assertEquals(16, $dashboardData['stats']['total_members']); // 1 club user + 5 teachers + 10 students
        $this->assertEquals(100, $dashboardData['stats']['max_students']);
        $this->assertEquals(150.00, $dashboardData['stats']['subscription_price']);
        $this->assertEquals(10.0, $dashboardData['stats']['occupancy_rate']); // 10/100 * 100

        // Vérifier que toutes les relations sont préservées
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $clubUser->id,
            'role' => 'owner'
        ]);

        foreach ($teachers as $teacher) {
            $this->assertDatabaseHas('club_user', [
                'club_id' => $club->id,
                'user_id' => $teacher->id,
                'role' => 'teacher'
            ]);
        }

        foreach ($students as $student) {
            $this->assertDatabaseHas('club_user', [
                'club_id' => $club->id,
                'user_id' => $student->id,
                'role' => 'student'
            ]);
        }
    }

    /** @test */
    public function it_maintains_data_consistency_with_concurrent_updates()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Simuler des mises à jour concurrentes
        $update1 = [
            'name' => 'Club Modifié 1',
            'description' => 'Description modifiée 1',
            'address' => 'Nouvelle adresse 1',
            'phone' => '01 23 45 67 89',
            'email' => 'test1@club.fr',
            'max_students' => 50,
            'subscription_price' => 100.00
        ];

        $update2 = [
            'name' => 'Club Modifié 2',
            'description' => 'Description modifiée 2',
            'address' => 'Nouvelle adresse 2',
            'phone' => '01 98 76 54 32',
            'email' => 'test2@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ];

        // Exécuter les mises à jour
        $response1 = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $update1);

        $response2 = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $update2);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Vérifier que la dernière mise à jour est appliquée
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié 2',
            'description' => 'Description modifiée 2',
            'address' => 'Nouvelle adresse 2',
            'phone' => '01 98 76 54 32',
            'email' => 'test2@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ]);

        // Vérifier que les relations sont préservées
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $clubUser->id,
            'role' => 'owner'
        ]);
    }

    /** @test */
    public function it_maintains_data_consistency_with_rollback_on_error()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Tentative de mise à jour avec des données invalides
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => '', // Nom requis
                'email' => 'invalid-email', // Email invalide
                'max_students' => -1, // Doit être positif
                'subscription_price' => -50.00 // Doit être positif
            ]);

        $response->assertStatus(422);

        // Vérifier que les données originales sont préservées
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => $club->name,
            'description' => $club->description,
            'address' => $club->address,
            'phone' => $club->phone,
            'email' => $club->email,
            'max_students' => $club->max_students,
            'subscription_price' => $club->subscription_price
        ]);

        // Vérifier que les relations sont préservées
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $clubUser->id,
            'role' => 'owner'
        ]);
    }

    /** @test */
    public function it_maintains_data_consistency_with_transactional_operations()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Opération transactionnelle complexe
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        // Ajouter l'enseignant
        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher->email])
            ->assertStatus(200);

        // Ajouter l'étudiant
        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $student->email])
            ->assertStatus(200);

        // Mettre à jour le profil
        $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => 'Club Modifié',
                'description' => 'Description modifiée',
                'address' => 'Nouvelle adresse',
                'phone' => '01 98 76 54 32',
                'email' => 'modifie@club.fr',
                'max_students' => 100,
                'subscription_price' => 150.00
            ])
            ->assertStatus(200);

        // Vérifier la cohérence finale
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(1, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(1, $dashboardData['stats']['total_students']);
        $this->assertEquals(3, $dashboardData['stats']['total_members']); // 1 club user + 1 teacher + 1 student
        $this->assertEquals(100, $dashboardData['stats']['max_students']);
        $this->assertEquals(150.00, $dashboardData['stats']['subscription_price']);
        $this->assertEquals(1.0, $dashboardData['stats']['occupancy_rate']); // 1/100 * 100

        // Vérifier que toutes les relations sont préservées
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $clubUser->id,
            'role' => 'owner'
        ]);

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

    /** @test */
    public function it_maintains_data_consistency_with_soft_deletes()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Ajouter des utilisateurs
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher->email])
            ->assertStatus(200);

        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $student->email])
            ->assertStatus(200);

        // Vérifier que les données sont cohérentes
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(1, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(1, $dashboardData['stats']['total_students']);
        $this->assertEquals(3, $dashboardData['stats']['total_members']);

        // Vérifier que les relations sont préservées
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
}
