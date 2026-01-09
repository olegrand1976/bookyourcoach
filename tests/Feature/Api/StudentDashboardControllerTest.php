<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class StudentDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_student_stats()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        $club = Club::factory()->create();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        // Créer des cours disponibles
        Lesson::factory()->count(5)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Créer des réservations confirmées à venir
        Lesson::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(2),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Créer des cours terminés
        Lesson::factory()->count(7)->create([
            'student_id' => $student->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(5),
            'end_time' => Carbon::now()->subDays(5)->addHours(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/student/dashboard/stats');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'availableLessons',
                         'activeBookings',
                         'completedLessons',
                         'favoriteTeachers',
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['availableLessons']);
        $this->assertEquals(3, $data['activeBookings']);
        $this->assertEquals(7, $data['completedLessons']);
        $this->assertIsInt($data['favoriteTeachers']);
    }

    #[Test]
    public function it_returns_404_if_student_profile_not_found()
    {
        // Arrange
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/student/dashboard/stats');

        // Assert
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Profil étudiant non trouvé.'
                 ]);
    }

    #[Test]
    public function it_requires_authentication_to_get_stats()
    {
        // Act
        $response = $this->getJson('/api/student/dashboard/stats');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_get_available_lessons()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(5)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        Lesson::factory()->count(2)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->subDays(1), // Passé
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/student/available-lessons');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'status',
                             'start_time',
                             'teacher',
                             'course_type',
                             'location',
                         ]
                     ]
                 ]);

        $lessons = $response->json('data');
        $this->assertCount(5, $lessons); // Seulement les cours futurs
        $this->assertEquals('available', $lessons[0]['status']);
    }

    #[Test]
    public function it_can_filter_available_lessons_by_discipline()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $discipline1 = \App\Models\Discipline::factory()->create();
        $discipline2 = \App\Models\Discipline::factory()->create();
        
        $courseType1 = CourseType::factory()->create(['discipline_id' => $discipline1->id]);
        $courseType2 = CourseType::factory()->create(['discipline_id' => $discipline2->id]);
        $location = Location::factory()->create();

        Lesson::factory()->count(3)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType1->id,
            'location_id' => $location->id,
        ]);

        Lesson::factory()->count(2)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType2->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson("/api/student/available-lessons?discipline={$discipline1->id}");

        // Assert
        $response->assertStatus(200);
        $lessons = $response->json('data');
        $this->assertCount(3, $lessons);
        $this->assertEquals($courseType1->id, $lessons[0]['course_type']['id']);
    }

    #[Test]
    public function it_can_filter_available_lessons_by_date()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $targetDate = Carbon::now()->addDays(5)->format('Y-m-d');
        
        Lesson::factory()->count(2)->create([
            'status' => 'available',
            'start_time' => Carbon::parse($targetDate)->setTime(10, 0),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        Lesson::factory()->count(3)->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(10),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson("/api/student/available-lessons?date={$targetDate}");

        // Assert
        $response->assertStatus(200);
        $lessons = $response->json('data');
        $this->assertCount(2, $lessons);
    }

    #[Test]
    public function it_can_get_student_bookings()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        Lesson::factory()->count(2)->create([
            'student_id' => $student->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(5),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/student/bookings');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'student_id',
                             'status',
                             'start_time',
                         ]
                     ]
                 ]);

        $bookings = $response->json('data');
        $this->assertCount(5, $bookings);
    }

    #[Test]
    public function it_can_filter_bookings_by_status()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        Lesson::factory()->count(2)->create([
            'student_id' => $student->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(5),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/student/bookings?status=confirmed');

        // Assert
        $response->assertStatus(200);
        $bookings = $response->json('data');
        $this->assertCount(3, $bookings);
        $this->assertEquals('confirmed', $bookings[0]['status']);
    }

    #[Test]
    public function it_can_create_booking()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'price' => 50.00,
        ]);

        // Act
        $response = $this->postJson('/api/student/bookings', [
            'lesson_id' => $lesson->id,
            'notes' => 'Cours de test',
        ]);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'student_id',
                         'status',
                     ],
                     'message'
                 ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'student_id' => $user->student->id,
            'status' => 'confirmed',
        ]);
    }

    #[Test]
    public function it_cannot_book_unavailable_lesson()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'status' => 'confirmed', // Déjà réservé
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->postJson('/api/student/bookings', [
            'lesson_id' => $lesson->id,
        ]);

        // Assert
        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Ce cours n\'est pas disponible.'
                 ]);
    }

    #[Test]
    public function it_returns_402_and_payment_required_if_no_subscription_credit()
    {
        // Arrange
        $user = $this->actingAsStudent();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'status' => 'available',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'price' => 50.00,
        ]);

        // Act
        $response = $this->postJson('/api/student/bookings', [
            'lesson_id' => $lesson->id,
            'notes' => 'Tentative sans crédit',
        ]);

        // Assert
        $response->assertStatus(402)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Aucun crédit disponible pour ce cours. Veuillez payer la séance ou souscrire à un abonnement.',
                     'code' => 'PAYMENT_REQUIRED',
                 ]);
    }

    #[Test]
    public function it_validates_lesson_id_on_booking()
    {
        // Arrange
        $user = $this->actingAsStudent();

        // Act
        $response = $this->postJson('/api/student/bookings', [
            'lesson_id' => 99999, // ID inexistant
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['lesson_id']);
    }

    #[Test]
    public function it_can_cancel_booking()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'student_id' => $student->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->putJson("/api/student/bookings/{$lesson->id}/cancel");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Réservation annulée avec succès'
                 ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function it_cannot_cancel_other_students_booking()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $otherStudent = Student::factory()->create();
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'student_id' => $otherStudent->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->putJson("/api/student/bookings/{$lesson->id}/cancel");

        // Assert
        $response->assertStatus(404); // Not found car la leçon n'appartient pas à l'étudiant
    }

    #[Test]
    public function it_can_get_lesson_history()
    {
        // Arrange
        $user = $this->actingAsStudent();
        $student = $user->student;
        
        $teacher = Teacher::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(5)->create([
            'student_id' => $student->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(10),
            'end_time' => Carbon::now()->subDays(10)->addHours(1),
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);

        // Act
        $response = $this->getJson('/api/student/lesson-history');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'status',
                             'start_time',
                             'teacher',
                             'course_type',
                         ]
                     ]
                 ]);

        $history = $response->json('data');
        $this->assertCount(5, $history);
        $this->assertEquals('completed', $history[0]['status']);
    }

    #[Test]
    public function it_requires_student_role_to_access_endpoints()
    {
        // Arrange
        $clubUser = $this->actingAsClub();

        // Act
        $response = $this->getJson('/api/student/dashboard/stats');

        // Assert
        $response->assertStatus(403); // Forbidden car le middleware student vérifie le rôle
    }
}

