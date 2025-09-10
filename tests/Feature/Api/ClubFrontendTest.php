<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubFrontendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_access_club_dashboard_page()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->get('/club/dashboard');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_access_club_profile_page()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->get('/club/profile');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_redirects_unauthenticated_user_from_club_pages()
    {
        $response = $this->get('/club/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/club/profile');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_redirects_non_club_user_from_club_pages()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($student)
            ->get('/club/dashboard');

        $response->assertRedirect('/dashboard');

        $response = $this->actingAs($student)
            ->get('/club/profile');

        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_redirects_club_user_without_club_association()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        // Ne pas associer l'utilisateur à un club

        $response = $this->actingAs($clubUser)
            ->get('/club/dashboard');

        $response->assertRedirect('/dashboard');

        $response = $this->actingAs($clubUser)
            ->get('/club/profile');

        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_allows_admin_user_to_access_club_pages()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::factory()->create();
        
        $club->users()->attach($admin->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($admin)
            ->get('/club/dashboard');

        $response->assertStatus(200);

        $response = $this->actingAs($admin)
            ->get('/club/profile');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_displays_club_dashboard_with_correct_data()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create([
            'name' => 'Club de Test',
            'description' => 'Description du club',
            'address' => '123 Rue de Test',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@club-test.fr',
            'max_students' => 50,
            'subscription_price' => 100.00,
            'is_active' => true
        ]);
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Ajouter des enseignants et étudiants
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

        $response = $this->actingAs($clubUser)
            ->get('/club/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Club de Test');
        $response->assertSee('Description du club');
        $response->assertSee('123 Rue de Test');
        $response->assertSee('01 23 45 67 89');
        $response->assertSee('contact@club-test.fr');
        $response->assertSee('50');
        $response->assertSee('100.00');
    }

    #[Test]
    public function it_displays_club_profile_with_correct_data()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create([
            'name' => 'Club de Test',
            'description' => 'Description du club',
            'address' => '123 Rue de Test',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@club-test.fr',
            'max_students' => 50,
            'subscription_price' => 100.00,
            'is_active' => true
        ]);
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->get('/club/profile');

        $response->assertStatus(200);
        $response->assertSee('Club de Test');
        $response->assertSee('Description du club');
        $response->assertSee('123 Rue de Test');
        $response->assertSee('01 23 45 67 89');
        $response->assertSee('contact@club-test.fr');
        $response->assertSee('50');
        $response->assertSee('100.00');
    }

    #[Test]
    public function it_handles_club_dashboard_api_calls()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Simuler un appel API depuis le frontend
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
    public function it_handles_club_profile_update_from_frontend()
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
            'email' => 'nouveau@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ];

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profil du club mis à jour avec succès'
            ]);

        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'name' => 'Club Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 100,
            'subscription_price' => 150.00
        ]);
    }

    #[Test]
    public function it_handles_adding_teachers_from_frontend()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => $teacher->email
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Enseignant ajouté au club avec succès'
            ]);

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $teacher->id,
            'role' => 'teacher'
        ]);
    }

    #[Test]
    public function it_handles_adding_students_from_frontend()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => $student->email
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Élève ajouté au club avec succès'
            ]);

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $student->id,
            'role' => 'student'
        ]);
    }

    #[Test]
    public function it_handles_frontend_validation_errors()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test de validation pour l'ajout d'enseignant
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test de validation pour l'ajout d'étudiant
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test de validation pour la mise à jour du profil
        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => '', // Nom requis
                'email' => 'invalid-email',
                'max_students' => -1,
                'subscription_price' => -50.00
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_handles_frontend_error_messages()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        // Test d'erreur pour utilisateur non trouvé
        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'nonexistent@example.com'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ]);

        // Test d'erreur pour utilisateur déjà dans le club
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => $teacher->email
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cet utilisateur est déjà membre du club'
            ]);
    }
}
