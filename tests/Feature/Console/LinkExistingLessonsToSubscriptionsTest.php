<?php

namespace Tests\Feature\Console;

use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkExistingLessonsToSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_links_unlinked_past_lesson_via_consume_lesson(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'LINK-CMD',
            'name' => 'Template link cmd',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'LINK-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $this->artisan('subscriptions:link-existing-lessons')
            ->assertSuccessful();

        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(1, $instance->fresh()->lessons_used);
    }

    #[Test]
    public function it_skips_link_when_attachment_slots_are_full(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'LINK-FULL',
            'name' => 'Template full',
            'total_lessons' => 2,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'LINK-FULL-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        for ($i = 0; $i < 2; $i++) {
            $attached = Lesson::factory()->create([
                'club_id' => $club->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'start_time' => Carbon::now()->addWeeks($i + 1),
                'end_time' => Carbon::now()->addWeeks($i + 1)->addHour(),
                'status' => 'confirmed',
            ]);
            $instance->consumeLesson($attached);
        }

        $unlinked = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $this->assertEquals(0, $instance->fresh()->getRemainingAttachmentSlots());

        $this->artisan('subscriptions:link-existing-lessons')
            ->assertSuccessful();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $unlinked->id,
        ]);
    }

    #[Test]
    public function dry_run_does_not_persist_links(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'LINK-DRY',
            'name' => 'Template dry run',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'LINK-DRY-001',
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $this->artisan('subscriptions:link-existing-lessons', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(0, $instance->fresh()->lessons_used);
    }
}
