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
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClubCancelledLessonsTest extends TestCase
{
    #[Test]
    public function cancelled_list_is_scoped_to_club(): void
    {
        if (! Schema::hasColumn('lessons', 'cancelled_at')) {
            $this->markTestSkipped('Colonnes audit annulation absentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $otherClub = Club::factory()->create();

        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $otherTeacher = Teacher::factory()->create(['club_id' => $otherClub->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->addDays(3);

        $ours = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'cancelled',
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'cancelled_at' => now(),
                'cancelled_by_role' => 'club',
            ]);

        Lesson::factory()
            ->forClub($otherClub)
            ->forTeacher($otherTeacher)
            ->forStudent($student)
            ->create([
                'status' => 'cancelled',
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'cancelled_at' => now(),
                'cancelled_by_role' => 'club',
            ]);

        $response = $this->getJson('/api/club/lessons/cancelled');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($ours->id, $ids);
        $this->assertCount(1, $ids);
    }

    #[Test]
    public function reactivate_single_lesson_restores_confirmed_status(): void
    {
        if (! Schema::hasColumn('lessons', 'cancelled_at')) {
            $this->markTestSkipped('Colonnes audit annulation absentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->addDays(5);

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'cancelled',
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'cancelled_at' => now(),
                'cancelled_by_role' => 'student',
                'cancelled_subscription_instance_ids' => [],
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/reactivate", [
            'reactivate_scope' => 'single',
            'restore_recurring_slot' => false,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $lesson->refresh();
        $this->assertSame('confirmed', $lesson->status);
        $this->assertStringContainsString('[Réactivé par le club', $lesson->notes ?? '');
    }

    #[Test]
    public function reactivate_restores_cancelled_recurring_slot(): void
    {
        if (! Schema::hasColumn('lessons', 'cancelled_at')) {
            $this->markTestSkipped('Colonnes audit annulation absentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $template = \App\Models\SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'REACT001',
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-REACT-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(6),
        ]);
        $instance->students()->attach($student->id);

        $start = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0, 0);

        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => $start->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 1,
            'start_date' => $start->copy()->subWeek()->toDateString(),
            'end_date' => $start->copy()->addMonths(3)->toDateString(),
            'status' => 'cancelled',
        ]);

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'cancelled',
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
                'cancelled_at' => now(),
                'cancelled_by_role' => 'club',
                'cancelled_subscription_instance_ids' => [$instance->id],
            ]);

        $response = $this->postJson("/api/club/lessons/{$lesson->id}/reactivate", [
            'reactivate_scope' => 'single',
            'restore_recurring_slot' => true,
            'reattach_subscription' => true,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $recurringSlot->refresh();
        $this->assertSame('active', $recurringSlot->status);
    }

    #[Test]
    public function cancel_with_future_sets_cancellation_audit_fields(): void
    {
        if (! Schema::hasColumn('lessons', 'cancelled_at')) {
            $this->markTestSkipped('Colonnes audit annulation absentes.');
        }

        $user = $this->actingAsClub();
        $club = $user->getFirstClub();
        $teacher = Teacher::factory()->create(['club_id' => $club->id]);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $start = Carbon::now()->addDays(4);

        $lesson = Lesson::factory()
            ->forClub($club)
            ->forTeacher($teacher)
            ->forStudent($student)
            ->create([
                'status' => 'confirmed',
                'start_time' => $start,
                'end_time' => $start->copy()->addHour(),
                'course_type_id' => $courseType->id,
                'location_id' => $location->id,
            ]);

        $response = $this->postJson("/api/lessons/{$lesson->id}/cancel-with-future", [
            'cancel_scope' => 'single',
            'action' => 'cancel',
            'reason' => 'Test audit',
        ]);

        $response->assertStatus(200);
        $lesson->refresh();
        $this->assertSame('cancelled', $lesson->status);
        $this->assertNotNull($lesson->cancelled_at);
        $this->assertSame('club', $lesson->cancelled_by_role);
        $this->assertSame($user->id, $lesson->cancelled_by_user_id);
    }
}
