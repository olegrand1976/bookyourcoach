<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Services\LegacyRecurringSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecurringSlotMaterializeLessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_club_can_materialize_lesson_for_recurring_occurrence_date(): void
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $this->assertNotNull($club);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MAT001',
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-MAT-'.uniqid(),
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2027-01-01'),
        ]);
        $subscriptionInstance->students()->attach($student->id);

        Lesson::create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::parse('2026-01-05 10:00:00'),
            'end_time' => Carbon::parse('2026-01-05 11:00:00'),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $subscriptionInstance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $targetYmd = '2026-01-12';
        $this->assertFalse(
            Lesson::where('teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->whereDate('start_time', $targetYmd)
                ->exists()
        );

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => $targetYmd,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.already_existed', false)
            ->assertJsonStructure(['data' => ['lesson', 'already_existed']]);

        $this->assertTrue(
            Lesson::where('teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->whereDate('start_time', $targetYmd)
                ->exists()
        );

        $second = $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => $targetYmd,
        ]);
        $second->assertOk()->assertJsonPath('data.already_existed', true);
    }

    public function test_materialize_reactivates_cancelled_lesson_instead_of_reporting_already_existed(): void
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MAT003',
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-MAT3-'.uniqid(),
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2027-01-01'),
        ]);
        $subscriptionInstance->students()->attach($student->id);

        $cancelled = Lesson::create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::parse('2026-01-12 10:00:00'),
            'end_time' => Carbon::parse('2026-01-12 11:00:00'),
            'status' => 'cancelled',
            'cancelled_by_role' => 'club',
            'price' => 50.00,
        ]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $subscriptionInstance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => '2026-01-12',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reactivated', true)
            ->assertJsonPath('data.already_existed', false)
            ->assertJsonPath('data.lesson.id', $cancelled->id);

        $this->assertSame('confirmed', $cancelled->fresh()->status);
        $this->assertSame(1, Lesson::where('student_id', $student->id)->whereDate('start_time', '2026-01-12')->count());
    }

    public function test_materialize_returns_422_when_date_not_in_series_pattern(): void
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MAT002',
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-MAT2-'.uniqid(),
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2027-01-01'),
        ]);
        $subscriptionInstance->students()->attach($student->id);

        Lesson::create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::parse('2026-01-05 10:00:00'),
            'end_time' => Carbon::parse('2026-01-05 11:00:00'),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $subscriptionInstance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 2,
            'start_date' => '2026-01-05',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => '2026-01-12',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_materialize_rolls_over_to_next_active_subscription_when_series_one_is_full(): void
    {
        $ctx = $this->makeSeriesContext();
        $full = $this->makeInstance($ctx, 'SUB-FULL', '2026-01-01', '2027-01-01');
        $full->resetLessonsUsedAt(2, Carbon::parse('2026-01-01'));
        $full->save();
        $next = $this->makeInstance($ctx, 'SUB-NEXT', '2026-01-01', '2027-01-01');
        $slot = $this->makeSlot($ctx, $full);

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => '2026-01-12',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $lessonId = $response->json('data.lesson.id');
        $this->assertTrue($next->lessons()->where('lessons.id', $lessonId)->exists());
        $this->assertFalse($full->lessons()->where('lessons.id', $lessonId)->exists());
        $this->assertSame($next->id, (int) $slot->fresh()->subscription_instance_id);
    }

    public function test_materialize_does_not_count_lessons_on_club_closure_days(): void
    {
        $ctx = $this->makeSeriesContext();
        $instance = $this->makeInstance($ctx, 'SUB-CLOSED', '2026-01-01', '2027-01-01');
        $slot = $this->makeSlot($ctx, $instance);

        // Cours rattachés rétroactivement alors que le club était fermé ces jours-là.
        foreach (['2026-01-19', '2026-01-26'] as $ymd) {
            ClubClosureDay::create(['club_id' => $ctx['club']->id, 'closed_on' => $ymd]);
            $lesson = $this->makeLesson($ctx, $ymd);
            DB::table('subscription_lessons')->insert([
                'subscription_instance_id' => $instance->id,
                'lesson_id' => $lesson->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => '2026-01-12',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_materialize_explains_when_no_subscription_has_room(): void
    {
        $ctx = $this->makeSeriesContext();
        $full = $this->makeInstance($ctx, 'SUB-ONLY', '2026-01-01', '2027-01-01');
        $full->resetLessonsUsedAt(2, Carbon::parse('2026-01-01'));
        $full->save();
        $slot = $this->makeSlot($ctx, $full);

        $this->postJson("/api/club/recurring-slots/{$slot->id}/materialize-lesson", [
            'date' => '2026-01-12',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Aucune place restante sur l\'abonnement SUB-ONLY et aucun autre abonnement actif de l\'élève ne couvre ce cours.');
    }

    public function test_generation_continues_on_next_active_subscription_instead_of_leaving_holes(): void
    {
        $ctx = $this->makeSeriesContext();
        // Le cours de référence (05/01) occupe déjà une des deux places.
        $first = $this->makeInstance($ctx, 'SUB-FIRST', '2026-01-01', '2027-01-01');
        $first->lessons()->attach(Lesson::where('student_id', $ctx['student']->id)->value('id'));
        $next = $this->makeInstance($ctx, 'SUB-SECOND', '2026-01-01', '2027-01-01');
        $slot = $this->makeSlot($ctx, $first);

        $stats = (new LegacyRecurringSlotService)->generateLessonsForSlot(
            $slot,
            Carbon::parse('2026-01-12'),
            Carbon::parse('2026-01-26')
        );

        $this->assertSame(3, $stats['generated']);
        $this->assertSame(2, $first->lessons()->count());
        $this->assertSame(2, $next->lessons()->count());
        $this->assertSame($next->id, (int) $slot->fresh()->subscription_instance_id);
    }

    /**
     * Club, enseignant, élève, type de cours et un cours de référence le lundi 05/01/2026 à 10h.
     *
     * @return array{club: Club, teacher: Teacher, student: Student, courseType: CourseType, location: Location}
     */
    private function makeSeriesContext(): array
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $ctx = [
            'club' => $club,
            'teacher' => $teacher,
            'student' => Student::factory()->create(['club_id' => $club->id]),
            'courseType' => CourseType::factory()->create(),
            'location' => Location::factory()->create(),
        ];

        $this->makeLesson($ctx, '2026-01-05');

        return $ctx;
    }

    private function makeInstance(array $ctx, string $number, string $startedAt, string $expiresAt): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $ctx['club']->id,
            'model_number' => 'TPL-'.$number,
            'total_lessons' => 2,
            'free_lessons' => 0,
            'validity_months' => 12,
            'price' => 100.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($ctx['courseType']->id);

        $subscription = Subscription::create([
            'club_id' => $ctx['club']->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => $number,
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse($startedAt),
            'expires_at' => Carbon::parse($expiresAt),
        ]);
        $instance->students()->attach($ctx['student']->id);

        return $instance;
    }

    private function makeSlot(array $ctx, SubscriptionInstance $instance): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $ctx['teacher']->id,
            'student_id' => $ctx['student']->id,
            'day_of_week' => Carbon::MONDAY,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
    }

    private function makeLesson(array $ctx, string $ymd): Lesson
    {
        return Lesson::create([
            'club_id' => $ctx['club']->id,
            'teacher_id' => $ctx['teacher']->id,
            'student_id' => $ctx['student']->id,
            'course_type_id' => $ctx['courseType']->id,
            'location_id' => $ctx['location']->id,
            'start_time' => Carbon::parse("{$ymd} 10:00:00"),
            'end_time' => Carbon::parse("{$ymd} 11:00:00"),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
    }
}
