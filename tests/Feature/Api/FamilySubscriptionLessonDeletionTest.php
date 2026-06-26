<?php

namespace Tests\Feature\Api;

use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FamilySubscriptionLessonDeletionTest extends TestCase
{
    use RefreshDatabase;

    private $club;

    private Student $studentA;

    private Student $studentB;

    private Teacher $teacher;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsClub();
        $this->club = \App\Models\Club::find(Auth::user()->club_id);

        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);

        $this->studentA = Student::factory()->create(['club_id' => $this->club->id]);
        $this->studentB = Student::factory()->create(['club_id' => $this->club->id]);

        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'FAM-DEL-001',
        ]);

        $this->instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(6),
        ]);

        $this->instance->students()->attach([$this->studentA->id, $this->studentB->id]);
    }

    private function attachLesson(Lesson $lesson): void
    {
        $lesson->subscriptionInstances()->attach($this->instance->id);
    }

    #[Test]
    public function single_delete_does_not_remove_sibling_lesson_same_day(): void
    {
        $sameDay = '2026-04-15 10:00:00';

        $lessonA = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $sameDay,
            'end_time' => '2026-04-15 11:00:00',
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonA);

        $lessonB = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-04-15 14:00:00',
            'end_time' => '2026-04-15 15:00:00',
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonB);

        $response = $this->deleteJson("/api/club/lessons/{$lessonA->id}", [
            'cancel_scope' => 'single',
            'action' => 'delete',
            'reason' => 'Test suppression isolée',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.processed_count', 1);

        $this->assertSoftDeleted('lessons', ['id' => $lessonA->id]);
        $this->assertDatabaseHas('lessons', ['id' => $lessonB->id, 'status' => 'confirmed']);
    }

    #[Test]
    public function all_future_delete_only_affects_same_student_same_slot(): void
    {
        $slotStart = Carbon::parse('next wednesday')->setTime(10, 0, 0);
        $slotEnd = $slotStart->copy()->addHour();

        $lessonA1 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy(),
            'end_time' => $slotEnd->copy(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonA1);

        $lessonAFuture = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy()->addWeek(),
            'end_time' => $slotEnd->copy()->addWeek(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonAFuture);

        $lessonBSameDay = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy()->addHours(2),
            'end_time' => $slotEnd->copy()->addHours(2),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonBSameDay);

        $lessonBFuture = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy()->addWeek()->addHours(2),
            'end_time' => $slotEnd->copy()->addWeek()->addHours(2),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonBFuture);

        $response = $this->deleteJson("/api/club/lessons/{$lessonA1->id}", [
            'cancel_scope' => 'all_future',
            'action' => 'delete',
        ]);

        $response->assertStatus(200);
        $processed = $response->json('data.processed_lesson_ids');
        $this->assertContains($lessonA1->id, $processed);
        $this->assertContains($lessonAFuture->id, $processed);
        $this->assertNotContains($lessonBSameDay->id, $processed);
        $this->assertNotContains($lessonBFuture->id, $processed);

        $this->assertDatabaseHas('lessons', ['id' => $lessonBSameDay->id]);
        $this->assertDatabaseHas('lessons', ['id' => $lessonBFuture->id]);
    }

    #[Test]
    public function deletion_preview_lists_sibling_warnings_same_day(): void
    {
        $date = '2026-05-20 09:00:00';

        $lessonA = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $date,
            'end_time' => '2026-05-20 10:00:00',
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonA);

        $lessonB = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => '2026-05-20 11:00:00',
            'end_time' => '2026-05-20 12:00:00',
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonB);

        $response = $this->getJson("/api/club/lessons/{$lessonA->id}/deletion-preview?" . http_build_query([
            'cancel_scope' => 'single',
            'action' => 'delete',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.target_lesson.id', $lessonA->id)
            ->assertJsonCount(1, 'data.affected_lessons')
            ->assertJsonCount(1, 'data.sibling_warnings');

        $this->assertEquals($lessonB->id, $response->json('data.sibling_warnings.0.id'));
    }

    #[Test]
    public function selective_delete_with_lesson_ids_only_removes_checked(): void
    {
        $slotStart = Carbon::parse('next monday')->setTime(9, 0, 0);
        $slotEnd = $slotStart->copy()->addHour();

        $lesson1 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy(),
            'end_time' => $slotEnd->copy(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lesson1);

        $lesson2 = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => $slotStart->copy()->addWeek(),
            'end_time' => $slotEnd->copy()->addWeek(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lesson2);

        $response = $this->deleteJson("/api/club/lessons/{$lesson1->id}", [
            'cancel_scope' => 'all_future',
            'action' => 'delete',
            'lesson_ids' => [$lesson1->id],
        ]);

        $response->assertStatus(200)->assertJsonPath('data.processed_count', 1);
        $this->assertSoftDeleted('lessons', ['id' => $lesson1->id]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson2->id]);
    }

    #[Test]
    public function recurring_interval_update_does_not_delete_sibling_student_future_lessons(): void
    {
        $slotStart = Carbon::parse('next wednesday')->setTime(10, 0, 0);
        $slotEnd = $slotStart->copy()->addHour();

        $lessonA = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->copy(),
            'end_time' => $slotEnd->copy(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
        $this->attachLesson($lessonA);

        $lessonAFuture = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->copy()->addWeek(),
            'end_time' => $slotEnd->copy()->addWeek(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
        $this->attachLesson($lessonAFuture);

        $lessonBFuture = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->copy()->addWeek()->addHours(2),
            'end_time' => $slotEnd->copy()->addWeek()->addHours(2),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
        $this->attachLesson($lessonBFuture);

        SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'day_of_week' => $slotStart->dayOfWeek,
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotEnd->format('H:i:s'),
            'recurring_interval' => 1,
            'start_date' => $slotStart->copy()->startOfDay(),
            'end_date' => $slotStart->copy()->addWeeks(26),
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/lessons/{$lessonA->id}", [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'update_scope' => 'all_future',
            'recurring_interval' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $lessonBFuture->id]);
    }

    #[Test]
    public function lesson_ids_outside_allowed_scope_returns_422(): void
    {
        $slotStart = Carbon::parse('next monday')->setTime(9, 0, 0);
        $slotEnd = $slotStart->copy()->addHour();

        $lessonA = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->copy(),
            'end_time' => $slotEnd->copy(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonA);

        $lessonB = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentB->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $slotStart->copy()->addWeek(),
            'end_time' => $slotEnd->copy()->addWeek(),
            'status' => 'confirmed',
        ]);
        $this->attachLesson($lessonB);

        $response = $this->deleteJson("/api/club/lessons/{$lessonA->id}", [
            'cancel_scope' => 'single',
            'action' => 'delete',
            'lesson_ids' => [$lessonB->id],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('lessons', ['id' => $lessonB->id]);
    }

    #[Test]
    public function subscriptions_index_family_shared_filter(): void
    {
        $soloSubscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => 'SOLO-001',
        ]);
        $soloInstance = SubscriptionInstance::create([
            'subscription_id' => $soloSubscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);
        $soloInstance->students()->attach($this->studentA->id);

        $response = $this->getJson('/api/club/subscriptions?family_shared=1&scope=active');

        $response->assertStatus(200)->assertJson(['success' => true]);

        $numbers = collect($response->json('data'))->pluck('subscription_number');
        $this->assertTrue($numbers->contains('FAM-DEL-001'));
        $this->assertFalse($numbers->contains('SOLO-001'));
    }
}
