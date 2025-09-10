<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class StudentDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_student_dashboard_stats()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        $this->actingAs($student);

        $response = $this->getJson('/api/student/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'total_bookings',
                'completed_lessons',
                'pending_lessons',
                'cancelled_lessons',
                'total_spent',
                'favorite_teachers_count'
            ]);
    }

    #[Test]
    public function it_can_get_available_lessons()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student);

        // Créer des cours disponibles
        Lesson::factory()->count(5)->create(['status' => 'available']);

        $response = $this->getJson('/api/student/available-lessons');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'price',
                        'duration',
                        'teacher' => [
                            'id',
                            'name',
                            'rating',
                            'specialties'
                        ],
                        'location' => [
                            'id',
                            'name',
                            'address'
                        ]
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_get_available_teachers()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student);

        // Créer des enseignants disponibles
        Teacher::factory()->count(3)->create(['is_available' => true]);

        $response = $this->getJson('/api/student/available-teachers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'specialties',
                        'rating',
                        'hourly_rate',
                        'bio',
                        'is_available'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_get_student_bookings()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        // Créer des réservations
        Booking::factory()->count(3)->create([
            'student_id' => $studentProfile->id
        ]);

        $this->actingAs($student);

        $response = $this->getJson('/api/student/bookings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'lesson_id',
                        'status',
                        'booked_at',
                        'notes',
                        'lesson' => [
                            'id',
                            'title',
                            'price',
                            'duration',
                            'teacher' => [
                                'id',
                                'name'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_create_booking()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        $lesson = Lesson::factory()->create(['status' => 'available']);

        $this->actingAs($student);

        $bookingData = [
            'lesson_id' => $lesson->id,
            'notes' => 'Premier cours, niveau débutant'
        ];

        $response = $this->postJson('/api/student/bookings', $bookingData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'lesson_id',
                    'student_id',
                    'status',
                    'notes',
                    'booked_at'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('bookings', [
            'lesson_id' => $lesson->id,
            'student_id' => $studentProfile->id,
            'status' => 'pending'
        ]);
    }

    #[Test]
    public function it_can_cancel_booking()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        $booking = Booking::factory()->create([
            'student_id' => $studentProfile->id,
            'status' => 'pending'
        ]);

        $this->actingAs($student);

        $response = $this->putJson("/api/student/bookings/{$booking->id}/cancel");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Réservation annulée avec succès'
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled'
        ]);
    }

    #[Test]
    public function it_can_rate_lesson()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        $booking = Booking::factory()->create([
            'student_id' => $studentProfile->id,
            'status' => 'completed'
        ]);

        $this->actingAs($student);

        $ratingData = [
            'rating' => 5,
            'comment' => 'Excellent cours, professeur très compétent'
        ];

        $response = $this->postJson("/api/student/bookings/{$booking->id}/rate", $ratingData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Évaluation enregistrée avec succès'
            ]);

        $this->assertDatabaseHas('ratings', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Excellent cours, professeur très compétent'
        ]);
    }

    #[Test]
    public function it_can_get_favorite_teachers()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        // Créer des enseignants favoris
        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();
        
        // Ajouter aux favoris (simulation)
        $studentProfile->favoriteTeachers()->attach([$teacher1->id, $teacher2->id]);

        $this->actingAs($student);

        $response = $this->getJson('/api/student/favorite-teachers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'specialties',
                        'rating',
                        'hourly_rate',
                        'is_available'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_toggle_favorite_teacher()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        $teacher = Teacher::factory()->create();

        $this->actingAs($student);

        // Ajouter aux favoris
        $response = $this->postJson("/api/student/favorite-teachers/{$teacher->id}/toggle");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Enseignant ajouté aux favoris'
            ]);

        $this->assertDatabaseHas('student_favorite_teachers', [
            'student_id' => $studentProfile->id,
            'teacher_id' => $teacher->id
        ]);

        // Retirer des favoris
        $response = $this->postJson("/api/student/favorite-teachers/{$teacher->id}/toggle");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Enseignant retiré des favoris'
            ]);

        $this->assertDatabaseMissing('student_favorite_teachers', [
            'student_id' => $studentProfile->id,
            'teacher_id' => $teacher->id
        ]);
    }

    #[Test]
    public function it_can_get_lesson_history()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $studentProfile = Student::factory()->create(['user_id' => $student->id]);
        
        // Créer des réservations avec différents statuts
        Booking::factory()->create([
            'student_id' => $studentProfile->id,
            'status' => 'completed'
        ]);
        Booking::factory()->create([
            'student_id' => $studentProfile->id,
            'status' => 'cancelled'
        ]);

        $this->actingAs($student);

        $response = $this->getJson('/api/student/lesson-history');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'lesson_id',
                        'status',
                        'booked_at',
                        'completed_at',
                        'lesson' => [
                            'id',
                            'title',
                            'teacher' => [
                                'id',
                                'name'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_validates_booking_data()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student);

        $response = $this->postJson('/api/student/bookings', [
            'lesson_id' => 999999, // Cours inexistant
            'notes' => str_repeat('a', 1001) // Note trop longue
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'lesson_id',
                'notes'
            ]);
    }

    #[Test]
    public function it_requires_student_role_to_access_dashboard()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher);

        $response = $this->getJson('/api/student/dashboard/stats');

        $response->assertStatus(403);
    }
}
