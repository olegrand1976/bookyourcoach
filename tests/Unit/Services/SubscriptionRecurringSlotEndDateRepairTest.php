<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Discipline;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Teacher;
use App\Models\User;
use App\Services\RecurringSlotValidator;
use App\Services\SubscriptionRecurringSlotDiagnosticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Garde end_date >= start_date à la création et réparation diagnostics.
 */
class SubscriptionRecurringSlotEndDateRepairTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private SubscriptionInstance $instance;

    private Student $student;

    private Teacher $teacher;

    private ClubOpenSlot $openSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->club = Club::create([
            'name' => 'Club EndDate',
            'email' => 'enddate@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $discipline = Discipline::create([
            'name' => 'Tennis',
            'slug' => 'tennis-enddate-'.uniqid(),
            'is_active' => true,
        ]);

        $tu = User::create([
            'name' => 'Teacher End',
            'email' => 'teacher-end-'.uniqid().'@test.com',
            'password' => bcrypt('x'),
            'role' => 'teacher',
        ]);
        $su = User::create([
            'name' => 'Student End',
            'email' => 'student-end-'.uniqid().'@test.com',
            'password' => bcrypt('x'),
            'role' => 'student',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $tu->id,
            'club_id' => $this->club->id,
            'is_available' => true,
        ]);
        $this->student = Student::create([
            'user_id' => $su->id,
            'club_id' => $this->club->id,
        ]);

        $sub = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Sub End',
            'total_lessons' => 20,
            'free_lessons' => 0,
            'price' => 100,
            'is_active' => true,
        ]);

        $this->instance = SubscriptionInstance::create([
            'subscription_id' => $sub->id,
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2026-03-24'),
            'status' => 'active',
        ]);

        $this->openSlot = ClubOpenSlot::create([
            'club_id' => $this->club->id,
            'discipline_id' => $discipline->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:20:00',
            'end_time' => '17:40:00',
            'max_slots' => 2,
            'max_capacity' => 10,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function createRecurringSlot_keeps_end_after_start_when_subscription_already_expired(): void
    {
        $validator = new RecurringSlotValidator;
        $slot = $validator->createRecurringSlot(
            $this->instance,
            (int) $this->openSlot->id,
            (int) $this->teacher->id,
            (int) $this->student->id,
            '2026-09-02'
        );

        $start = Carbon::parse($slot->start_date)->startOfDay();
        $end = Carbon::parse($slot->end_date)->startOfDay();

        $this->assertTrue($end->gte($start), 'end_date must be >= start_date');
        $this->assertSame(
            $start->copy()->addWeeks(SubscriptionRecurringSlot::RECURRENCE_WEEKS)->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        // Même condition que LegacyRecurringSlotService::generateDatesForRecurringSlot
        $this->assertTrue(
            $start->lte($end),
            'La boucle de génération (while current lte end) doit pouvoir démarrer'
        );
    }

    #[Test]
    public function diagnostics_repairs_inverted_range_before_regenerate(): void
    {
        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:20:00',
            'end_time' => '17:40:00',
            'recurring_interval' => 1,
            'start_date' => '2026-09-02',
            'end_date' => '2026-03-24',
            'status' => 'active',
        ]);

        $service = app(SubscriptionRecurringSlotDiagnosticsService::class);
        $diag = $service->diagnoseSlot($slot);
        $codes = collect($diag['issues'])->pluck('code')->all();
        $this->assertContains(SubscriptionRecurringSlotDiagnosticsService::ISSUE_INVERTED_DATE_RANGE, $codes);
        $this->assertContains(SubscriptionRecurringSlotDiagnosticsService::ISSUE_NO_FUTURE_LESSONS, $codes);
        $this->assertTrue($diag['can_regenerate']);

        $repaired = $service->repairInvertedDateRangeIfNeeded($slot->fresh());
        $this->assertTrue($repaired);

        $slot->refresh();
        $this->assertTrue(
            Carbon::parse($slot->end_date)->gte(Carbon::parse($slot->start_date))
        );
        $this->assertSame(
            Carbon::parse('2026-09-02')->addWeeks(SubscriptionRecurringSlot::RECURRENCE_WEEKS)->format('Y-m-d'),
            Carbon::parse($slot->end_date)->format('Y-m-d')
        );
    }

    #[Test]
    public function regenerateFutureLessons_repairs_inverted_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 12:00:00', 'Europe/Paris'));

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::WEDNESDAY,
            'start_time' => '17:20:00',
            'end_time' => '17:40:00',
            'recurring_interval' => 1,
            'start_date' => '2026-09-02',
            'end_date' => '2026-03-24',
            'status' => 'active',
        ]);

        $service = app(SubscriptionRecurringSlotDiagnosticsService::class);
        $service->regenerateFutureLessons($slot);

        $slot->refresh();
        $this->assertTrue(
            Carbon::parse($slot->end_date)->gte(Carbon::parse($slot->start_date)),
            'regenerate doit réparer end_date avant génération'
        );
        $this->assertSame(
            Carbon::parse('2026-09-02')->addWeeks(SubscriptionRecurringSlot::RECURRENCE_WEEKS)->format('Y-m-d'),
            Carbon::parse($slot->end_date)->format('Y-m-d')
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function resolveEndDateWithMeta_flags_ignored_expired_expires(): void
    {
        $start = Carbon::parse('2026-09-02')->startOfDay();
        $expires = Carbon::parse('2026-03-24')->startOfDay();
        [$end, $ignored] = SubscriptionRecurringSlot::resolveEndDateWithMeta($start, $expires);

        $this->assertTrue($ignored);
        $this->assertSame(
            $start->copy()->addWeeks(SubscriptionRecurringSlot::RECURRENCE_WEEKS)->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        [$end2, $ignored2] = SubscriptionRecurringSlot::resolveEndDateWithMeta(
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-06-15')
        );
        $this->assertFalse($ignored2);
        $this->assertSame('2026-06-15', $end2->format('Y-m-d'));
    }
}
