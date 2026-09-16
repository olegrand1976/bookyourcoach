<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringSlotReleaseScopeTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveSlot(): array
    {
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $this->assertNotNull($club);

        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
        $student = Student::factory()->create(['club_id' => $club->id]);
        $courseType = CourseType::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'REL001',
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-REL-'.uniqid(),
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2027-01-01'),
        ]);
        $subscriptionInstance->students()->attach($student->id);

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

        return compact('club', 'slot', 'student', 'teacher');
    }

    public function test_release_single_skips_date_and_exposes_it_on_planning_index(): void
    {
        ['slot' => $slot] = $this->createActiveSlot();
        $fromDate = '2026-09-14'; // lundi

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/release", [
            'scope' => 'single',
            'from_date' => $fromDate,
            'reason' => 'Retirer ce jour',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scope', 'single');

        $slot->refresh();
        $this->assertEquals('active', $slot->status);
        $this->assertTrue($slot->isOccurrenceSkipped($fromDate));

        $index = $this->getJson('/api/club/recurring-slots?context=planning&status=active');
        $index->assertOk()->assertJsonPath('success', true);

        $payload = collect($index->json('data'))->firstWhere('id', $slot->id);
        $this->assertNotNull($payload);
        $this->assertContains($fromDate, $payload['skipped_dates'] ?? []);

        $releaseSlot = $response->json('data.slot');
        $this->assertIsArray($releaseSlot);
        $this->assertArrayHasKey('skipped_dates', $releaseSlot);
        $this->assertContains($fromDate, $releaseSlot['skipped_dates']);
        $this->assertArrayNotHasKey('notes', $releaseSlot);
    }

    public function test_release_all_future_truncates_series(): void
    {
        ['slot' => $slot] = $this->createActiveSlot();
        $fromDate = '2026-09-14';

        $response = $this->postJson("/api/club/recurring-slots/{$slot->id}/release", [
            'scope' => 'all_future',
            'from_date' => $fromDate,
            'reason' => 'Terminer la série',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scope', 'all_future');

        $slot->refresh();
        $this->assertEquals('active', $slot->status);
        $this->assertEquals('2026-09-13', $slot->end_date->format('Y-m-d'));
    }

    public function test_release_single_requires_from_date(): void
    {
        ['slot' => $slot] = $this->createActiveSlot();

        $this->postJson("/api/club/recurring-slots/{$slot->id}/release", [
            'scope' => 'single',
            'reason' => 'Sans date',
        ])->assertStatus(422);
    }
}
