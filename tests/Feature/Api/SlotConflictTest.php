<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\ClubOpenSlot;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SlotConflictTest extends TestCase
{
    use RefreshDatabase;

    protected $club;
    protected $teacher;
    protected $student;
    protected $courseType;
    protected $location;
    protected $openSlot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsClub();
        $this->club = \App\Models\Club::find(Auth::user()->club_id);
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
        
        // Créer un créneau ouvert (lundi 9h-12h, max 2 slots)
        $this->openSlot = ClubOpenSlot::create([
            'club_id' => $this->club->id,
            'day_of_week' => 1, // Lundi
            'start_time' => '09:00',
            'end_time' => '12:00',
            'max_slots' => 2,
            'max_capacity' => 5,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_get_slot_occupants()
    {
        // Créer des cours qui occupent le créneau
        $lesson1 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => '2025-12-08 09:00:00', // Lundi
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'confirmed',
        ]);

        $lesson2 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => '2025-12-08 09:30:00', // Chevauche le premier
            'end_time' => '2025-12-08 10:30:00',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson('/api/lessons/slot-occupants?' . http_build_query([
            'date' => '2025-12-08',
            'time' => '09:00',
            'duration' => 60,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $data = $response->json('data');
        $this->assertArrayHasKey('lessons', $data);
        $this->assertArrayHasKey('slot_info', $data);
        $this->assertCount(2, $data['lessons']);
        $this->assertEquals(2, $data['slot_info']['current_count']);
        $this->assertTrue($data['slot_info']['is_full']);
    }

    /** @test */
    public function it_returns_empty_when_no_occupants()
    {
        $response = $this->getJson('/api/lessons/slot-occupants?' . http_build_query([
            'date' => '2025-12-08',
            'time' => '09:00',
            'duration' => 60,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $data = $response->json('data');
        $this->assertCount(0, $data['lessons']);
        $this->assertFalse($data['slot_info']['is_full']);
        $this->assertEquals(2, $data['slot_info']['available_slots']);
    }

    /** @test */
    public function it_excludes_cancelled_lessons_from_occupants()
    {
        // Créer un cours confirmé
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'start_time' => '2025-12-08 09:00:00',
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'confirmed',
        ]);

        // Créer un cours annulé
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'start_time' => '2025-12-08 09:00:00',
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'cancelled',
        ]);

        $response = $this->getJson('/api/lessons/slot-occupants?' . http_build_query([
            'date' => '2025-12-08',
            'time' => '09:00',
            'duration' => 60,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Seul le cours confirmé doit être retourné
        $this->assertCount(1, $data['lessons']);
    }

    /** @test */
    public function it_can_cancel_single_lesson()
    {
        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => '2025-12-08 09:00:00',
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson("/api/lessons/{$lesson->id}/cancel-with-future", [
            'cancel_scope' => 'single',
            'reason' => 'Test annulation',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'cancelled_count' => 1,
            ],
        ]);

        $lesson->refresh();
        $this->assertEquals('cancelled', $lesson->status);
        $this->assertStringContainsString('Test annulation', $lesson->notes);
    }

    /** @test */
    public function it_can_cancel_lesson_with_all_future_lessons()
    {
        // Créer un abonnement
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => null,
            'name' => 'Test Subscription',
        ]);
        
        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);

        // Associer l'élève à l'instance d'abonnement
        $subscriptionInstance->students()->attach($this->student->id);

        // Créer plusieurs cours liés à l'abonnement
        $lesson1 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => '2025-12-08 09:00:00',
            'status' => 'confirmed',
        ]);
        $lesson1->subscriptionInstances()->attach($subscriptionInstance->id);

        $lesson2 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => '2025-12-15 09:00:00', // Une semaine après
            'status' => 'confirmed',
        ]);
        $lesson2->subscriptionInstances()->attach($subscriptionInstance->id);

        $lesson3 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => '2025-12-22 09:00:00', // Deux semaines après
            'status' => 'confirmed',
        ]);
        $lesson3->subscriptionInstances()->attach($subscriptionInstance->id);

        // Annuler le premier cours avec tous les cours futurs
        $response = $this->postJson("/api/lessons/{$lesson1->id}/cancel-with-future", [
            'cancel_scope' => 'all_future',
            'reason' => 'Annulation cascade',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'cancelled_count' => 3,
            ],
        ]);

        // Vérifier que tous les cours sont annulés
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();

        $this->assertEquals('cancelled', $lesson1->status);
        $this->assertEquals('cancelled', $lesson2->status);
        $this->assertEquals('cancelled', $lesson3->status);
    }

    /** @test */
    public function it_only_cancels_future_lessons_not_past()
    {
        // Créer un abonnement
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => null,
        ]);
        
        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now()->subMonths(1),
            'expires_at' => now()->addMonths(6),
        ]);
        $subscriptionInstance->students()->attach($this->student->id);

        // Créer un cours passé (déjà fait)
        $pastLesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->subWeek(),
            'status' => 'completed',
        ]);
        $pastLesson->subscriptionInstances()->attach($subscriptionInstance->id);

        // Créer le cours actuel
        $currentLesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addDay(),
            'status' => 'confirmed',
        ]);
        $currentLesson->subscriptionInstances()->attach($subscriptionInstance->id);

        // Créer un cours futur
        $futureLesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'start_time' => now()->addWeeks(2),
            'status' => 'confirmed',
        ]);
        $futureLesson->subscriptionInstances()->attach($subscriptionInstance->id);

        // Annuler le cours actuel avec tous les futurs
        $response = $this->postJson("/api/lessons/{$currentLesson->id}/cancel-with-future", [
            'cancel_scope' => 'all_future',
        ]);

        $response->assertStatus(200);
        
        // Le cours passé ne doit pas être annulé
        $pastLesson->refresh();
        $currentLesson->refresh();
        $futureLesson->refresh();

        $this->assertEquals('completed', $pastLesson->status); // Non modifié
        $this->assertEquals('cancelled', $currentLesson->status);
        $this->assertEquals('cancelled', $futureLesson->status);
    }

    /** @test */
    public function it_validates_cancel_scope_parameter()
    {
        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'confirmed',
        ]);

        $response = $this->postJson("/api/lessons/{$lesson->id}/cancel-with-future", [
            'cancel_scope' => 'invalid_scope',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_lesson()
    {
        $response = $this->postJson("/api/lessons/99999/cancel-with-future", [
            'cancel_scope' => 'single',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function slot_occupants_filters_by_teacher_when_provided()
    {
        $teacher2 = Teacher::factory()->create(['club_id' => $this->club->id]);

        // Cours du premier enseignant
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'start_time' => '2025-12-08 09:00:00',
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'confirmed',
        ]);

        // Cours du second enseignant
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $teacher2->id,
            'start_time' => '2025-12-08 09:00:00',
            'end_time' => '2025-12-08 10:00:00',
            'status' => 'confirmed',
        ]);

        // Filtrer par le premier enseignant
        $response = $this->getJson('/api/lessons/slot-occupants?' . http_build_query([
            'date' => '2025-12-08',
            'time' => '09:00',
            'duration' => 60,
            'teacher_id' => $this->teacher->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Seul le cours du premier enseignant doit être retourné
        $this->assertCount(1, $data['lessons']);
        $this->assertEquals($this->teacher->id, $data['lessons'][0]['teacher_id']);
    }
}

