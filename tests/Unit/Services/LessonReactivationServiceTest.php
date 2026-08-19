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
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Services\LessonReactivationService;
use Carbon\Carbon;
use Tests\TestCase;

class LessonReactivationServiceTest extends TestCase
{
    /** @test */
    public function find_matching_recurring_slot_locates_cancelled_slot_by_instance_and_time(): void
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
            'model_number' => 'MATCH001',
            'total_lessons' => 10,
            'validity_months' => 6,
            'price' => 100,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-MATCH-'.uniqid(),
        ]);
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
        ]);
        $instance->students()->attach($student->id);

        $start = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0, 0);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'day_of_week' => $start->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'recurring_interval' => 1,
            'start_date' => $start->copy()->subWeek()->toDateString(),
            'end_date' => $start->copy()->addMonths(2)->toDateString(),
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
                'cancelled_subscription_instance_ids' => [$instance->id],
            ]);

        $matched = app(LessonReactivationService::class)->findMatchingRecurringSlot($lesson);

        $this->assertNotNull($matched);
        $this->assertSame($slot->id, $matched->id);
    }
}
