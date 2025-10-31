<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\LessonReplacement;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_teacher_dashboard()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $club = Club::factory()->create();
        
        // Associer le teacher au club
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'joined_at' => now()
        ]);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $student = Student::factory()->create();

        // Créer des cours aujourd'hui
        Lesson::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->setTime(10, 0),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer des cours cette semaine
        Lesson::factory()->count(5)->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->startOfWeek()->addDays(1)->setTime(14, 0),
            'status' => 'completed',
            'price' => 50.00,
        ]);

        // Créer des cours futurs
        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
            'status' => 'confirmed',
        ]);

        // Act
        $response = $this->getJson('/api/teacher/dashboard');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'stats' => [
                             'today_lessons',
                             'total_lessons',
                             'active_students',
                             'weekly_lessons',
                             'week_earnings',
                             'week_hours',
                             'monthly_earnings',
                             'pending_replacements',
                         ],
                         'upcoming_lessons',
                         'recent_lessons',
                         'clubs',
                         'teacher',
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['stats']['today_lessons']);
        $this->assertGreaterThanOrEqual(5, $data['stats']['total_lessons']);
        $this->assertIsFloat($data['stats']['week_earnings']);
    }

    #[Test]
    public function it_returns_404_if_teacher_profile_not_found()
    {
        // Arrange
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/teacher/dashboard');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Profil enseignant introuvable'
                 ]);
    }

    #[Test]
    public function it_requires_authentication_to_get_dashboard()
    {
        // Act
        $response = $this->getJson('/api/teacher/dashboard');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_get_teacher_dashboard_simple()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $student = Student::factory()->create();

        // Créer des cours
        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        Lesson::factory()->count(2)->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->startOfWeek()->addDays(1),
            'status' => 'completed',
            'price' => 50.00,
        ]);

        // Act
        $response = $this->getJson('/api/teacher/dashboard-simple');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'stats' => [
                         'today_lessons',
                         'active_students',
                         'week_earnings',
                     ]
                 ]);

        $stats = $response->json('stats');
        $this->assertIsInt($stats['today_lessons']);
        $this->assertIsInt($stats['active_students']);
        $this->assertIsFloat($stats['week_earnings']);
    }

    #[Test]
    public function it_can_get_teacher_profile()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        $club = Club::factory()->create();
        
        $teacher->clubs()->attach($club->id, [
            'is_active' => true,
            'joined_at' => now()
        ]);

        // Act
        $response = $this->getJson('/api/teacher/profile');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'profile' => [
                         'id',
                         'name',
                         'email',
                     ],
                     'teacher' => [
                         'id',
                         'user_id',
                         'clubs',
                     ]
                 ]);

        $this->assertEquals($user->id, $response->json('profile.id'));
        $this->assertEquals($teacher->id, $response->json('teacher.id'));
    }

    #[Test]
    public function it_can_update_teacher_profile()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;

        $updateData = [
            'name' => 'Nouveau Nom',
            'phone' => '+33123456789',
            'bio' => 'Nouvelle bio',
            'experience_years' => 10,
            'hourly_rate' => 60.00,
            'specialties' => ['dressage', 'obstacle'],
        ];

        // Act
        $response = $this->putJson('/api/teacher/profile', $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'profile',
                     'teacher',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
            'phone' => '+33123456789',
        ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'bio' => 'Nouvelle bio',
            'experience_years' => 10,
            'hourly_rate' => 60.00,
        ]);
    }

    #[Test]
    public function it_validates_profile_update_data()
    {
        // Arrange
        $user = $this->actingAsTeacher();

        // Act
        $response = $this->putJson('/api/teacher/profile', [
            'experience_years' => -1, // Invalide
            'hourly_rate' => -10, // Invalide
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['experience_years', 'hourly_rate']);
    }

    #[Test]
    public function it_can_list_other_teachers_from_same_clubs()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();
        
        // Associer le teacher aux clubs
        $teacher->clubs()->attach([$club1->id, $club2->id], [
            'is_active' => true,
            'joined_at' => now()
        ]);

        // Créer d'autres enseignants dans les mêmes clubs
        $otherTeacher1 = Teacher::factory()->create();
        $otherTeacher1->clubs()->attach($club1->id, ['is_active' => true]);
        
        $otherTeacher2 = Teacher::factory()->create();
        $otherTeacher2->clubs()->attach($club2->id, ['is_active' => true]);
        
        // Enseignant dans un autre club (ne doit pas apparaître)
        $otherTeacher3 = Teacher::factory()->create();
        $otherClub = Club::factory()->create();
        $otherTeacher3->clubs()->attach($otherClub->id, ['is_active' => true]);

        // Act
        $response = $this->getJson('/api/teacher/teachers');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);

        $teachers = $response->json('data');
        $teacherIds = collect($teachers)->pluck('id')->toArray();
        
        $this->assertContains($otherTeacher1->id, $teacherIds);
        $this->assertContains($otherTeacher2->id, $teacherIds);
        $this->assertNotContains($teacher->id, $teacherIds); // L'enseignant actuel ne doit pas apparaître
        $this->assertNotContains($otherTeacher3->id, $teacherIds); // L'enseignant d'un autre club ne doit pas apparaître
    }

    #[Test]
    public function it_can_get_students_from_clubs()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();
        
        $teacher->clubs()->attach([$club1->id, $club2->id], [
            'is_active' => true,
            'joined_at' => now()
        ]);

        // Créer des étudiants dans les clubs
        $student1 = Student::factory()->create(['club_id' => $club1->id]);
        $student2 = Student::factory()->create(['club_id' => $club2->id]);
        
        // Étudiant dans un autre club (ne doit pas apparaître)
        $otherClub = Club::factory()->create();
        $student3 = Student::factory()->create(['club_id' => $otherClub->id]);

        // Act
        $response = $this->getJson('/api/teacher/students');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'students' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'level',
                             'club_id',
                         ]
                     ]
                 ]);

        $students = $response->json('students');
        $studentIds = collect($students)->pluck('id')->toArray();
        
        $this->assertContains($student1->id, $studentIds);
        $this->assertContains($student2->id, $studentIds);
        $this->assertNotContains($student3->id, $studentIds);
    }

    #[Test]
    public function it_can_get_teacher_clubs()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $club1 = Club::factory()->create(['name' => 'Club 1']);
        $club2 = Club::factory()->create(['name' => 'Club 2']);
        
        $teacher->clubs()->attach([$club1->id, $club2->id], [
            'is_active' => true,
            'joined_at' => now()
        ]);

        // Act
        $response = $this->getJson('/api/teacher/clubs');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'clubs',
                 ]);

        $clubs = $response->json('clubs');
        $clubIds = collect($clubs)->pluck('id')->toArray();
        
        $this->assertContains($club1->id, $clubIds);
        $this->assertContains($club2->id, $clubIds);
        $this->assertCount(2, $clubs);
    }

    #[Test]
    public function it_includes_pending_replacements_in_dashboard()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $lesson = Lesson::factory()->create(['teacher_id' => $teacher->id]);
        
        LessonReplacement::factory()->create([
            'lesson_id' => $lesson->id,
            'original_teacher_id' => $teacher->id,
            'replacement_teacher_id' => $otherTeacher->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->getJson('/api/teacher/dashboard');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['stats']['pending_replacements']);
    }

    #[Test]
    public function it_can_list_own_lessons()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $otherTeacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $student = Student::factory()->create();

        // Créer des cours pour ce teacher
        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Créer des cours pour un autre teacher (ne doivent pas apparaître)
        Lesson::factory()->count(2)->create([
            'teacher_id' => $otherTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/teacher/lessons');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'teacher_id',
                         ]
                     ]
                 ]);

        $lessons = $response->json('data');
        $this->assertCount(3, $lessons);
        $this->assertEquals($teacher->id, $lessons[0]['teacher_id']);
    }

    #[Test]
    public function it_can_create_lesson_as_teacher()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lessonData = [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'status' => 'confirmed',
            'price' => 45.00,
        ];

        // Act
        $response = $this->postJson('/api/teacher/lessons', $lessonData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'teacher_id',
                         'student_id',
                     ]
                 ]);

        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'status' => 'confirmed',
        ]);
    }

    #[Test]
    public function it_can_delete_own_lesson()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        $teacher = $user->teacher;
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->deleteJson("/api/teacher/lessons/{$lesson->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);
    }

    #[Test]
    public function it_requires_teacher_role_to_access_endpoints()
    {
        // Arrange
        $clubUser = $this->actingAsClub();

        // Act
        $response = $this->getJson('/api/teacher/dashboard');

        // Assert
        $response->assertStatus(403); // Forbidden car le middleware teacher vérifie le rôle
    }
}

