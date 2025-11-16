<?php

namespace Tests\Feature;

use App\Services\RecurringSlotService;
use App\Models\RecurringSlot;
use App\Models\RecurringSlotSubscription;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Lesson;
use App\Models\LessonRecurringSlot;
use App\Models\Club;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringSlotServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecurringSlotService $service;
    private Club $club;
    private Teacher $teacher;
    private Student $student;
    private CourseType $courseType;
    private Location $location;
    private Subscription $subscription;
    private SubscriptionInstance $subscriptionInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RecurringSlotService();

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer un utilisateur pour l'enseignant
        $teacherUser = User::create([
            'name' => 'Enseignant Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        // Créer un enseignant
        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);

        // Créer un utilisateur pour l'élève
        $studentUser = User::create([
            'name' => 'Élève Test',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        // Créer un élève
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'club_id' => $this->club->id,
        ]);

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
        ]);

        // Créer un lieu
        $this->location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        // Créer un abonnement
        $this->subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        // Créer une instance d'abonnement
        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Lier l'élève à l'abonnement
        $this->subscriptionInstance->students()->attach($this->student->id);
    }

    /**
     * Test de génération de lessons pour un créneau récurrent simple
     */
    public function test_generates_lessons_for_recurring_slot(): void
    {
        // Créer un créneau récurrent (tous les samedis à 9h)
        $recurringSlot = RecurringSlot::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'club_id' => $this->club->id,
            'course_type_id' => $this->courseType->id,
            'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
            'reference_start_time' => Carbon::now()->next(Carbon::SATURDAY)->setTime(9, 0),
            'duration_minutes' => 60,
            'status' => 'active',
        ]);

        // Créer la liaison avec l'abonnement
        $subscriptionLink = RecurringSlotSubscription::create([
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Générer les lessons pour les 4 prochaines semaines
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeeks(4);

        $stats = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);

        // Vérifier que des lessons ont été générées
        $this->assertGreaterThan(0, $stats['generated']);
        $this->assertEquals(0, $stats['errors']);

        // Vérifier que les lessons existent dans la base de données
        $lessons = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->get();

        $this->assertGreaterThan(0, $lessons->count());

        // Vérifier que les lessons sont liées au créneau récurrent
        $lessonLinks = LessonRecurringSlot::where('recurring_slot_id', $recurringSlot->id)->get();
        $this->assertEquals($stats['generated'], $lessonLinks->count());

        // Vérifier que les lessons sont liées à l'abonnement
        foreach ($lessons as $lesson) {
            $this->assertTrue($this->subscriptionInstance->lessons->contains($lesson->id));
        }
    }

    /**
     * Test que les lessons ne sont pas générées si le créneau est inactif
     */
    public function test_does_not_generate_lessons_for_inactive_slot(): void
    {
        // Créer un créneau récurrent inactif
        $recurringSlot = RecurringSlot::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'club_id' => $this->club->id,
            'course_type_id' => $this->courseType->id,
            'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
            'reference_start_time' => Carbon::now()->next(Carbon::SATURDAY)->setTime(9, 0),
            'duration_minutes' => 60,
            'status' => 'paused',
        ]);

        // Créer la liaison avec l'abonnement
        RecurringSlotSubscription::create([
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $stats = $this->service->generateLessonsForSlot($recurringSlot);

        // Vérifier qu'aucune lesson n'a été générée
        $this->assertEquals(0, $stats['generated']);
    }

    /**
     * Test que les lessons ne sont pas générées si l'abonnement n'est pas actif
     */
    public function test_does_not_generate_lessons_without_active_subscription(): void
    {
        // Créer un créneau récurrent sans liaison d'abonnement
        $recurringSlot = RecurringSlot::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'club_id' => $this->club->id,
            'course_type_id' => $this->courseType->id,
            'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
            'reference_start_time' => Carbon::now()->next(Carbon::SATURDAY)->setTime(9, 0),
            'duration_minutes' => 60,
            'status' => 'active',
        ]);

        // Ne pas créer de liaison d'abonnement

        $stats = $this->service->generateLessonsForSlot($recurringSlot);

        // Vérifier qu'aucune lesson n'a été générée
        $this->assertEquals(0, $stats['generated']);
    }

    /**
     * Test que les doublons ne sont pas créés
     */
    public function test_does_not_create_duplicate_lessons(): void
    {
        // Créer un créneau récurrent avec une date proche pour garantir la génération
        $nextSaturday = Carbon::now()->next(Carbon::SATURDAY);
        $recurringSlot = RecurringSlot::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'club_id' => $this->club->id,
            'course_type_id' => $this->courseType->id,
            'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
            'reference_start_time' => $nextSaturday->setTime(9, 0),
            'duration_minutes' => 60,
            'status' => 'active',
        ]);

        // Créer la liaison avec l'abonnement
        RecurringSlotSubscription::create([
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Générer les lessons une première fois avec une période qui garantit au moins une lesson
        $startDate = Carbon::now();
        $endDate = $nextSaturday->copy()->addWeeks(2); // Au moins 2 samedis
        
        $stats1 = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);
        $lessonsCount1 = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->count();

        // Vérifier qu'au moins une lesson a été générée
        $this->assertGreaterThan(0, $stats1['generated'], 'Au moins une lesson doit être générée la première fois');
        $this->assertEquals($stats1['generated'], $lessonsCount1);

        // Générer les lessons une deuxième fois avec la même période
        $stats2 = $this->service->generateLessonsForSlot($recurringSlot, $startDate, $endDate);
        $lessonsCount2 = Lesson::where('teacher_id', $this->teacher->id)
            ->where('student_id', $this->student->id)
            ->count();

        // Vérifier que le nombre de lessons n'a pas changé
        $this->assertEquals($lessonsCount1, $lessonsCount2, 'Le nombre de lessons ne doit pas changer');
        $this->assertEquals(0, $stats2['generated'], 'Aucune nouvelle lesson ne doit être générée');
        
        // Note: Les lessons existantes sont filtrées dans filterDatesBySubscriptionValidity
        // donc elles ne sont pas comptées comme "skipped" mais simplement ignorées
        // Le test principal est que le nombre de lessons n'a pas changé
    }

    /**
     * Test de l'expiration des liaisons abonnement-créneau
     */
    public function test_expires_subscription_links(): void
    {
        // Créer un créneau récurrent
        $recurringSlot = RecurringSlot::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'club_id' => $this->club->id,
            'course_type_id' => $this->courseType->id,
            'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
            'reference_start_time' => Carbon::now()->next(Carbon::SATURDAY)->setTime(9, 0),
            'duration_minutes' => 60,
            'status' => 'active',
        ]);

        // Créer une liaison expirée
        $expiredLink = RecurringSlotSubscription::create([
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'start_date' => Carbon::now()->subMonths(2),
            'end_date' => Carbon::now()->subWeek(),
            'status' => 'active',
        ]);

        // Expirer les liaisons
        $count = $this->service->expireSubscriptionLinks();

        // Vérifier qu'une liaison a été expirée
        $this->assertEquals(1, $count);
        $expiredLink->refresh();
        $this->assertEquals('expired', $expiredLink->status);
    }

    /**
     * Test de génération pour tous les créneaux actifs
     */
    public function test_generates_lessons_for_all_active_slots(): void
    {
        // Créer plusieurs créneaux récurrents
        $slots = [];
        for ($i = 0; $i < 3; $i++) {
            $slot = RecurringSlot::create([
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id,
                'club_id' => $this->club->id,
                'course_type_id' => $this->courseType->id,
                'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
                'reference_start_time' => Carbon::now()->next(Carbon::SATURDAY)->setTime(9 + $i, 0),
                'duration_minutes' => 60,
                'status' => 'active',
            ]);

            RecurringSlotSubscription::create([
                'recurring_slot_id' => $slot->id,
                'subscription_instance_id' => $this->subscriptionInstance->id,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'status' => 'active',
            ]);

            $slots[] = $slot;
        }

        // Générer les lessons pour tous les créneaux
        $stats = $this->service->generateLessonsForAllActiveSlots();

        // Vérifier que des lessons ont été générées
        $this->assertGreaterThan(0, $stats['lessons_generated']);
        $this->assertEquals(3, $stats['slots_processed']);
        $this->assertEquals(0, $stats['errors']);
    }
}
