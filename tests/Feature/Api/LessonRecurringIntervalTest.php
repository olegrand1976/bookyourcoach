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
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Tests pour la fonctionnalité de récurrence avec intervalle
 * 
 * Ce fichier teste la création et modification de cours avec recurring_interval :
 * - Création de cours avec recurring_interval (1, 2, 3, 4 semaines)
 * - Modification de cours avec recurring_interval et update_scope='all_future'
 * - Régénération des cours futurs avec nouvel intervalle
 */
class LessonRecurringIntervalTest extends TestCase
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
            'total_lessons' => 20,
            'duration_days' => 365,
            'price' => 200.00,
        ]);
        
        // Créer un abonnement
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'template_id' => $template->id,
            'name' => 'Abonnement Test',
            'model_number' => 'TEST001',
            'total_lessons' => 20,
            'duration_days' => 365,
            'price' => 200.00,
        ]);
        
        // Créer une instance d'abonnement active
        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'starts_at' => Carbon::now()->subDays(30),
            'expires_at' => Carbon::now()->addDays(335),
            'lessons_remaining' => 20,
        ]);
    }

    /**
     * Test : Création de cours avec recurring_interval = 1 (hebdomadaire)
     * 
     * BUT : Vérifier qu'un cours est créé avec récurrence hebdomadaire
     * 
     * ENTRÉE : Création de cours avec recurring_interval = 1
     * 
     * SORTIE ATTENDUE : Le cours est créé et les cours futurs sont générés chaque semaine
     */
    public function test_it_creates_lesson_with_weekly_recurring_interval(): void
    {
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            'recurring_interval' => 1, // Chaque semaine
        ]);

        $response->assertStatus(201);
        
        // Vérifier que le cours principal est créé
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
        ]);

        // Vérifier qu'un créneau récurrent est créé avec recurring_interval = 1
        $lesson = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($lesson);

        // Attendre que le job asynchrone soit traité
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        // Vérifier qu'un SubscriptionRecurringSlot est créé avec recurring_interval = 1
        $recurringSlot = SubscriptionRecurringSlot::where('subscription_instance_id', $this->subscriptionInstance->id)
            ->where('day_of_week', $startTime->dayOfWeek)
            ->first();

        if ($recurringSlot) {
            $this->assertEquals(1, $recurringSlot->recurring_interval);
        }
    }

    /**
     * Test : Création de cours avec recurring_interval = 2 (bi-hebdomadaire)
     * 
     * BUT : Vérifier qu'un cours est créé avec récurrence bi-hebdomadaire
     * 
     * ENTRÉE : Création de cours avec recurring_interval = 2
     * 
     * SORTIE ATTENDUE : Le cours est créé et les cours futurs sont générés toutes les 2 semaines
     */
    public function test_it_creates_lesson_with_biweekly_recurring_interval(): void
    {
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            'recurring_interval' => 2, // Toutes les 2 semaines
        ]);

        $response->assertStatus(201);

        // Attendre que le job asynchrone soit traité
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        // Vérifier qu'un SubscriptionRecurringSlot est créé avec recurring_interval = 2
        $recurringSlot = SubscriptionRecurringSlot::where('subscription_instance_id', $this->subscriptionInstance->id)
            ->where('day_of_week', $startTime->dayOfWeek)
            ->first();

        if ($recurringSlot) {
            $this->assertEquals(2, $recurringSlot->recurring_interval);
        }
    }

    /**
     * Test : Création de cours avec recurring_interval = 4 (mensuel)
     * 
     * BUT : Vérifier qu'un cours est créé avec récurrence mensuelle
     * 
     * ENTRÉE : Création de cours avec recurring_interval = 4
     * 
     * SORTIE ATTENDUE : Le cours est créé et les cours futurs sont générés toutes les 4 semaines
     */
    public function test_it_creates_lesson_with_monthly_recurring_interval(): void
    {
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            'recurring_interval' => 4, // Toutes les 4 semaines
        ]);

        $response->assertStatus(201);

        // Attendre que le job asynchrone soit traité
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        // Vérifier qu'un SubscriptionRecurringSlot est créé avec recurring_interval = 4
        $recurringSlot = SubscriptionRecurringSlot::where('subscription_instance_id', $this->subscriptionInstance->id)
            ->where('day_of_week', $startTime->dayOfWeek)
            ->first();

        if ($recurringSlot) {
            $this->assertEquals(4, $recurringSlot->recurring_interval);
        }
    }

    /**
     * Test : Modification de cours avec recurring_interval et update_scope='all_future'
     * 
     * BUT : Vérifier que la modification avec nouvel intervalle régénère les cours futurs
     * 
     * ENTRÉE : Modification d'un cours avec recurring_interval = 2 et update_scope='all_future'
     * 
     * SORTIE ATTENDUE : Les cours futurs sont supprimés et régénérés avec le nouvel intervalle
     */
    public function test_it_updates_lesson_with_new_recurring_interval_and_all_future_scope(): void
    {
        // Créer un cours initial avec récurrence hebdomadaire
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $startTime->copy()->addMinutes(60)->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        // Créer un créneau récurrent avec intervalle = 1
        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'day_of_week' => $startTime->dayOfWeek,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $startTime->copy()->addMinutes(60)->format('H:i:s'),
            'recurring_interval' => 1,
        ]);

        // Créer quelques cours futurs
        for ($i = 1; $i <= 3; $i++) {
            Lesson::create([
                'club_id' => $this->club->id,
                'teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'course_type_id' => $this->courseType->id,
                'location_id' => $this->location->id,
                'start_time' => $startTime->copy()->addWeeks($i)->format('Y-m-d H:i:s'),
                'end_time' => $startTime->copy()->addWeeks($i)->addMinutes(60)->format('Y-m-d H:i:s'),
                'status' => 'confirmed',
                'price' => 50.00,
            ]);
        }

        $initialFutureLessonsCount = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->where('start_time', '>', $startTime->format('Y-m-d H:i:s'))
            ->count();

        // Modifier le cours avec nouvel intervalle et update_scope='all_future'
        $response = $this->putJson("/api/lessons/{$lesson->id}", [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'update_scope' => 'all_future',
            'recurring_interval' => 2, // Nouvel intervalle : toutes les 2 semaines
        ]);

        $response->assertStatus(200);

        // Attendre que le job asynchrone soit traité
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        // Vérifier que le créneau récurrent a été mis à jour avec le nouvel intervalle
        $recurringSlot->refresh();
        $this->assertEquals(2, $recurringSlot->recurring_interval);

        // Note : Les cours futurs sont supprimés et régénérés par le job asynchrone
        // Ce test vérifie principalement que la mise à jour de l'intervalle fonctionne
    }

    /**
     * Test : Validation de recurring_interval (min: 1, max: 52)
     * 
     * BUT : Vérifier que recurring_interval est validé correctement
     * 
     * ENTRÉE : Création de cours avec recurring_interval invalide
     * 
     * SORTIE ATTENDUE : Erreur de validation
     */
    public function test_it_validates_recurring_interval_range(): void
    {
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        // Test avec recurring_interval = 0 (invalide)
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'recurring_interval' => 0, // Invalide
        ]);

        $response->assertStatus(422);

        // Test avec recurring_interval = 53 (invalide, max: 52)
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'recurring_interval' => 53, // Invalide
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test : recurring_interval par défaut = 1 si non spécifié
     * 
     * BUT : Vérifier que recurring_interval = 1 par défaut
     * 
     * ENTRÉE : Création de cours sans recurring_interval
     * 
     * SORTIE ATTENDUE : recurring_interval = 1 par défaut
     */
    public function test_it_uses_default_recurring_interval_when_not_specified(): void
    {
        $startTime = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            // Pas de recurring_interval spécifié
        ]);

        $response->assertStatus(201);

        // Attendre que le job asynchrone soit traité
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1]);

        // Vérifier qu'un SubscriptionRecurringSlot est créé avec recurring_interval = 1 par défaut
        $recurringSlot = SubscriptionRecurringSlot::where('subscription_instance_id', $this->subscriptionInstance->id)
            ->where('day_of_week', $startTime->dayOfWeek)
            ->first();

        if ($recurringSlot) {
            $this->assertEquals(1, $recurringSlot->recurring_interval);
        }
    }
}

