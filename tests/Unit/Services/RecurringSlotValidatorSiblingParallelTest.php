<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Teacher;
use App\Models\User;
use App\Services\RecurringSlotValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Abonnement familial : deux élèves (student_id distincts) peuvent avoir une récurrence
 * le même jour / même plage horaire avec des enseignants différents.
 *
 * Requiert l’extension PHP pdo_sqlite (ou DB de test configurée) pour RefreshDatabase.
 */
class RecurringSlotValidatorSiblingParallelTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Student $studentA;

    private Student $studentB;

    private Teacher $teacherA;

    private Teacher $teacherB;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.timezone', 'Europe/Paris');

        $this->club = Club::create([
            'name' => 'Club Fratrie',
            'email' => 'fratrie@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'name' => 'Piscine',
            'address' => 'Rue 1',
            'city' => 'Ville',
            'postal_code' => '1000',
            'country' => 'BE',
        ]);

        $this->studentA = $this->makeStudent('eleve-a@test.com', 'Élève A');
        $this->studentB = $this->makeStudent('eleve-b@test.com', 'Élève B');

        $this->teacherA = $this->makeTeacher('prof-a-par@test.com', 'Prof A');
        $this->teacherB = $this->makeTeacher('prof-b-par@test.com', 'Prof B');
    }

    private function makeStudent(string $email, string $name): Student
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'club_id' => $this->club->id,
        ]);
    }

    private function makeTeacher(string $email, string $name): Teacher
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);
    }

    #[Test]
    public function two_students_same_subscription_may_have_parallel_recurring_same_time_different_teachers(): void
    {
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'FAM-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => '2026-01-01',
            'expires_at' => '2027-12-31',
            'status' => 'active',
        ]);

        $instance->students()->sync([$this->studentA->id, $this->studentB->id]);

        SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'open_slot_id' => null,
            'teacher_id' => $this->teacherA->id,
            'student_id' => $this->studentA->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:00:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacherB->id,
            $this->studentB->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertTrue($result['valid'], $result['message'] ?? json_encode($result['conflicts'] ?? []));
        $this->assertSame([], $result['conflicts']);
    }

    #[Test]
    public function teacher_busy_in_other_club_does_not_block_when_validating_with_club_scope(): void
    {
        $clubOther = Club::create([
            'name' => 'Autre club',
            'email' => 'autre@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        // Cours dans un autre club, même enseignant, même fenêtre UTC que la proposition
        Lesson::create([
            'club_id' => $clubOther->id,
            'student_id' => $this->studentA->id,
            'teacher_id' => $this->teacherA->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 16:00:00',
            'end_time' => '2026-03-25 16:20:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacherA->id,
            $this->studentB->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertTrue($result['valid'], $result['message'] ?? json_encode($result['conflicts'] ?? []));
    }

    /**
     * Cas prod (HAR) : 1er mercredi = prof bloqué par un cours existant (lesson_id) +
     * élève bloqué par la récurrence déjà enregistrée ; semaines suivantes = recurring_duplicate (même slot).
     */
    #[Test]
    public function har_like_first_week_teacher_lesson_plus_student_recurring_then_duplicate_weeks(): void
    {
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'HAR-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => '2026-01-01',
            'expires_at' => '2027-12-31',
            'status' => 'active',
        ]);

        $instance->students()->sync([$this->studentA->id, $this->studentB->id]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'open_slot_id' => null,
            'teacher_id' => $this->teacherB->id,
            'student_id' => $this->studentB->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:00:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $courseType = CourseType::factory()->create(['club_id' => $this->club->id]);

        // Autre élève, même prof : cours réel 17:00–17:20 (chevauche la proposition, même fenêtre)
        $blockingLesson = Lesson::create([
            'club_id' => $this->club->id,
            'student_id' => $this->studentA->id,
            'teacher_id' => $this->teacherB->id,
            'course_type_id' => $courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-25 17:00:00',
            'end_time' => '2026-03-25 17:20:00',
            'status' => 'confirmed',
            'price' => 18,
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacherB->id,
            $this->studentB->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertFalse($result['valid']);
        $byDate = [];
        foreach ($result['conflicts'] as $c) {
            $byDate[$c['date']][] = $c;
        }

        $this->assertArrayHasKey('2026-03-25', $byDate);
        $this->assertCount(2, $byDate['2026-03-25']);

        $typesMar25 = array_column($byDate['2026-03-25'], 'type');
        $this->assertContains('teacher_unavailable', $typesMar25);
        $this->assertContains('student_unavailable', $typesMar25);

        $teacherC = collect($byDate['2026-03-25'])->firstWhere('type', 'teacher_unavailable');
        $studentC = collect($byDate['2026-03-25'])->firstWhere('type', 'student_unavailable');

        $this->assertSame((int) $blockingLesson->id, (int) ($teacherC['lesson_id'] ?? 0));
        $this->assertTrue(
            ($teacherC['recurring_slot_id'] ?? null) === null,
            'Blocage prof 1re semaine via cours, pas via recurring_slot_id'
        );
        $this->assertSame((int) $slot->id, (int) ($studentC['recurring_slot_id'] ?? 0));

        $dupes = array_filter($result['conflicts'], fn ($c) => ($c['type'] ?? '') === 'recurring_duplicate');
        $this->assertNotEmpty($dupes);
        $firstDup = reset($dupes);
        $this->assertSame((int) $slot->id, (int) ($firstDup['recurring_slot_id'] ?? 0));
    }

    /** Tenter d’enregistrer la même récurrence (même élève + même prof + même créneau) doit échouer. */
    #[Test]
    public function exact_duplicate_recurring_proposal_is_rejected(): void
    {
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'DUP-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => '2026-01-01',
            'expires_at' => '2027-12-31',
            'status' => 'active',
        ]);

        $instance->students()->attach($this->studentA->id);

        SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'open_slot_id' => null,
            'teacher_id' => $this->teacherA->id,
            'student_id' => $this->studentA->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:00:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $validator = new RecurringSlotValidator;
        $result = $validator->validateRecurringAvailabilityWithoutOpenSlot(
            $this->teacherA->id,
            $this->studentA->id,
            '2026-03-25',
            Carbon::WEDNESDAY,
            '17:00:00',
            '17:20:00',
            1,
            null,
            (int) $this->club->id
        );

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['conflicts']);
        $hasDup = false;
        foreach ($result['conflicts'] as $c) {
            if (($c['type'] ?? '') === 'recurring_duplicate') {
                $hasDup = true;
                break;
            }
        }
        $this->assertTrue($hasDup, 'Attendu au moins un conflit recurring_duplicate sur 26 semaines');
    }
}
