<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LegacyRecurringSlotService;
use App\Services\PlanningConsistencyCheckService;
use App\Services\ProlongRecurringSlotsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Garde-fous de la génération nocturne (incident du 12/11/2026) :
 * la nuit a recréé des cours de séries annulées, rouvert des séries terminées depuis des mois
 * et surchargé des créneaux. Chaque test fige une des règles corrigées.
 */
class RecurringNightRunSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionInstance $instance;

    private Student $student;

    private Teacher $teacher;

    /** Vendredi : le samedi suivant est le 14/11/2026. */
    private const NOW = '2026-11-13 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse(self::NOW));

        $this->club = Club::create(['name' => 'Club Test', 'email' => 'club@test.com', 'phone' => '0123456789', 'is_active' => true]);
        $this->courseType = CourseType::create(['name' => 'Cours', 'duration_minutes' => 20, 'price' => 18.00]);
        $this->location = Location::create(['name' => 'Manège', 'address' => '1 rue', 'city' => 'Ville', 'postal_code' => '7000', 'country' => 'Belgique']);
        $this->teacher = $this->makeTeacher('Enseignant A', 'a@test.com');
        $this->student = $this->makeStudent('eleve@test.com');

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement',
            'total_lessons' => 30,
            'free_lessons' => 0,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $this->instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(6),
            'status' => 'active',
        ]);
        $this->instance->students()->attach($this->student->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // --- Générateur -------------------------------------------------------------------------

    #[Test]
    public function cancelled_series_never_generates_lessons(): void
    {
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot(['status' => 'cancelled']);

        $stats = $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-12-31'));
        $all = $this->generator()->generateLessonsForAllActiveSlots(Carbon::now(), Carbon::parse('2026-12-31'));

        $this->assertSame(0, $stats['generated']);
        $this->assertSame(0, $all['generated']);
        $this->assertSame(0, Lesson::where('start_time', '>', Carbon::now())->count());
    }

    #[Test]
    public function lesson_deleted_by_club_is_not_recreated(): void
    {
        // Suppression club = suppression définitive + date sautée sur la série (LessonController).
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot();
        $this->lesson('2026-11-14 09:00')->forceDelete();
        $slot->skipOccurrenceDate('2026-11-14', 'Supprimé par le club');

        $this->generator()->generateLessonsForSlot($slot->fresh(), Carbon::now(), Carbon::parse('2026-11-21 23:59'));

        $this->assertSame(0, Lesson::whereDate('start_time', '2026-11-14')->count());
        $this->assertSame(1, Lesson::whereDate('start_time', '2026-11-21')->count());
    }

    #[Test]
    public function archived_lessons_do_not_block_regeneration_after_interval_change(): void
    {
        // Changement d'intervalle : les cours futurs sont archivés (soft delete) puis la série régénérée.
        $this->lesson('2026-11-07 09:00');
        $this->lesson('2026-11-14 09:00')->delete();
        $slot = $this->slot();

        $stats = $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-14 23:59'));

        $this->assertSame(1, $stats['generated']);
    }

    #[Test]
    public function capacity_counts_simultaneous_lessons_not_every_touching_lesson(): void
    {
        ClubOpenSlot::create([
            'club_id' => $this->club->id, 'day_of_week' => 6, 'start_time' => '08:40:00', 'end_time' => '18:00:00',
            'max_capacity' => 1, 'max_slots' => 2, 'duration' => 20, 'price' => 18, 'is_active' => true,
        ]);
        $this->lesson('2026-11-07 09:00');
        $b = $this->makeTeacher('Enseignant B', 'b@test.com');
        $c = $this->makeTeacher('Enseignant C', 'c@test.com');
        $this->lesson('2026-11-14 08:50', $b, $this->makeStudent('s1@test.com'));
        $this->lesson('2026-11-14 09:10', $c, $this->makeStudent('s2@test.com'));
        $slot = $this->slot();

        $stats = $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-14 23:59'));

        $this->assertSame(1, $stats['generated'], 'au plus 1 autre cours à chaque instant : il reste une place');
    }

    #[Test]
    public function skipped_occurrence_is_not_generated(): void
    {
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot(['skipped_dates' => ['2026-11-14']]);

        $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-21 23:59'));

        $this->assertSame(0, Lesson::whereDate('start_time', '2026-11-14')->count());
        $this->assertSame(1, Lesson::whereDate('start_time', '2026-11-21')->count());
    }

    #[Test]
    public function occurrence_is_not_generated_when_student_already_has_a_lesson_at_that_time(): void
    {
        $this->lesson('2026-11-07 09:00');
        $other = $this->makeTeacher('Enseignant B', 'b@test.com');
        $this->lesson('2026-11-14 09:00', $other);
        $slot = $this->slot();

        $stats = $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-14 23:59'));

        $this->assertSame(0, $stats['generated']);
        $this->assertSame('student_busy', $stats['conflicts'][0]['type']);
        $this->assertSame('2026-11-14', $stats['conflicts'][0]['date']);
    }

    #[Test]
    public function occurrence_is_not_generated_when_open_slot_is_full(): void
    {
        ClubOpenSlot::create([
            'club_id' => $this->club->id, 'day_of_week' => 6, 'start_time' => '08:40:00', 'end_time' => '18:00:00',
            'max_capacity' => 1, 'max_slots' => 1, 'duration' => 20, 'price' => 18, 'is_active' => true,
        ]);
        $this->lesson('2026-11-07 09:00');
        $this->lesson('2026-11-14 09:00', $this->makeTeacher('Enseignant B', 'b@test.com'), $this->makeStudent('autre@test.com'));
        $slot = $this->slot();

        $stats = $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-14 23:59'));

        $this->assertSame(0, $stats['generated']);
        $this->assertSame('capacity_full', $stats['conflicts'][0]['type']);
    }

    #[Test]
    public function generated_lesson_is_traced_in_action_log(): void
    {
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot();

        $this->generator()->generateLessonsForSlot($slot, Carbon::now(), Carbon::parse('2026-11-14 23:59'));

        $generated = Lesson::whereDate('start_time', '2026-11-14')->firstOrFail();
        $log = LessonActionLog::where('lesson_id', $generated->id)->firstOrFail();
        $this->assertSame(LessonActionLog::ACTION_GENERATED, $log->action);
        $this->assertSame('system', $log->performed_by_role);
        $this->assertSame((int) $slot->id, (int) $log->meta['recurring_slot_id']);
    }

    // --- Prolongation -----------------------------------------------------------------------

    #[Test]
    public function prolong_never_reopens_a_series_that_ended_long_ago(): void
    {
        $this->lesson('2026-03-07 09:00');
        $slot = $this->slot(['start_date' => '2026-01-03', 'end_date' => '2026-03-08']);

        $result = app(ProlongRecurringSlotsService::class)->prolongActiveRecurrences();

        $this->assertSame(0, $result['prolonged']);
        $this->assertSame('2026-03-08', $slot->fresh()->end_date->format('Y-m-d'));
        $this->assertSame(0, Lesson::where('start_time', '>', Carbon::now())->count());
    }

    #[Test]
    public function prolong_does_not_reopen_a_recently_ended_series_replaced_by_another(): void
    {
        // Cas Luis : série chez Theo finie le 10/10, élève passé chez un autre enseignant le 07/11.
        $this->lesson('2026-10-10 10:40');
        $old = $this->slot(['start_date' => '2026-04-11', 'end_date' => '2026-10-10', 'start_time' => '10:40:00', 'end_time' => '11:00:00']);
        $this->slot(['teacher_id' => $this->makeTeacher('Enseignant B', 'b@test.com')->id, 'start_date' => '2026-11-07', 'end_date' => '2027-05-14', 'start_time' => '10:40:00', 'end_time' => '11:00:00']);

        app(ProlongRecurringSlotsService::class)->prolongActiveRecurrences();

        $this->assertSame('2026-10-10', $old->fresh()->end_date->format('Y-m-d'));
    }

    #[Test]
    public function prolong_reopens_a_recently_ended_series_without_successor(): void
    {
        $this->lesson('2026-10-10 09:00');
        $slot = $this->slot(['start_date' => '2026-04-11', 'end_date' => '2026-10-10']);

        $result = app(ProlongRecurringSlotsService::class)->prolongActiveRecurrences();

        $this->assertSame(1, $result['prolonged']);
        $this->assertTrue($slot->fresh()->end_date->gt(Carbon::parse('2027-05-01')));
    }

    #[Test]
    public function prolong_respects_a_locked_end(): void
    {
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot(['end_date' => '2026-11-28', 'end_locked_at' => Carbon::now()]);

        $result = app(ProlongRecurringSlotsService::class)->prolongActiveRecurrences();

        $this->assertSame(0, $result['prolonged']);
        $this->assertSame('2026-11-28', $slot->fresh()->end_date->format('Y-m-d'));
    }

    #[Test]
    public function prolong_extends_a_running_series(): void
    {
        $this->lesson('2026-11-07 09:00');
        $slot = $this->slot(['end_date' => '2026-11-28']);

        $result = app(ProlongRecurringSlotsService::class)->prolongActiveRecurrences();

        $this->assertSame(1, $result['prolonged']);
        $this->assertTrue($slot->fresh()->end_date->gt(Carbon::parse('2027-05-01')));
        $this->assertSame('prolong', $result['details'][0]['action']);
    }

    #[Test]
    public function truncating_a_series_locks_its_end(): void
    {
        $slot = $this->slot();

        $slot->truncateFromDate(Carbon::parse('2026-11-21'), 'Fin voulue');

        $fresh = $slot->fresh();
        $this->assertSame('2026-11-20', $fresh->end_date->format('Y-m-d'));
        $this->assertNotNull($fresh->end_locked_at);
    }

    // --- Remise en état ---------------------------------------------------------------------

    #[Test]
    public function repair_command_deletes_untouched_generated_lessons_and_closes_series(): void
    {
        $this->lesson('2026-03-07 09:00');
        $slot = $this->slot(['start_date' => '2026-01-03', 'end_date' => '2027-05-14']);
        $untouched = $this->lesson('2026-11-14 09:00', notes: PlanningConsistencyCheckService::GENERATED_NOTE);
        $this->instance->lessons()->attach($untouched->id);
        $touched = $this->lesson('2026-11-21 09:00', notes: PlanningConsistencyCheckService::GENERATED_NOTE);
        LessonActionLog::create(['club_id' => $this->club->id, 'lesson_id' => $touched->id, 'action' => 'updated']);

        $this->artisan('recurring-slots:repair-night-run', [
            '--lessons' => "{$untouched->id},{$touched->id}",
            '--slot-ends' => "{$slot->id}:2026-03-08",
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertNull(Lesson::withTrashed()->find($untouched->id));
        $this->assertSame(0, DB::table('subscription_lessons')->where('lesson_id', $untouched->id)->count());
        $this->assertNotNull(Lesson::find($touched->id));
        // La lesson gardée (21/11) borne la nouvelle fin : la série n'est pas coupée avant elle.
        $fresh = $slot->fresh();
        $this->assertSame('2026-11-21', $fresh->end_date->format('Y-m-d'));
        $this->assertNotNull($fresh->end_locked_at);
    }

    #[Test]
    public function repair_command_simulates_by_default(): void
    {
        $generated = $this->lesson('2026-11-14 09:00', notes: PlanningConsistencyCheckService::GENERATED_NOTE);

        $this->artisan('recurring-slots:repair-night-run', ['--lessons' => (string) $generated->id])->assertSuccessful();

        $this->assertNotNull(Lesson::find($generated->id));
    }

    // --- Veille planning --------------------------------------------------------------------

    #[Test]
    public function consistency_check_flags_overbooked_slot_double_booking_and_orphan_generated_lesson(): void
    {
        ClubOpenSlot::create([
            'club_id' => $this->club->id, 'day_of_week' => 6, 'start_time' => '08:40:00', 'end_time' => '18:00:00',
            'max_capacity' => 1, 'max_slots' => 1, 'duration' => 20, 'price' => 18, 'is_active' => true,
        ]);
        DB::table('club_teachers')->insert(['club_id' => $this->club->id, 'teacher_id' => $this->teacher->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $other = $this->makeTeacher('Enseignant B', 'b@test.com');
        DB::table('club_teachers')->insert(['club_id' => $this->club->id, 'teacher_id' => $other->id, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);

        $this->lesson('2026-11-14 09:00');
        $this->lesson('2026-11-14 09:00', $other, notes: PlanningConsistencyCheckService::GENERATED_NOTE);

        $report = app(PlanningConsistencyCheckService::class)->checkDay($this->club, Carbon::parse('2026-11-14'));
        $types = array_column($report['issues'], 'type');

        $this->assertContains('capacity_exceeded', $types);
        $this->assertContains('student_double_booking', $types);
        $this->assertContains('inactive_teacher', $types);
        $this->assertContains('generated_without_series', $types);
        $this->assertGreaterThan(0, $report['counts']['critical']);
    }

    #[Test]
    public function consistency_check_is_silent_on_a_coherent_day(): void
    {
        ClubOpenSlot::create([
            'club_id' => $this->club->id, 'day_of_week' => 6, 'start_time' => '08:40:00', 'end_time' => '18:00:00',
            'max_capacity' => 1, 'max_slots' => 4, 'duration' => 20, 'price' => 18, 'is_active' => true,
        ]);
        DB::table('club_teachers')->insert(['club_id' => $this->club->id, 'teacher_id' => $this->teacher->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->lesson('2026-11-14 09:00');
        $this->lesson('2026-11-14 09:20', student: $this->makeStudent('autre@test.com'));

        $report = app(PlanningConsistencyCheckService::class)->checkDay($this->club, Carbon::parse('2026-11-14'));

        $this->assertSame([], $report['issues']);
    }

    #[Test]
    public function planning_issue_contains_no_personal_data(): void
    {
        $this->lesson('2026-11-14 09:00');
        $this->lesson('2026-11-14 09:00', $this->makeTeacher('Enseignant B', 'b@test.com'));
        $report = app(PlanningConsistencyCheckService::class)->checkDay($this->club, Carbon::parse('2026-11-14'));
        $this->assertNotSame([], $report['issues']);

        $reporter = app(\App\Services\PlanningIssueReporter::class);
        $text = $reporter->title($this->club, $report)."\n".$reporter->body($this->club, $report);

        $this->assertStringNotContainsString('Enseignant', $text);
        $this->assertStringNotContainsString('Élève', $text);
        $this->assertStringNotContainsString('@test.com', $text);
        $this->assertStringContainsString('student_double_booking', $text);
    }

    // --- Helpers ----------------------------------------------------------------------------

    private function generator(): LegacyRecurringSlotService
    {
        return app(LegacyRecurringSlotService::class);
    }

    private function makeTeacher(string $name, string $email): Teacher
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => bcrypt('password'), 'role' => 'teacher']);

        return Teacher::create(['user_id' => $user->id, 'club_id' => $this->club->id, 'is_available' => true]);
    }

    private function makeStudent(string $email): Student
    {
        $user = User::create(['name' => 'Élève', 'email' => $email, 'password' => bcrypt('password'), 'role' => 'student']);

        return Student::create(['user_id' => $user->id, 'club_id' => $this->club->id]);
    }

    private function lesson(string $start, ?Teacher $teacher = null, ?Student $student = null, ?string $notes = null): Lesson
    {
        $begin = Carbon::parse($start);

        return Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => ($teacher ?? $this->teacher)->id,
            'student_id' => ($student ?? $this->student)->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $begin,
            'end_time' => $begin->copy()->addMinutes(20),
            'status' => 'confirmed',
            'price' => 18.00,
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function slot(array $overrides = []): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create(array_merge([
            'subscription_instance_id' => $this->instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '09:00:00',
            'end_time' => '09:20:00',
            'start_date' => '2026-11-07',
            'end_date' => '2027-01-30',
            'status' => 'active',
        ], $overrides));
    }
}
