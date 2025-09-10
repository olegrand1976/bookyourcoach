<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class LessonApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->user = User::factory()->create([
            'email' => 'club@bookyourcoach.com',
            'role' => 'club'
        ]);
        
        $this->club = Club::factory()->create();
        $this->user->clubs()->attach($this->club->id, [
            'role' => 'admin',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
    }

    #[Test]
    public function it_can_create_a_lesson_via_api()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'notes' => 'Cours de test'
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'lesson' => [
                        'id',
                        'teacher_id',
                        'student_id',
                        'course_type_id',
                        'location_id',
                        'start_time',
                        'end_time',
                        'price',
                        'status',
                        'notes'
                    ]
                ]);
        
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'price' => 50.00,
            'status' => 'pending'
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/club/lessons-test', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['teacher_id', 'student_id', 'start_time', 'duration', 'price']);
    }

    #[Test]
    public function it_validates_teacher_exists()
    {
        $lessonData = [
            'teacher_id' => 999, // ID inexistant
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['teacher_id']);
    }

    #[Test]
    public function it_validates_student_exists()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => 999, // ID inexistant
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['student_id']);
    }

    #[Test]
    public function it_validates_duration_minimum()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 20, // Moins de 30 minutes
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['duration']);
    }

    #[Test]
    public function it_validates_price_minimum()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => -10.00 // Prix négatif
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['price']);
    }

    #[Test]
    public function it_calculates_end_time_correctly()
    {
        $startTime = now()->addDay()->setTime(14, 0, 0);
        $duration = 90; // 1h30
        
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(201);
        
        $lesson = Lesson::latest()->first();
        $expectedEndTime = $startTime->copy()->addMinutes($duration);
        
        $this->assertEquals($expectedEndTime->format('Y-m-d H:i:s'), $lesson->end_time->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_associates_student_with_lesson()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(201);
        
        $lesson = Lesson::latest()->first();
        
        // Vérifier que l'étudiant est associé au cours via la table pivot
        $this->assertTrue($lesson->students->contains($this->student));
        
        // Vérifier les données de la table pivot
        $pivotData = $lesson->students->first()->pivot;
        $this->assertEquals('pending', $pivotData->status);
        $this->assertEquals(50.00, $pivotData->price);
    }

    #[Test]
    public function it_uses_default_course_type_and_location()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(201);
        
        $lesson = Lesson::latest()->first();
        
        // Vérifier que les valeurs par défaut sont utilisées
        $this->assertEquals(1, $lesson->course_type_id);
        $this->assertEquals(1, $lesson->location_id);
    }

    #[Test]
    public function it_can_create_lesson_with_custom_course_type_and_location()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(201);
        
        $lesson = Lesson::latest()->first();
        
        $this->assertEquals($this->courseType->id, $lesson->course_type_id);
        $this->assertEquals($this->location->id, $lesson->location_id);
    }

    #[Test]
    public function it_updates_dashboard_statistics()
    {
        // Vérifier les statistiques initiales
        $initialResponse = $this->getJson('/api/club/dashboard-test');
        $initialStats = $initialResponse->json('data.stats');
        
        // Créer un cours
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $this->postJson('/api/club/lessons-test', $lessonData);
        
        // Vérifier que les statistiques sont mises à jour
        $updatedResponse = $this->getJson('/api/club/dashboard-test');
        $updatedStats = $updatedResponse->json('data.stats');
        
        $this->assertEquals($initialStats['total_lessons'] + 1, $updatedStats['total_lessons']);
        $this->assertEquals($initialStats['pending_lessons'] + 1, $updatedStats['pending_lessons']);
    }

    #[Test]
    public function it_shows_new_lesson_in_recent_lessons()
    {
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'notes' => 'Nouveau cours'
        ];
        
        $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response = $this->getJson('/api/club/dashboard-test');
        
        $recentLessons = $response->json('data.recentLessons');
        
        // Le nouveau cours devrait être en premier
        $this->assertEquals('Nouveau cours', $recentLessons[0]['notes']);
        $this->assertEquals('pending', $recentLessons[0]['status']);
    }

    #[Test]
    public function it_returns_error_when_user_not_found()
    {
        // Supprimer l'utilisateur club
        $this->user->delete();
        
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'User not found']);
    }

    #[Test]
    public function it_returns_error_when_club_not_found()
    {
        // Supprimer le club
        $this->club->delete();
        
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Club not found']);
    }

    #[Test]
    public function it_handles_database_errors_gracefully()
    {
        // Mock une erreur de base de données
        $this->mock(\Illuminate\Database\DatabaseManager::class, function ($mock) {
            $mock->shouldReceive('connection')->andThrow(new \Exception('Database error'));
        });
        
        $lessonData = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00
        ];
        
        $response = $this->postJson('/api/club/lessons-test', $lessonData);
        
        $response->assertStatus(500)
                ->assertJsonStructure([
                    'error',
                    'message',
                    'trace'
                ]);
    }
}
