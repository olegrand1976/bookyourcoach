<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use Tests\TestCase;

class LessonControllerTest extends TestCase
{
    /** @test */
    public function it_can_list_lessons()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);
        
        // Act
        $response = $this->getJson('/api/lessons');
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'teacher_id',
                             'course_type_id',
                             'location_id',
                             'start_time',
                             'end_time',
                             'status',
                             'price',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_can_create_lesson_with_all_required_fields()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lessonData = [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 60,
            'status' => 'confirmed',
            'price' => 45.00,
            'notes' => 'Cours de test',
        ];
        
        // Act
        $response = $this->postJson('/api/lessons', $lessonData);
        
        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'teacher_id',
                         'student_id',
                         'course_type_id',
                         'location_id',
                         'start_time',
                         'end_time',
                         'status',
                         'price',
                     ]
                 ]);
        
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_validates_lesson_creation_data()
    {
        // Arrange
        $this->actingAsClub();
        
        // Act - Envoyer des données vides
        $response = $this->postJson('/api/lessons', []);
        
        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'teacher_id',
                     'course_type_id',
                     'start_time',
                 ]);
    }

    /** @test */
    public function it_validates_teacher_id_exists()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lessonData = [
            'teacher_id' => 99999, // ID inexistant
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 60,
        ];
        
        // Act
        $response = $this->postJson('/api/lessons', $lessonData);
        
        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['teacher_id']);
    }

    /** @test */
    public function it_can_show_lesson_details()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);
        
        // Act
        $response = $this->getJson("/api/lessons/{$lesson->id}");
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'teacher_id',
                         'course_type_id',
                         'location_id',
                         'start_time',
                         'end_time',
                         'status',
                         'price',
                     ]
                 ])
                 ->assertJsonFragment([
                     'id' => $lesson->id,
                 ]);
    }

    /** @test */
    public function it_can_update_lesson()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'pending',
            'price' => 30.00,
        ]);
        
        $updateData = [
            'status' => 'confirmed',
            'price' => 45.00,
            'notes' => 'Cours confirmé',
        ];
        
        // Act
        $response = $this->putJson("/api/lessons/{$lesson->id}", $updateData);
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                 ]);
        
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'confirmed',
            'price' => 45.00,
            'notes' => 'Cours confirmé',
        ]);
    }

    /** @test */
    public function it_can_delete_lesson()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
        ]);
        
        // Act
        $response = $this->deleteJson("/api/lessons/{$lesson->id}");
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                 ]);
        
        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access()
    {
        // Act
        $response = $this->getJson('/api/lessons');
        
        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_calculates_end_time_from_duration()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        $lessonData = [
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 90, // 1h30
        ];
        
        // Act
        $response = $this->postJson('/api/lessons', $lessonData);
        
        // Assert
        $response->assertStatus(201);
        
        $lesson = Lesson::latest()->first();
        $this->assertEquals('2025-10-15 11:30:00', $lesson->end_time);
    }

    /** @test */
    public function it_can_filter_lessons_by_date_range()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        
        // Créer des leçons à différentes dates
        Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
        ]);
        
        Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-20 14:00:00',
        ]);
        
        Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-25 16:00:00',
        ]);
        
        // Act
        $response = $this->getJson('/api/lessons?date_from=2025-10-14&date_to=2025-10-21');
        
        // Assert
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}

