<?php

namespace Tests\Feature\Api;

use App\Jobs\NotifyClubClosureRecipientsJob;
use App\Jobs\ProcessLessonPostCreationJob;
use App\Models\ClubClosureDay;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubClosureDayTest extends TestCase
{
    private function createSubscriptionInstanceForCourseType($club, Student $student, CourseType $courseType): SubscriptionInstance
    {
        $subscriptionData = [
            'club_id' => $club->id,
            'name' => 'Abonnement closure counters',
            'total_lessons' => 20,
            'price' => 150.00,
            'is_active' => true,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'validity_months')) {
            $subscriptionData['validity_months'] = 12;
        }
        $sub = Subscription::create($subscriptionData);
        if (!$courseType->discipline_id) {
            $courseType->discipline_id = Discipline::factory()->create()->id;
            $courseType->save();
        }
        DB::table('subscription_course_types')->insert([
            'subscription_id' => $sub->id,
            'discipline_id' => $courseType->discipline_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $sub->id,
            'started_at' => Carbon::now()->subMonth(),
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        return $instance;
    }

    private function seedSubscriptionForClub($club, Student $student, CourseType $courseType): SubscriptionInstance
    {
        $subscriptionData = [
            'club_id' => $club->id,
            'name' => 'Abonnement closure test',
            'total_lessons' => 10,
            'price' => 100.00,
            'is_active' => true,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'validity_months')) {
            $subscriptionData['validity_months'] = 12;
        }
        $sub = Subscription::create($subscriptionData);
        if (!$courseType->discipline_id) {
            $courseType->discipline_id = Discipline::factory()->create()->id;
            $courseType->save();
        }
        DB::table('subscription_course_types')->insert([
            'subscription_id' => $sub->id,
            'discipline_id' => $courseType->discipline_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $instance = SubscriptionInstance::create([
            'subscription_id' => $sub->id,
            'started_at' => Carbon::now()->subWeek(),
            'lessons_used' => 1,
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        return $instance;
    }

    #[Test]
    public function index_returns_closure_dates_in_range(): void
    {
        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        ClubClosureDay::create([
            'club_id' => $club->id,
            'closed_on' => '2026-05-10',
        ]);

        $response = $this->getJson('/api/club/closure-days?date_from=2026-05-01&date_to=2026-05-31');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertContains('2026-05-10', $response->json('data.dates'));
    }

    #[Test]
    public function closing_day_detaches_lesson_from_subscription_and_dispatches_notify_job(): void
    {
        Queue::fake();

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $instance = $this->seedSubscriptionForClub($club, $student, $courseType);

        $day = Carbon::now()->addDays(10)->format('Y-m-d');
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->confirmed()
            ->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $day.' 10:00:00',
                'end_time' => $day.' 11:00:00',
            ]);
        $instance->lessons()->attach($lesson->id);
        $instance->recalculateLessonsUsed();
        $instance->refresh();

        $response = $this->postJson('/api/club/closure-days', [
            'date' => $day,
            'closed' => true,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('subscription_lessons', [
            'lesson_id' => $lesson->id,
            'subscription_instance_id' => $instance->id,
        ]);

        Queue::assertPushed(NotifyClubClosureRecipientsJob::class, function (NotifyClubClosureRecipientsJob $job) use ($club, $day) {
            return $job->clubId === $club->id && $job->dateYmd === $day && $job->kind === 'closed';
        });
    }

    #[Test]
    public function opening_day_dispatches_reopened_notification_job(): void
    {
        Queue::fake();

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $day = '2026-06-15';
        ClubClosureDay::create([
            'club_id' => $club->id,
            'closed_on' => $day,
        ]);

        $response = $this->postJson('/api/club/closure-days', [
            'date' => $day,
            'closed' => false,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);

        Queue::assertPushed(NotifyClubClosureRecipientsJob::class, function (NotifyClubClosureRecipientsJob $job) use ($club, $day) {
            return $job->clubId === $club->id && $job->dateYmd === $day && $job->kind === 'reopened';
        });
    }

    #[Test]
    public function post_creation_job_skips_subscription_consumption_on_closure_day(): void
    {
        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();
        $this->seedSubscriptionForClub($club, $student, $courseType);

        $day = Carbon::now()->addDays(14)->format('Y-m-d');
        ClubClosureDay::create([
            'club_id' => $club->id,
            'closed_on' => $day,
        ]);

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->confirmed()
            ->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $day.' 14:00:00',
                'end_time' => $day.' 15:00:00',
            ]);

        $job = new ProcessLessonPostCreationJob($lesson);
        $job->handle();

        $this->assertDatabaseMissing('subscription_lessons', [
            'lesson_id' => $lesson->id,
        ]);
    }

    #[Test]
    public function closing_day_recalculates_lessons_used_with_past_present_future_split(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00'));
        try {
            $user = $this->actingAsClub();
            $club = $user->getFirstClub();
            $teacher = Teacher::factory()->create(['club_id' => $club->id]);
            $student = Student::factory()->create();
            $courseType = CourseType::factory()->create();
            $location = Location::factory()->create();
            $instance = $this->createSubscriptionInstanceForCourseType($club, $student, $courseType);

            $closureDay = '2026-07-10';

            $pastOnClosure = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 09:00:00',
                'end_time' => $closureDay . ' 09:20:00',
            ]);
            $presentOnClosure = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 12:00:00',
                'end_time' => $closureDay . ' 12:20:00',
            ]);
            $futureOnClosure = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 18:00:00',
                'end_time' => $closureDay . ' 18:20:00',
            ]);
            $pastOutsideClosure = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => '2026-07-03 10:00:00',
                'end_time' => '2026-07-03 10:20:00',
            ]);

            $instance->lessons()->attach([
                $pastOnClosure->id,
                $presentOnClosure->id,
                $futureOnClosure->id,
                $pastOutsideClosure->id,
            ]);
            $instance->recalculateLessonsUsed();
            $instance->refresh();
            $this->assertSame(3, (int) $instance->lessons_used, 'Avant fermeture: passé+présent comptent, futur non.');

            $response = $this->postJson('/api/club/closure-days', [
                'date' => $closureDay,
                'closed' => true,
            ]);
            $response->assertStatus(200)->assertJson(['success' => true]);

            $this->assertDatabaseMissing('subscription_lessons', ['subscription_instance_id' => $instance->id, 'lesson_id' => $pastOnClosure->id]);
            $this->assertDatabaseMissing('subscription_lessons', ['subscription_instance_id' => $instance->id, 'lesson_id' => $presentOnClosure->id]);
            $this->assertDatabaseMissing('subscription_lessons', ['subscription_instance_id' => $instance->id, 'lesson_id' => $futureOnClosure->id]);
            $this->assertDatabaseHas('subscription_lessons', ['subscription_instance_id' => $instance->id, 'lesson_id' => $pastOutsideClosure->id]);

            $instance->refresh();
            $this->assertSame(1, (int) $instance->lessons_used, 'Après fermeture: seule la séance passée hors jour de congés reste comptée.');
            $this->assertSame('active', $instance->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function closing_day_reopens_completed_subscription(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00'));
        try {
            $user = $this->actingAsClub();
            $club = $user->getFirstClub();
            $teacher = Teacher::factory()->create(['club_id' => $club->id]);
            $student = Student::factory()->create();
            $courseType = CourseType::factory()->create();
            $location = Location::factory()->create();

            $template = \App\Models\SubscriptionTemplate::create([
                'club_id' => $club->id,
                'model_number' => 'CLOSURE-REOPEN',
                'name' => 'Template reopen',
                'total_lessons' => 3,
                'validity_months' => 4,
                'price' => 150.00,
                'is_active' => true,
            ]);
            $template->courseTypes()->attach($courseType->id);

            $subscription = Subscription::create([
                'club_id' => $club->id,
                'subscription_template_id' => $template->id,
                'subscription_number' => 'CLOSURE-REOPEN-001',
            ]);

            $instance = SubscriptionInstance::create([
                'subscription_id' => $subscription->id,
                'started_at' => Carbon::now()->subMonth(),
                'lessons_used' => 0,
                'status' => 'active',
            ]);
            $instance->students()->attach($student->id);

            $closureDay = '2026-07-10';
            $pastOnClosure1 = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 09:00:00',
                'end_time' => $closureDay . ' 09:20:00',
            ]);
            $pastOnClosure2 = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 10:00:00',
                'end_time' => $closureDay . ' 10:20:00',
            ]);
            $presentOnClosure = Lesson::factory()->forClub($club)->forTeacher($teacher)->forStudent($student)->confirmed()->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $closureDay . ' 12:00:00',
                'end_time' => $closureDay . ' 12:20:00',
            ]);

            foreach ([$pastOnClosure1, $pastOnClosure2, $presentOnClosure] as $lesson) {
                $instance->lessons()->attach($lesson->id);
            }
            $instance->recalculateLessonsUsed();
            $instance->checkAndUpdateStatus();
            $instance->refresh();

            $this->assertSame(3, (int) $instance->lessons_used);
            $this->assertSame('completed', $instance->status);

            $response = $this->postJson('/api/club/closure-days', [
                'date' => $closureDay,
                'closed' => true,
            ]);
            $response->assertStatus(200)->assertJson(['success' => true]);

            $instance->refresh();
            $this->assertSame(0, (int) $instance->lessons_used);
            $this->assertSame('active', $instance->status);
            $this->assertSame(3, $instance->getRemainingAttachmentSlots());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{club: \App\Models\Club, teacher: Teacher, student: Student, day: string, lesson: Lesson}
     */
    private function seedStudentLessonOnFutureDay(): array
    {
        $studentUser = \App\Models\User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'is_active' => true,
        ]);
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $clubUser = $this->actingAsClub();
        $club = $clubUser->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $day = Carbon::now()->addDays(12)->format('Y-m-d');
        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->confirmed()
            ->create([
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => $day.' 10:00:00',
                'end_time' => $day.' 11:00:00',
            ]);

        return [
            'club' => $club,
            'teacher' => $teacher,
            'student' => $student,
            'studentUser' => $studentUser,
            'clubUser' => $clubUser,
            'day' => $day,
            'lesson' => $lesson,
        ];
    }

    #[Test]
    public function closure_day_hides_lessons_from_student_bookings_by_default(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($seed['lesson']->id, $ids);
        $this->assertSame('confirmed', $seed['lesson']->fresh()->status);
    }

    #[Test]
    public function closure_day_lessons_visible_with_include_cancelled_and_flagged(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings?include_cancelled=true');
        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $seed['lesson']->id);
        $this->assertNotNull($row);
        $this->assertTrue((bool) $row['is_on_closure_day']);
        $this->assertSame('confirmed', $row['status']);
    }

    #[Test]
    public function reopening_closure_day_restores_student_booking_visibility(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => false,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($seed['lesson']->id, $ids);
    }

    #[Test]
    public function closure_day_on_club_a_does_not_hide_lesson_on_club_b_same_date(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $otherClub = \App\Models\Club::factory()->create();
        $otherTeacher = Teacher::factory()->create(['club_id' => $otherClub->id]);
        $otherLesson = Lesson::factory()
            ->forClub($otherClub)
            ->forTeacher($otherTeacher)
            ->forStudent($seed['student'])
            ->confirmed()
            ->create([
                'course_type_id' => $seed['lesson']->course_type_id,
                'location_id' => $seed['lesson']->location_id,
                'start_time' => $seed['day'].' 14:00:00',
                'end_time' => $seed['day'].' 15:00:00',
            ]);

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings');
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($seed['lesson']->id, $ids);
        $this->assertContains($otherLesson->id, $ids);
    }

    #[Test]
    public function club_lessons_index_still_includes_lessons_on_closure_day(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        $response = $this->getJson('/api/lessons?date_from='.$seed['day'].'&date_to='.$seed['day']);
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($seed['lesson']->id, $ids);
    }

    #[Test]
    public function teacher_lessons_index_excludes_lessons_on_closure_day(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        $teacherUser = \App\Models\User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
            'is_active' => true,
        ]);
        $seed['teacher']->update(['user_id' => $teacherUser->id]);

        Sanctum::actingAs($teacherUser);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/lessons?date_from='.$seed['day'].'&date_to='.$seed['day']);
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($seed['lesson']->id, $ids);
    }

    #[Test]
    public function closure_day_hides_lessons_from_student_bookings_with_confirmed_status_filter(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings?status=confirmed');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($seed['lesson']->id, $ids);
    }

    #[Test]
    public function closure_day_hides_lessons_from_student_lessons_index(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/lessons?date_from='.$seed['day'].'&date_to='.$seed['day']);
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($seed['lesson']->id, $ids);
    }

    #[Test]
    public function lesson_history_includes_closure_day_lessons_with_flag(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/lesson-history');
        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $seed['lesson']->id);
        $this->assertNotNull($row);
        $this->assertTrue((bool) $row['is_on_closure_day']);
        $this->assertSame('confirmed', $row['status']);
    }

    #[Test]
    public function closure_day_hides_lessons_from_student_bookings_with_pending_status_filter(): void
    {
        Queue::fake();
        $seed = $this->seedStudentLessonOnFutureDay();
        $seed['lesson']->update(['status' => 'pending']);

        $this->postJson('/api/club/closure-days', [
            'date' => $seed['day'],
            'closed' => true,
        ])->assertStatus(200);

        Sanctum::actingAs($seed['studentUser']);
        $this->withHeaders(['Accept' => 'application/json']);

        $response = $this->getJson('/api/student/bookings?status=pending');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($seed['lesson']->id, $ids);
    }
}
