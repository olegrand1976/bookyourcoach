<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
