<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class LessonFutureLessonsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $club;
    protected $teacher;
    protected $student;
    protected $courseType;
    protected $location;
    protected $subscriptionInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer le club et l'utilisateur
        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        
        // Créer les entités nécessaires
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
        
        // Créer un template d'abonnement
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'model_number' => 'TEST001',
            'total_lessons' => 10,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true
        ]);
        
        // Créer un abonnement
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'template_id' => $template->id,
            'student_id' => $this->student->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'price' => 200.00
        ]);
        
        // Créer une instance d'abonnement
        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'lessons_remaining' => 10,
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6)
        ]);
    }

    /** @test */
    public function it_can_update_single_lesson_without_affecting_future_lessons()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 10:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Mettre à jour uniquement le premier cours
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 14:00:00',
            'update_scope' => 'single'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que seul le premier cours a été modifié
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-01 14:00:00', $lesson1->start_time);
        $this->assertEquals('2025-12-08 10:00:00', $lesson2->start_time);
        $this->assertEquals('2025-12-15 10:00:00', $lesson3->start_time);
    }

    /** @test */
    public function it_can_update_all_future_lessons_with_time_offset()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 10:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Mettre à jour le premier cours avec décalage horaire (+2 heures)
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 12:00:00', // +2h
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que tous les cours ont été décalés de +2h
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-01 12:00:00', $lesson1->start_time);
        $this->assertEquals('2025-12-08 12:00:00', $lesson2->start_time);
        $this->assertEquals('2025-12-15 12:00:00', $lesson3->start_time);
    }

    /** @test */
    public function it_can_update_all_future_lessons_with_date_offset()
    {
        // Créer plusieurs cours liés à l'abonnement (tous les samedis)
        $baseDate = Carbon::parse('2025-12-06 10:00:00'); // Samedi
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Décaler le premier cours d'un jour (samedi -> dimanche)
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-07 10:00:00', // +1 jour
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que tous les cours ont été décalés de +1 jour
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-07 10:00:00', $lesson1->start_time);
        $this->assertEquals('2025-12-14 10:00:00', $lesson2->start_time);
        $this->assertEquals('2025-12-21 10:00:00', $lesson3->start_time);
    }

    /** @test */
    public function it_can_update_all_future_lessons_with_both_date_and_time_offset()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-06 08:00:00'); // Samedi 8h
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Décaler le premier cours de +1 jour et +2h40
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-07 10:40:00', // +1 jour, +2h40
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que tous les cours ont été décalés de +1 jour et +2h40
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-07 10:40:00', $lesson1->start_time);
        $this->assertEquals('2025-12-14 10:40:00', $lesson2->start_time);
        $this->assertEquals('2025-12-21 10:40:00', $lesson3->start_time);
    }

    /** @test */
    public function it_can_update_all_future_lessons_with_negative_time_offset()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 14:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Décaler le premier cours de -2 heures
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 12:00:00', // -2h
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que tous les cours ont été décalés de -2h
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-01 12:00:00', $lesson1->start_time);
        $this->assertEquals('2025-12-08 12:00:00', $lesson2->start_time);
        $this->assertEquals('2025-12-15 12:00:00', $lesson3->start_time);
    }

    /** @test */
    public function it_does_not_update_cancelled_lessons()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 10:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek(), 'cancelled');
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Mettre à jour avec décalage
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 12:00:00',
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que le cours annulé n'a pas été modifié
        $lesson1->refresh();
        $lesson2->refresh();
        $lesson3->refresh();
        
        $this->assertEquals('2025-12-01 12:00:00', $lesson1->start_time);
        $this->assertEquals('2025-12-08 10:00:00', $lesson2->start_time); // Non modifié
        $this->assertEquals('2025-12-15 12:00:00', $lesson3->start_time);
    }

    /** @test */
    public function it_returns_count_of_updated_future_lessons()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 10:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        $lesson3 = $this->createLessonForSubscription($baseDate->copy()->addWeeks(2));
        
        // Mettre à jour avec all_future
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 12:00:00',
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que le message contient le nombre de cours mis à jour
        $this->assertStringContainsString('2 cours futur(s)', $response->json('message'));
    }

    /** @test */
    public function it_updates_end_time_correctly_for_future_lessons()
    {
        // Créer plusieurs cours liés à l'abonnement
        $baseDate = Carbon::parse('2025-12-01 10:00:00');
        
        $lesson1 = $this->createLessonForSubscription($baseDate);
        $lesson2 = $this->createLessonForSubscription($baseDate->copy()->addWeek());
        
        // Mettre à jour avec décalage et durée explicite
        $response = $this->putJson("/api/lessons/{$lesson1->id}", [
            'start_time' => '2025-12-01 12:00:00',
            'duration' => 60,
            'update_scope' => 'all_future'
        ]);
        
        $response->assertStatus(200);
        
        // Vérifier que end_time est correctement calculé (start_time + duration)
        $lesson1->refresh();
        $lesson2->refresh();
        
        // Vérifier que end_time est après start_time (durée positive)
        $lesson1EndTime = Carbon::parse($lesson1->end_time);
        $lesson1StartTime = Carbon::parse($lesson1->start_time);
        $this->assertTrue($lesson1EndTime->gt($lesson1StartTime), 'end_time should be after start_time for lesson1');
        
        $lesson2EndTime = Carbon::parse($lesson2->end_time);
        $lesson2StartTime = Carbon::parse($lesson2->start_time);
        $this->assertTrue($lesson2EndTime->gt($lesson2StartTime), 'end_time should be after start_time for lesson2');
    }

    /**
     * Helper pour créer un cours lié à l'abonnement
     */
    protected function createLessonForSubscription(Carbon $startTime, string $status = 'confirmed'): Lesson
    {
        $lesson = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'club_id' => $this->club->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $startTime->copy()->addMinutes(60)->format('Y-m-d H:i:s'),
            'status' => $status
        ]);
        
        // Lier le cours à l'instance d'abonnement
        $lesson->subscriptionInstances()->attach($this->subscriptionInstance->id);
        
        return $lesson;
    }
}

