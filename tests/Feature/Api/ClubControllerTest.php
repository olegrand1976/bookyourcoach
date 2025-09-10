<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;


class ClubControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_club_dashboard_data()
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
                    'occupancy_rate',
                    'total_lessons',
                    'completed_lessons',
                    'pending_lessons',
                    'confirmed_lessons',
                    'cancelled_lessons',
                    'total_revenue',
                    'monthly_revenue',
                    'average_lesson_price'
                ],
                'recentTeachers',
                'recentStudents',
                'recentLessons'
            ]);
    }

    #[Test]
    public function it_can_get_club_teachers()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $teacher1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacher2 = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $club->users()->attach($teacher1->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($teacher2->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/teachers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    #[Test]
    public function it_can_get_club_students()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student1 = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $student2 = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club->users()->attach($student1->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($student2->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    #[Test]
    public function it_can_add_teacher_to_club()
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
    public function it_can_add_student_to_club()
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
    public function it_can_update_club_profile()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $updateData = [
            'name' => 'Club Équestre Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 150,
            'subscription_price' => 200.00
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
            'name' => 'Club Équestre Modifié',
            'description' => 'Description modifiée',
            'address' => 'Nouvelle adresse',
            'phone' => '01 98 76 54 32',
            'email' => 'nouveau@club.fr',
            'max_students' => 150,
            'subscription_price' => 200.00
        ]);
    }

    #[Test]
    public function it_validates_teacher_email_when_adding()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_validates_student_email_when_adding()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_validates_club_profile_update_data()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->putJson('/api/club/profile', [
                'name' => '', // Nom requis
                'email' => 'invalid-email',
                'max_students' => -1, // Doit être positif
                'subscription_price' => -50.00 // Doit être positif
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'max_students', 'subscription_price']);
    }

    #[Test]
    public function it_requires_club_role_to_access_dashboard()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_club_role_to_access_teachers()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->getJson('/api/club/teachers');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_club_role_to_access_students()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->getJson('/api/club/students');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_club_role_to_add_teacher()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->postJson('/api/club/teachers', [
                'email' => 'teacher@example.com'
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_club_role_to_add_student()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->postJson('/api/club/students', [
                'email' => 'student@example.com'
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_club_role_to_update_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $response = $this->actingAs($user)
            ->putJson('/api/club/profile', [
                'name' => 'Nouveau nom'
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_authentication_for_all_club_routes()
    {
        $response = $this->getJson('/api/club/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/club/teachers');
        $response->assertStatus(401);

        $response = $this->getJson('/api/club/students');
        $response->assertStatus(401);

        $response = $this->postJson('/api/club/teachers', ['email' => 'test@example.com']);
        $response->assertStatus(401);

        $response = $this->postJson('/api/club/students', ['email' => 'test@example.com']);
        $response->assertStatus(401);

        $response = $this->putJson('/api/club/profile', ['name' => 'Test']);
        $response->assertStatus(401);
    }

    #[Test]
    public function it_handles_nonexistent_teacher_email()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/teachers', [
                'email' => 'nonexistent@example.com'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ]);
    }

    #[Test]
    public function it_handles_nonexistent_student_email()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => 'nonexistent@example.com'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ]);
    }

    #[Test]
    public function it_handles_teacher_already_in_club()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

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

    #[Test]
    public function it_handles_student_already_in_club()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        
        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $response = $this->actingAs($clubUser)
            ->postJson('/api/club/students', [
                'email' => $student->email
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cet utilisateur est déjà membre du club'
            ]);
    }

    #[Test]
    public function it_handles_user_not_associated_with_club()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        // Ne pas associer l'utilisateur à un club

        $response = $this->actingAs($clubUser)
            ->getJson('/api/club/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'User is not associated with any club.'
            ]);
    }
}
