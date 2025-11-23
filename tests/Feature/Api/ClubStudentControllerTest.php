<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Student;
use App\Models\Discipline;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class ClubStudentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_student()
    {
        // Arrange
        Notification::fake();
        
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $discipline = Discipline::factory()->create();

        $studentData = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '+33123456789',
            'date_of_birth' => '2010-05-15',
            'level' => 'debutant',
            'goals' => 'Apprendre les bases',
            'disciplines' => [$discipline->id],
        ];

        // Act
        $response = $this->postJson('/api/club/students', $studentData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'user_id',
                         'club_id',
                     ],
                     'student',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.doe@example.com',
            'role' => 'student',
        ]);

        $student = Student::whereHas('user', function($q) {
            $q->where('email', 'jane.doe@example.com');
        })->first();

        $this->assertEquals($club->id, $student->club_id);
        $this->assertTrue($student->disciplines->contains($discipline));
        
        Notification::assertSentTo($student->user, \App\Notifications\StudentWelcomeNotification::class);
    }

    #[Test]
    public function it_validates_student_creation_data()
    {
        // Arrange
        $user = $this->actingAsClub();

        // Act
        $response = $this->postJson('/api/club/students', [
            'email' => 'invalid-email', // Email invalide
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
    }

    #[Test]
    public function it_can_update_student()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $student = Student::factory()->create(['club_id' => $club->id]);
        
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updateData = [
            'level' => 'intermediaire',
            'goals' => 'Progresser rapidement',
        ];

        // Act
        $response = $this->putJson("/api/club/students/{$student->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'level' => 'intermediaire',
        ]);
    }

    #[Test]
    public function it_can_delete_student()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $student = Student::factory()->create(['club_id' => $club->id]);
        
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this->deleteJson("/api/club/students/{$student->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        // Vérifier que l'association est désactivée
        $this->assertDatabaseHas('club_students', [
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_can_resend_student_invitation()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        // Créer un utilisateur pour l'élève (nécessaire pour l'invitation)
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'student@example.com',
        ]);
        
        $student = Student::factory()->create([
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);
        
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Notification::fake();

        // Act
        $response = $this->postJson("/api/club/students/{$student->id}/resend-invitation");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        Notification::assertSentTo($user, \App\Notifications\StudentWelcomeNotification::class);
    }

    #[Test]
    public function it_cannot_access_students_from_other_club()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        $otherClub = Club::factory()->create();
        $student = Student::factory()->create(['club_id' => $otherClub->id]);

        // Act
        $response = $this->putJson("/api/club/students/{$student->id}", [
            'level' => 'avance',
        ]);

        // Assert
        $response->assertStatus(404); // Not found car l'étudiant n'appartient pas au club
    }

    #[Test]
    public function it_requires_club_role_to_create_student()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->postJson('/api/club/students', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(403);
    }
}

