<?php

namespace Tests\Unit\Models;

use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Club;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle SubscriptionRecurringSlot
 * 
 * Ce fichier teste le modèle legacy des créneaux récurrents d'abonnement.
 * SubscriptionRecurringSlot représente un créneau récurrent défini par :
 * - day_of_week : jour de la semaine (ex: samedi)
 * - start_time / end_time : heures de début et fin
 * - start_date / end_date : période de validité
 * 
 * Fonctionnalités testées :
 * - Instanciation et relations
 * - Attributs alias pour compatibilité
 * - Validation (isValid)
 * - Gestion du statut (cancel, release, reactivate, complete)
 * - Mise à jour automatique du statut (checkAndUpdateStatus)
 * - Scopes de requête (active, byDayOfWeek, byTeacher, byTimeRange)
 */
class SubscriptionRecurringSlotTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Subscription $subscription;
    private SubscriptionInstance $subscriptionInstance;
    private Student $student;
    private Teacher $teacher;
    private SubscriptionRecurringSlot $recurringSlot;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Créer un abonnement
        $this->subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'free_lessons' => 0,
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

        // Créer un créneau récurrent
        $this->recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SubscriptionRecurringSlot::class, $this->recurringSlot);
    }

    #[Test]
    public function it_has_correct_relationships(): void
    {
        $this->assertInstanceOf(SubscriptionInstance::class, $this->recurringSlot->subscriptionInstance);
        $this->assertInstanceOf(Teacher::class, $this->recurringSlot->teacher);
        $this->assertInstanceOf(Student::class, $this->recurringSlot->student);
    }

    #[Test]
    public function it_has_alias_attributes_for_compatibility(): void
    {
        $this->assertEquals($this->recurringSlot->start_date, $this->recurringSlot->started_at);
        $this->assertEquals($this->recurringSlot->end_date, $this->recurringSlot->expires_at);
    }

    /**
     * Test : Validation d'un créneau actif dans sa période de validité
     * 
     * BUT : Vérifier que isValid() retourne true pour un créneau actif dont la date actuelle
     *       est entre start_date et end_date
     * 
     * ENTRÉE :
     * - status = 'active'
     * - start_date = il y a 1 semaine (dans le passé)
     * - end_date = dans 1 semaine (dans le futur)
     * 
     * SORTIE ATTENDUE : isValid() = true
     * 
     * POURQUOI : Un créneau récurrent est valide uniquement s'il est actif ET que la date actuelle
     *            est dans sa période de validité. C'est utilisé pour déterminer si des lessons
     *            doivent être générées pour ce créneau.
     */
    #[Test]
    public function isValid_returns_true_for_active_slot_in_range(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->start_date = Carbon::now()->subWeek();
        $this->recurringSlot->end_date = Carbon::now()->addWeek();
        $this->recurringSlot->save();

        $this->assertTrue($this->recurringSlot->isValid());
    }

    #[Test]
    public function isValid_returns_false_for_inactive_slot(): void
    {
        $this->recurringSlot->status = 'cancelled';
        $this->recurringSlot->save();

        $this->assertFalse($this->recurringSlot->isValid());
    }

    #[Test]
    public function isValid_returns_false_for_expired_slot(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->start_date = Carbon::now()->subMonths(2);
        $this->recurringSlot->end_date = Carbon::now()->subWeek();
        $this->recurringSlot->save();

        $this->assertFalse($this->recurringSlot->isValid());
    }

    #[Test]
    public function isValid_returns_false_for_future_slot(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->start_date = Carbon::now()->addWeek();
        $this->recurringSlot->end_date = Carbon::now()->addMonths(2);
        $this->recurringSlot->save();

        $this->assertFalse($this->recurringSlot->isValid());
    }

    #[Test]
    public function cancel_sets_status_to_cancelled(): void
    {
        $this->recurringSlot->cancel('Test cancellation');

        $this->recurringSlot->refresh();
        $this->assertEquals('cancelled', $this->recurringSlot->status);
        $this->assertStringContainsString('Annulé', $this->recurringSlot->notes);
    }

    #[Test]
    public function cancel_adds_reason_to_notes(): void
    {
        $reason = 'Raison de test';
        $this->recurringSlot->cancel($reason);

        $this->recurringSlot->refresh();
        $this->assertStringContainsString($reason, $this->recurringSlot->notes);
    }

    #[Test]
    public function release_cancels_with_release_reason(): void
    {
        $reason = 'Libération manuelle';
        $this->recurringSlot->release($reason);

        $this->recurringSlot->refresh();
        $this->assertEquals('cancelled', $this->recurringSlot->status);
        $this->assertStringContainsString('Libération manuelle', $this->recurringSlot->notes);
    }

    #[Test]
    public function reactivate_reactivates_cancelled_slot(): void
    {
        $this->recurringSlot->status = 'cancelled';
        $this->recurringSlot->save();

        $reason = 'Réactivation test';
        $this->recurringSlot->reactivate($reason);

        $this->recurringSlot->refresh();
        $this->assertEquals('active', $this->recurringSlot->status);
        $this->assertStringContainsString('Réactivé', $this->recurringSlot->notes);
    }

    #[Test]
    public function reactivate_does_not_change_non_cancelled_slot(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->save();

        $oldStatus = $this->recurringSlot->status;
        $this->recurringSlot->reactivate('Test');

        $this->recurringSlot->refresh();
        $this->assertEquals($oldStatus, $this->recurringSlot->status);
    }

    #[Test]
    public function complete_sets_status_to_completed(): void
    {
        $this->recurringSlot->complete();

        $this->recurringSlot->refresh();
        $this->assertEquals('completed', $this->recurringSlot->status);
    }

    #[Test]
    public function checkAndUpdateStatus_expires_past_slot(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->end_date = Carbon::now()->subDay();
        $this->recurringSlot->save();

        $this->recurringSlot->checkAndUpdateStatus();

        $this->recurringSlot->refresh();
        $this->assertEquals('expired', $this->recurringSlot->status);
    }

    #[Test]
    public function checkAndUpdateStatus_does_not_change_active_slot(): void
    {
        $this->recurringSlot->status = 'active';
        $this->recurringSlot->end_date = Carbon::now()->addWeek();
        $this->recurringSlot->save();

        $this->recurringSlot->checkAndUpdateStatus();

        $this->recurringSlot->refresh();
        $this->assertEquals('active', $this->recurringSlot->status);
    }

    #[Test]
    public function scopeActive_filters_active_slots(): void
    {
        // Créer un slot inactif
        $inactiveSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'cancelled',
        ]);

        $activeSlots = SubscriptionRecurringSlot::active()->get();

        $this->assertTrue($activeSlots->contains($this->recurringSlot->id));
        $this->assertFalse($activeSlots->contains($inactiveSlot->id));
    }

    #[Test]
    public function scopeByDayOfWeek_filters_by_day(): void
    {
        // Créer un slot pour un autre jour
        $mondaySlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $saturdaySlots = SubscriptionRecurringSlot::byDayOfWeek(Carbon::SATURDAY)->get();

        $this->assertTrue($saturdaySlots->contains($this->recurringSlot->id));
        $this->assertFalse($saturdaySlots->contains($mondaySlot->id));
    }

    #[Test]
    public function scopeByTeacher_filters_by_teacher(): void
    {
        // Créer un autre enseignant
        $otherTeacherUser = User::create([
            'name' => 'Autre Enseignant',
            'email' => 'otherteacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);

        // Créer un slot pour l'autre enseignant
        $otherSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $otherTeacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $teacherSlots = SubscriptionRecurringSlot::byTeacher($this->teacher->id)->get();

        $this->assertTrue($teacherSlots->contains($this->recurringSlot->id));
        $this->assertFalse($teacherSlots->contains($otherSlot->id));
    }

    #[Test]
    public function scopeByTimeRange_filters_overlapping_times(): void
    {
        // Créer un slot avec un horaire qui chevauche (08:30-09:30)
        $overlappingSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Créer un slot avec un horaire qui ne chevauche pas (11:00-12:00)
        $nonOverlappingSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        // Chercher les slots qui chevauchent avec 09:00-10:00
        $overlappingSlots = SubscriptionRecurringSlot::byTimeRange('09:00:00', '10:00:00')->get();

        $this->assertTrue($overlappingSlots->contains($this->recurringSlot->id));
        $this->assertTrue($overlappingSlots->contains($overlappingSlot->id));
        $this->assertFalse($overlappingSlots->contains($nonOverlappingSlot->id));
    }
}

