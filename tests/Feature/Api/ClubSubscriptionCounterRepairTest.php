<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\CourseType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClubSubscriptionCounterRepairTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
    }

    public function test_preview_lists_closure_day_lessons_without_writing_anything(): void
    {
        $instance = $this->makeInstance($this->club);
        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => '2026-07-04']);
        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => '2026-07-11']);
        $this->attach($instance, $this->makeLesson('2026-07-04'));
        $this->attach($instance, $this->makeLesson('2026-07-11'));
        $this->attach($instance, $this->makeLesson('2026-06-27'));
        DB::table('subscription_instances')->where('id', $instance->id)->update(['lessons_used' => 3]);

        $response = $this->getJson('/api/club/subscriptions/counter-repair/preview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.counter_changes', 1)
            ->assertJsonPath('data.items.0.instance_id', $instance->id)
            ->assertJsonPath('data.items.0.current_lessons_used', 3)
            ->assertJsonPath('data.items.0.recalculated_lessons_used', 1)
            ->assertJsonCount(2, 'data.items.0.closure_day_lessons');
        $this->assertSame(3, (int) $instance->fresh()->lessons_used);
    }

    public function test_apply_recalculates_and_keeps_future_excess_unless_asked(): void
    {
        $instance = $this->makeInstance($this->club);
        foreach (['2027-01-02', '2027-01-09', '2027-01-16', '2027-01-23'] as $ymd) {
            $this->attach($instance, $this->makeLesson($ymd));
        }

        $this->getJson('/api/club/subscriptions/counter-repair/preview')
            ->assertOk()
            ->assertJsonCount(2, 'data.items.0.future_excess_lessons');

        $this->postJson('/api/club/subscriptions/counter-repair/apply', [
            'instance_ids' => [$instance->id],
        ])->assertOk()->assertJsonPath('data.results.0.detached_lesson_ids', []);
        $this->assertSame(4, $instance->lessons()->count());

        $response = $this->postJson('/api/club/subscriptions/counter-repair/apply', [
            'instance_ids' => [$instance->id],
            'detach_future_excess_for' => [$instance->id],
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.results.0.detached_lesson_ids'));
        $this->assertSame(
            ['2027-01-02', '2027-01-09'],
            $instance->lessons()->orderBy('start_time')->get()->map(fn ($l) => Carbon::parse($l->start_time)->toDateString())->all()
        );
    }

    public function test_future_excess_skips_closure_day_lessons_that_are_not_counted(): void
    {
        $instance = $this->makeInstance($this->club);
        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => '2027-01-30']);
        foreach (['2027-01-02', '2027-01-09', '2027-01-16', '2027-01-30'] as $ymd) {
            $this->attach($instance, $this->makeLesson($ymd));
        }

        // 3 cours décomptés pour 2 places : un seul en excédent, le 16/01 (le 30/01 est un congé).
        $this->getJson('/api/club/subscriptions/counter-repair/preview')
            ->assertOk()
            ->assertJsonCount(1, 'data.items.0.future_excess_lessons')
            ->assertJsonPath('data.items.0.future_excess_lessons.0.id', Lesson::whereDate('start_time', '2027-01-16')->value('id'));
    }

    public function test_apply_ignores_instances_of_another_club(): void
    {
        $otherInstance = $this->makeInstance(Club::factory()->create());
        DB::table('subscription_instances')->where('id', $otherInstance->id)->update(['lessons_used' => 7]);

        $this->postJson('/api/club/subscriptions/counter-repair/apply', [
            'instance_ids' => [$otherInstance->id],
        ])->assertOk()->assertJsonPath('data.results', []);

        $this->assertSame(7, (int) $otherInstance->fresh()->lessons_used);
    }

    private function makeInstance(Club $club): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'TPL-'.uniqid(),
            'total_lessons' => 2,
            'free_lessons' => 0,
            'validity_months' => 12,
            'price' => 100.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => Carbon::parse('2026-06-01'),
            'expires_at' => Carbon::parse('2027-06-01'),
        ]);
        $instance->students()->attach($this->student->id);

        return $instance;
    }

    private function makeLesson(string $ymd): Lesson
    {
        // Sans observateur : son recalcul part en afterResponse(), donc à la fin de la
        // première requête du test, et fausserait le constat « la simulation n'écrit rien ».
        return Lesson::createQuietly([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse("{$ymd} 10:00:00"),
            'end_time' => Carbon::parse("{$ymd} 11:00:00"),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
    }

    private function attach(SubscriptionInstance $instance, Lesson $lesson): void
    {
        DB::table('subscription_lessons')->insert([
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
