<?php

namespace Tests\Unit\Observers;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LessonObserverTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionInstance $subscriptionInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $club = Club::create([
            'name' => 'Club Observer',
            'email' => 'observer@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $discipline = Discipline::create([
            'name' => 'Discipline Observer',
            'slug' => 'discipline-observer',
            'is_active' => true,
        ]);

        $courseType = CourseType::create([
            'name' => 'Cours Observer',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $discipline->id,
        ]);

        Location::create([
            'name' => 'Lieu Observer',
            'address' => '1 rue test',
            'city' => 'Test',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        $location = Location::first();

        $teacherUser = User::create([
            'name' => 'Teacher Observer',
            'email' => 'teacher-observer@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'club_id' => $club->id,
            'is_available' => true,
        ]);

        $studentUser = User::create([
            'name' => 'Student Observer',
            'email' => 'student-observer@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'club_id' => $club->id,
        ]);

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'OBS-01',
            'name' => 'Template observer',
            'total_lessons' => 10,
            'validity_months' => 3,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
        ]);

        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);
        $this->subscriptionInstance->students()->attach($student->id);

        $this->club = $club;
        $this->teacher = $teacher;
        $this->student = $student;
        $this->courseType = $courseType;
        $this->location = $location;
    }

    private Club $club;
    private Teacher $teacher;
    private Student $student;
    private CourseType $courseType;
    private Location $location;

    #[Test]
    public function reactivation_restores_subscription_link_automatically(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $this->subscriptionInstance->consumeLesson($lesson);

        $lesson->update([
            'status' => 'cancelled',
            'cancellation_count_in_subscription' => false,
        ]);

        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(0, $this->subscriptionInstance->fresh()->lessons_used);

        $lesson->update(['status' => 'confirmed']);

        $this->assertDatabaseHas('subscription_lessons', [
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(1, $this->subscriptionInstance->fresh()->lessons_used);
        $this->assertNull($lesson->fresh()->cancelled_subscription_instance_ids);
    }

    #[Test]
    public function reactivation_does_not_double_count_when_already_linked(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $this->subscriptionInstance->consumeLesson($lesson);

        $lesson->update([
            'status' => 'cancelled',
            'cancellation_count_in_subscription' => true,
        ]);
        $this->assertEquals(1, $this->subscriptionInstance->fresh()->lessons_used);

        $lesson->update(['status' => 'confirmed']);

        $this->assertEquals(1, $this->subscriptionInstance->fresh()->lessons_used);
        $this->assertEquals(
            1,
            $this->subscriptionInstance->lessons()->where('lesson_id', $lesson->id)->count()
        );
    }
}
