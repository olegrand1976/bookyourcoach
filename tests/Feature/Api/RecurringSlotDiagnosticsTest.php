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

class RecurringSlotDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionInstance $subscriptionInstance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

        $user = $this->actingAsClub();
        $this->club = Club::findOrFail($user->club_id);
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'name' => 'Abo Diagnostic',
            'model_number' => 'DIAG001',
            'total_lessons' => 20,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-DIAG-'.uniqid(),
        ]);

        $this->subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'lessons_remaining' => 20,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(6),
        ]);
        $this->subscriptionInstance->students()->attach($this->student->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_diagnostics_flags_active_slot_without_future_lessons(): void
    {
        $slot = $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00')); // lundi

        $response = $this->getJson('/api/club/recurring-slots/diagnostics?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);
        $row = collect($items)->firstWhere('id', $slot->id);
        $this->assertNotNull($row);
        $this->assertSame(0, $row['future_lessons_count']);
        $codes = collect($row['issues'])->pluck('code')->all();
        $this->assertContains('no_future_lessons', $codes);
        $this->assertTrue($row['can_regenerate']);
    }

    public function test_diagnostics_flags_teacher_mismatch(): void
    {
        $base = Carbon::parse('2026-03-02 10:00:00');
        $slot = $this->createActiveSlot($base);
        $otherTeacher = Teacher::factory()->create(['club_id' => $this->club->id]);

        $lesson = $this->createFutureLesson($base->copy()->addWeek(), $otherTeacher->id);
        $lesson->subscriptionInstances()->attach($this->subscriptionInstance->id);

        $response = $this->getJson('/api/club/recurring-slots/diagnostics?issue=teacher_mismatch');

        $response->assertStatus(200);
        $row = collect($response->json('data.items'))->firstWhere('id', $slot->id);
        $this->assertNotNull($row);
        $codes = collect($row['issues'])->pluck('code')->all();
        $this->assertContains('teacher_mismatch', $codes);
    }

    public function test_regenerate_creates_future_lessons_idempotent(): void
    {
        $base = Carbon::parse('2026-03-02 10:00:00');
        $slot = $this->createActiveSlot($base);

        // Cours de référence passé / courant pour ancrer la série
        $anchor = $this->createFutureLesson($base, $this->teacher->id);
        $anchor->update(['start_time' => $base->copy()->subWeek()->format('Y-m-d H:i:s')]);
        $anchor->subscriptionInstances()->attach($this->subscriptionInstance->id);

        $first = $this->postJson("/api/club/recurring-slots/{$slot->id}/regenerate-future-lessons", [
            'from_date' => '2026-03-02',
        ]);

        $first->assertStatus(200)->assertJsonPath('success', true);
        $generated = (int) $first->json('data.generated');
        $this->assertGreaterThan(0, $generated);

        $second = $this->postJson("/api/club/recurring-slots/{$slot->id}/regenerate-future-lessons", [
            'from_date' => '2026-03-02',
        ]);
        $second->assertStatus(200);
        $this->assertSame(0, (int) $second->json('data.generated'));
    }

    public function test_other_club_cannot_see_diagnostics(): void
    {
        $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00'));

        $otherUser = $this->actingAsClub(); // nouveau club
        $this->assertNotEquals($this->club->id, $otherUser->club_id);

        $response = $this->getJson('/api/club/recurring-slots/diagnostics');
        $response->assertStatus(200);
        $this->assertSame([], $response->json('data.items'));
    }

    public function test_diagnostics_flags_schedule_drift(): void
    {
        $base = Carbon::parse('2026-03-02 10:00:00');
        $slot = $this->createActiveSlot($base);

        $aligned = $this->createFutureLesson($base->copy()->addWeek(), $this->teacher->id);
        $aligned->subscriptionInstances()->attach($this->subscriptionInstance->id);

        // Cours lié à l'abo mais à une autre heure → drift
        $drifted = $this->createFutureLesson($base->copy()->addWeeks(2)->setTime(15, 0), $this->teacher->id);
        $drifted->subscriptionInstances()->attach($this->subscriptionInstance->id);

        $response = $this->getJson('/api/club/recurring-slots/diagnostics?issue=schedule_drift');
        $response->assertStatus(200);
        $row = collect($response->json('data.items'))->firstWhere('id', $slot->id);
        $this->assertNotNull($row);
        $this->assertContains('schedule_drift', collect($row['issues'])->pluck('code')->all());
    }

    public function test_diagnostics_does_not_flag_drift_for_sibling_series_lessons(): void
    {
        $base = Carbon::parse('2026-03-02 10:00:00'); // lundi 10h
        $slotA = $this->createActiveSlot($base);

        $slotB = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::parse('2026-03-04')->dayOfWeek, // mercredi
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'recurring_interval' => 1,
            'start_date' => $base->copy()->subMonth()->startOfDay(),
            'end_date' => $base->copy()->addMonths(4)->endOfDay(),
            'status' => 'active',
        ]);

        $lessonA = $this->createFutureLesson($base->copy()->addWeek(), $this->teacher->id);
        $lessonA->subscriptionInstances()->attach($this->subscriptionInstance->id);

        $lessonB = $this->createFutureLesson(Carbon::parse('2026-03-11 14:00:00'), $this->teacher->id); // mercredi
        $lessonB->subscriptionInstances()->attach($this->subscriptionInstance->id);

        $response = $this->getJson('/api/club/recurring-slots/diagnostics?status=active');
        $response->assertStatus(200);

        $rowA = collect($response->json('data.items'))->firstWhere('id', $slotA->id);
        $this->assertNotNull($rowA);
        $this->assertNotContains('schedule_drift', collect($rowA['issues'])->pluck('code')->all());

        $rowB = collect($response->json('data.items'))->firstWhere('id', $slotB->id);
        $this->assertNotNull($rowB);
        $this->assertNotContains('schedule_drift', collect($rowB['issues'])->pluck('code')->all());
    }

    public function test_cancelled_slot_cannot_regenerate(): void
    {
        $slot = $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00'));
        $slot->update(['status' => 'cancelled']);

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/regenerate-future-lessons");
        $response->assertStatus(422);
    }

    /** Régression : search ne doit plus requêter subscription_templates.name (colonne absente → 500). */
    public function test_index_search_does_not_query_missing_template_name_column(): void
    {
        $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00'));

        $response = $this->getJson('/api/club/recurring-slots?search=ez');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_index_search_matches_template_model_number(): void
    {
        $slot = $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00'));

        $response = $this->getJson('/api/club/recurring-slots?search=DIAG001');

        $response->assertOk()
            ->assertJsonPath('success', true);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($slot->id, $ids);
    }

    public function test_index_search_matches_student_first_name_without_user(): void
    {
        $this->student->update([
            'user_id' => null,
            'first_name' => 'Ezra',
            'last_name' => 'SansCompte',
        ]);
        $slot = $this->createActiveSlot(Carbon::parse('2026-03-02 10:00:00'));

        $response = $this->getJson('/api/club/recurring-slots?search=ez');

        $response->assertOk()
            ->assertJsonPath('success', true);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($slot->id, $ids);
    }

    private function createActiveSlot(Carbon $start): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->subscriptionInstance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => $start->dayOfWeek,
            'start_time' => $start->format('H:i:s'),
            'end_time' => $start->copy()->addHour()->format('H:i:s'),
            'recurring_interval' => 1,
            'start_date' => $start->copy()->subMonth()->startOfDay(),
            'end_date' => $start->copy()->addMonths(4)->endOfDay(),
            'status' => 'active',
        ]);
    }

    private function createFutureLesson(Carbon $start, int $teacherId): Lesson
    {
        return Lesson::factory()->create([
            'teacher_id' => $teacherId,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'club_id' => $this->club->id,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
        ]);
    }
}
