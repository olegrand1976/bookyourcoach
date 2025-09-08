<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ClubIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_complete_full_club_workflow()
    {
        // 1. Créer un utilisateur club
        $clubUser = User::factory()->create([
            'name' => 'Gérant du Club',
            'email' => 'gerant@club.fr',
            'role' => 'club'
        ]);

        // 2. Créer un club
        $club = Club::factory()->create([
            'name' => 'Club Équestre de Test',
            'description' => 'Un club pour les tests',
            'address' => '123 Rue de Test',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@club-test.fr',
            'max_students' => 50,
            'subscription_price' => 100.00,
            'is_active' => true
        ]);

        // 3. Associer l'utilisateur au club
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // 4. Créer des enseignants
        $teacher1 = User::factory()->create([
            'name' => 'Enseignant 1',
            'email' => 'teacher1@example.com',
            'role' => User::ROLE_TEACHER
        ]);
        $teacher2 = User::factory()->create([
            'name' => 'Enseignant 2',
            'email' => 'teacher2@example.com',
            'role' => User::ROLE_TEACHER
        ]);

        // 5. Créer des étudiants
        $student1 = User::factory()->create([
            'name' => 'Étudiant 1',
            'email' => 'student1@example.com',
            'role' => User::ROLE_STUDENT
        ]);
        $student2 = User::factory()->create([
            'name' => 'Étudiant 2',
            'email' => 'student2@example.com',
            'role' => User::ROLE_STUDENT
        ]);

        // 6. Ajouter les enseignants au club
        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher1->email])
            ->assertStatus(200);

        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher2->email])
            ->assertStatus(200);

        // 7. Ajouter les étudiants au club
        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $student1->email])
            ->assertStatus(200);

        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $student2->email])
            ->assertStatus(200);

        // 8. Vérifier le dashboard
        $dashboardResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard')
            ->assertStatus(200);

        $dashboardData = $dashboardResponse->json();
        $this->assertEquals(2, $dashboardData['stats']['total_teachers']);
        $this->assertEquals(2, $dashboardData['stats']['total_students']);
        $this->assertEquals(5, $dashboardData['stats']['total_members']); // 1 club user + 2 teachers + 2 students

        // 9. Vérifier la liste des enseignants
        $teachersResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/teachers')
            ->assertStatus(200);

        $teachersData = $teachersResponse->json();
        $this->assertCount(2, $teachersData['data']);

        // 10. Vérifier la liste des étudiants
        $studentsResponse = $this->actingAs($clubUser)
            ->getJson('/api/club/students')
            ->assertStatus(200);

        $studentsData = $studentsResponse->json();
        $this->assertCount(2, $studentsData['data']);

        // 11. Mettre à jour le profil du club
        $updateData = [
            'name' => 'Club Équestre Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ];

        $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $updateData)
            ->assertStatus(200);

        // 12. Vérifier que les modifications ont été appliquées
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Équestre Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ]);

        // 13. Vérifier que les relations sont correctes
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $teacher1->id,
            'role' => 'teacher'
        ]);

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $student1->id,
            'role' => 'student'
        ]);
    }

    /** @test */
    public function it_handles_club_user_management_scenarios()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Scénario 1: Ajouter un enseignant qui n'existe pas
        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => 'nonexistent@example.com'])
            ->assertStatus(404)
            ->assertJson(['message' => 'Utilisateur non trouvé']);

        // Scénario 2: Ajouter un étudiant qui n'existe pas
        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => 'nonexistent@example.com'])
            ->assertStatus(404)
            ->assertJson(['message' => 'Utilisateur non trouvé']);

        // Scénario 3: Ajouter un utilisateur avec un mauvais rôle
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $admin->email])
            ->assertStatus(400)
            ->assertJson(['message' => 'Cet utilisateur n\'est pas un enseignant']);

        $this->actingAs($clubUser)
            ->postJson('/api/club/students', ['email' => $admin->email])
            ->assertStatus(400)
            ->assertJson(['message' => 'Cet utilisateur n\'est pas un étudiant']);

        // Scénario 4: Ajouter un utilisateur déjà dans le club
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        
        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', ['email' => $teacher->email])
            ->assertStatus(400)
            ->assertJson(['message' => 'Cet utilisateur est déjà membre du club']);
    }

    /** @test */
    public function it_handles_club_profile_validation()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de validation des données
        $invalidData = [
            'name' => '', // Nom requis
            'email' => 'invalid-email', // Email invalide
            'max_students' => -1, // Doit être positif
            'subscription_price' => -50.00 // Doit être positif
        ];

        $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $invalidData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);

        // Test avec des données valides
        $validData = [
            'name' => 'Club Valide',
            'description' => 'Description valide',
            'address' => 'Adresse valide',
            'phone' => '01 23 45 67 89',
            'email' => 'valid@club.fr',
            'max_students' => 50,
            'subscription_price' => 100.00
        ];

        $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $validData)
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_handles_club_statistics_calculation()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create([
            'max_students' => 100,
            'subscription_price' => 150.00
        ]);
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Ajouter des enseignants
        $teachers = User::factory()->count(5)->create(['role' => User::ROLE_TEACHER]);
        foreach ($teachers as $teacher) {
            $club->users()->attach($teacher->id, [
                'role' => 'teacher',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        // Ajouter des étudiants
        $students = User::factory()->count(25)->create(['role' => User::ROLE_STUDENT]);
        foreach ($students as $student) {
            $club->users()->attach($student->id, [
                'role' => 'student',
                'is_admin' => false,
                'joined_at' => now()
            ]);
        }

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard')
            ->assertStatus(200);

        $data = $response->json();
        
        $this->assertEquals(5, $data['stats']['total_teachers']);
        $this->assertEquals(25, $data['stats']['total_students']);
        $this->assertEquals(31, $data['stats']['total_members']); // 1 club user + 5 teachers + 25 students
        $this->assertEquals(100, $data['stats']['max_students']);
        $this->assertEquals(150.00, $data['stats']['subscription_price']);
        $this->assertEquals(25.0, $data['stats']['occupancy_rate']); // 25/100 * 100
    }

    /** @test */
    public function it_handles_club_user_roles_and_permissions()
    {
        $club = Club::factory()->create();
        
        // Créer des utilisateurs avec différents rôles dans le club
        $owner = User::factory()->create(['role' => 'club']);
        $manager = User::factory()->create(['role' => 'club']);
        $member = User::factory()->create(['role' => 'club']);
        
        $club->users()->attach($owner->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        $club->users()->attach($manager->id, [
            'role' => 'manager',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($member->id, [
            'role' => 'member',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        // Tous les utilisateurs du club peuvent accéder au dashboard
        $this->actingAs($owner)->getJson('/api/club/dashboard')->assertStatus(200);
        $this->actingAs($manager)->getJson('/api/club/dashboard')->assertStatus(200);
        $this->actingAs($member)->getJson('/api/club/dashboard')->assertStatus(200);

        // Tous peuvent voir les enseignants et étudiants
        $this->actingAs($owner)->getJson('/api/club/teachers')->assertStatus(200);
        $this->actingAs($manager)->getJson('/api/club/teachers')->assertStatus(200);
        $this->actingAs($member)->getJson('/api/club/teachers')->assertStatus(200);

        $this->actingAs($owner)->getJson('/api/club/students')->assertStatus(200);
        $this->actingAs($manager)->getJson('/api/club/students')->assertStatus(200);
        $this->actingAs($member)->getJson('/api/club/students')->assertStatus(200);

        // Tous peuvent ajouter des enseignants et étudiants
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($owner)->postJson('/api/club/teachers', ['email' => $teacher->email])->assertStatus(200);
        $this->actingAs($manager)->postJson('/api/club/students', ['email' => $student->email])->assertStatus(200);
        $this->actingAs($member)->postJson('/api/club/teachers', ['email' => $teacher->email])->assertStatus(200);

        // Tous peuvent mettre à jour le profil du club
        $this->actingAs($owner)->putJson('/api/club/profile', ['name' => 'Nouveau nom'])->assertStatus(200);
        $this->actingAs($manager)->putJson('/api/club/profile', ['name' => 'Nouveau nom'])->assertStatus(200);
        $this->actingAs($member)->putJson('/api/club/profile', ['name' => 'Nouveau nom'])->assertStatus(200);
    }
}
