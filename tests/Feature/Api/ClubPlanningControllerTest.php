<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Discipline;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;

class ClubPlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_suggest_optimal_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
            'discipline_id' => $discipline->id,
            'day_of_week' => Carbon::now()->addDays(1)->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);
        
        $slot->courseTypes()->attach($courseType->id);

        $requestData = [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'duration' => 60,
            'discipline_id' => $discipline->id,
        ];

        // Act
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', $requestData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'suggestions' => [
                             '*' => [
                                 'slot_id',
                                 'time',
                                 'cost',
                                 'teacher_availability',
                             ]
                         ]
                     ]
                 ]);
    }

    #[Test]
    public function it_validates_suggest_optimal_slot_data()
    {
        // Arrange
        $user = $this->actingAsClub();

        // Act
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', []);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }

    #[Test]
    public function it_can_check_availability()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $requestData = [
            'teacher_id' => $teacher->id,
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'start_time' => '10:00',
            'duration' => 60,
        ];

        // Act
        $response = $this->postJson('/api/club/planning/check-availability', $requestData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'is_available',
                         'conflicts',
                     ]
                 ]);
    }

    #[Test]
    public function it_detects_conflicts_when_checking_availability()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        // Créer un cours existant qui entre en conflit
        $existingLesson = Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(1)->setTime(10, 0),
            'end_time' => Carbon::now()->addDays(1)->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $requestData = [
            'teacher_id' => $teacher->id,
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'start_time' => '10:30', // Conflit avec le cours existant
            'duration' => 60,
        ];

        // Act
        $response = $this->postJson('/api/club/planning/check-availability', $requestData);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertFalse($data['is_available']);
        $this->assertNotEmpty($data['conflicts']);
    }

    #[Test]
    public function it_can_get_planning_statistics()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true]);
        
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(5)->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
            'start_time' => Carbon::now()->addDays(1),
        ]);

        Lesson::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
            'club_id' => $club->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'completed',
            'start_time' => Carbon::now()->subDays(5),
        ]);

        // Act
        $response = $this->getJson('/api/club/planning/statistics');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'upcoming_lessons',
                         'completed_lessons',
                         'total_revenue',
                         'average_lessons_per_day',
                     ]
                 ]);

        $stats = $response->json('data');
        $this->assertGreaterThanOrEqual(5, $stats['upcoming_lessons']);
        $this->assertGreaterThanOrEqual(3, $stats['completed_lessons']);
    }

    #[Test]
    public function it_requires_club_role_to_access_planning_endpoints()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);

        // Assert
        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_authentication_to_access_planning_endpoints()
    {
        // Act
        $response = $this->getJson('/api/club/planning/statistics');

        // Assert
        $response->assertStatus(401);
    }
}

