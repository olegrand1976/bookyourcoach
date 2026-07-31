<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\PlanningLessonResource;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class PlanningLessonResourceTest extends TestCase
{
    /** @test */
    public function it_exposes_grid_fields_without_remaining_appends_or_club_location(): void
    {
        $clubUser = $this->actingAsClub();
        $club = \App\Models\Club::find($clubUser->club_id);

        $teacherUser = User::factory()->create(['name' => 'Coach Grid']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        $studentUser = User::factory()->create(['name' => 'Élève Grid', 'phone' => '0600000001']);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'club_id' => $club->id,
            'phone' => '0600000002',
        ]);

        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create([
            'name' => 'Individuel',
            'discipline_id' => $discipline->id,
        ]);

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'RES-GRID',
            'name' => 'Template Grid',
            'total_lessons' => 10,
            'validity_months' => 4,
            'price' => 200,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-GRID',
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
            'start_time' => now()->addDay()->setTime(10, 0),
            'end_time' => now()->addDay()->setTime(11, 0),
            'status' => 'confirmed',
            'price' => 45,
        ]);
        $instance->lessons()->attach($lesson->id);

        $lesson->load([
            'teacher.user',
            'student.user',
            'student.subscriptionInstances' => fn ($q) => $q->select('subscription_instances.id'),
            'students.user',
            'courseType',
            'subscriptionInstances' => fn ($q) => $q->select('subscription_instances.id'),
            'lessonRecurringSlot',
        ]);
        $lesson->subscriptionInstances->each->setAppends([]);
        if ($lesson->student?->relationLoaded('subscriptionInstances')) {
            $lesson->student->subscriptionInstances->each->setAppends([]);
        }

        $payload = (new PlanningLessonResource($lesson))->resolve(Request::create('/'));

        $this->assertSame($lesson->id, $payload['id']);
        $this->assertSame('confirmed', $payload['status']);
        $this->assertEquals(45, $payload['price']);
        $this->assertSame('Coach Grid', $payload['teacher']['user']['name']);
        $this->assertSame('Élève Grid', $payload['student']['user']['name']);
        $this->assertSame('Individuel', $payload['course_type']['name']);
        $this->assertSame('Individuel', $payload['courseType']['name']);
        $this->assertNotEmpty($payload['subscription_instances']);
        $this->assertArrayHasKey('id', $payload['subscription_instances'][0]);
        $this->assertArrayNotHasKey('remaining_bookable', $payload['subscription_instances'][0]);
        $this->assertArrayNotHasKey('club', $payload);
        $this->assertArrayNotHasKey('location', $payload);
        $this->assertArrayHasKey('subscription_instances', $payload['student']);
        $this->assertArrayNotHasKey('remaining_bookable', $payload['student']['subscription_instances'][0] ?? []);
    }
}
