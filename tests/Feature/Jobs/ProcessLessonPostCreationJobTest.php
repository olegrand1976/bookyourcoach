<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessLessonPostCreationJob;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Tests\TestCase;

class ProcessLessonPostCreationJobTest extends TestCase
{
    /** @test */
    public function it_skips_subscription_consumption_when_deduct_from_subscription_is_false(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MOD-JOB',
            'name' => 'Template job',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'JOB-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
            'deduct_from_subscription' => false,
        ]);

        (new ProcessLessonPostCreationJob($lesson, 1, false))->handle();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(0, $instance->fresh()->lessons_used);
    }

    /** @test */
    public function it_consumes_subscription_when_deduct_from_subscription_is_true(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MOD-JOB-2',
            'name' => 'Template job 2',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'JOB-002',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
            'deduct_from_subscription' => true,
        ]);

        (new ProcessLessonPostCreationJob($lesson, 0, true))->handle();

        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /** @test */
    public function it_skips_subscription_even_with_recurring_interval_when_deduct_is_false(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MOD-JOB-3',
            'name' => 'Template job 3',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'JOB-003',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
            'deduct_from_subscription' => false,
        ]);

        (new ProcessLessonPostCreationJob($lesson, 2, false))->handle();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(0, $instance->fresh()->lessons_used);
    }

    /** @test */
    public function it_consumes_subscription_for_collective_lesson_without_student_id(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MOD-JOB-PIVOT',
            'name' => 'Template job pivot',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'JOB-PIVOT-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        // Cours collectif sans student_id : le bénéficiaire n'est rattaché que via le pivot.
        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => null,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
            'deduct_from_subscription' => true,
        ]);
        $lesson->students()->attach($student->id, ['status' => 'pending']);

        (new ProcessLessonPostCreationJob($lesson, 0, true))->handle();

        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
        // Cours futur : 1 place réservée (les futurs ne montent dans lessons_used qu'une fois passés).
        $this->assertEquals(1, $instance->fresh()->getAttachedCountableLessonsCount());
    }
}
