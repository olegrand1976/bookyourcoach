<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeacherDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_teacher_dashboard_data()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        $this->actingAs($teacher);

        $response = $this->getJson('/api/teacher/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'teacher' => [
                    'id',
                    'name',
                    'email',
                    'specialties',
                    'rating',
                    'total_lessons'
                ],
                'stats' => [
                    'total_lessons',
                    'completed_lessons',
                    'pending_lessons',
                    'total_revenue',
                    'average_rating'
                ],
                'recent_lessons',
                'upcoming_lessons'
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_availability()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        $this->actingAs($teacher);

        $availabilityData = [
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_available' => true
        ];

        $response = $this->postJson('/api/teacher/availabilities', $availabilityData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'day_of_week',
                    'start_time',
                    'end_time',
                    'is_available'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('availabilities', [
            'teacher_id' => $teacherProfile->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_availability()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        $availability = Availability::factory()->create([
            'teacher_id' => $teacherProfile->id
        ]);

        $this->actingAs($teacher);

        $updateData = [
            'start_time' => '10:00',
            'end_time' => '18:00',
            'is_available' => false
        ];

        $response = $this->putJson("/api/teacher/availabilities/{$availability->id}", $updateData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $availability->id,
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'is_available' => false
                ]
            ]);

        $this->assertDatabaseHas('availabilities', [
            'id' => $availability->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
            'is_available' => false
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_availability()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        $availability = Availability::factory()->create([
            'teacher_id' => $teacherProfile->id
        ]);

        $this->actingAs($teacher);

        $response = $this->deleteJson("/api/teacher/availabilities/{$availability->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilité supprimée avec succès'
            ]);

        $this->assertDatabaseMissing('availabilities', [
            'id' => $availability->id
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_teacher_students()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        // Créer des étudiants qui ont réservé des cours avec ce professeur
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        
        $this->actingAs($teacher);

        $response = $this->getJson('/api/teacher/students');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'level',
                        'total_lessons',
                        'last_lesson_date'
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_validates_availability_data()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($teacher);

        $response = $this->postJson('/api/teacher/availabilities', [
            'day_of_week' => 'invalid_day',
            'start_time' => '25:00', // Heure invalide
            'end_time' => '08:00'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'day_of_week',
                'start_time'
            ]);
    }

    /**
     * @test
     */
    public function it_requires_teacher_role_to_access_dashboard()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student);

        $response = $this->getJson('/api/teacher/dashboard');

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_get_teacher_lessons_with_pagination()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacherProfile = Teacher::factory()->create(['user_id' => $teacher->id]);
        
        // Créer plusieurs cours
        \App\Models\Lesson::factory()->count(15)->create([
            'teacher_id' => $teacherProfile->id
        ]);

        $this->actingAs($teacher);

        $response = $this->getJson('/api/teacher/lessons?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'price',
                        'duration',
                        'created_at'
                    ]
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
    }
}
