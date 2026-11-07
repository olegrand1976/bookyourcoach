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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * « Planifier l'avenir » (cas réel : série arrêtée à 6 mois, rattachée à un carnet terminé,
 * alors que l'élève a un carnet actif — plus aucun cours généré).
 */
class ClubStudentFuturePlanningTest extends TestCase
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
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00')); // mercredi

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        DB::table('club_students')->insert([
            'club_id' => $this->club->id, 'student_id' => $this->student->id, 'is_active' => true,
            'joined_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_prolonge_la_serie_sur_le_carnet_actif_sans_creer_de_cours_passes(): void
    {
        $old = $this->makeInstance('completed', '2026-03-13', '2026-07-13', 11);
        $active = $this->makeInstance('active', '2026-09-03', '2026-12-31', 11);

        // Dernier cours de la série le 10/10 (samedi), série arrêtée ce jour-là.
        $last = Lesson::createQuietly([
            'club_id' => $this->club->id, 'teacher_id' => $this->teacher->id, 'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id, 'location_id' => $this->location->id,
            'start_time' => '2026-10-10 10:00:00', 'end_time' => '2026-10-10 10:20:00',
            'status' => 'confirmed', 'price' => 18,
        ]);
        DB::table('subscription_lessons')->insert(['subscription_instance_id' => $active->id, 'lesson_id' => $last->id, 'created_at' => now(), 'updated_at' => now()]);

        $slot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $old->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY,
            'start_time' => '10:00:00',
            'end_time' => '10:20:00',
            'start_date' => '2026-04-11',
            'end_date' => '2026-10-10',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/club/students/{$this->student->id}/plan-future")->assertOk();

        $this->assertSame(1, $response->json('data.prolonged'));
        $this->assertSame('2026-12-31', $slot->fresh()->end_date->format('Y-m-d'));

        $dates = Lesson::where('student_id', $this->student->id)->where('id', '!=', $last->id)
            ->orderBy('start_time')->pluck('start_time')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->all();
        $this->assertNotEmpty($dates);
        $this->assertSame('2026-11-07', $dates[0], 'aucune semaine passée recréée (17/10 → 31/10)');
        // Rattachés au carnet actif, dans la limite de ses places (11 - 1 déjà pris)
        $this->assertLessThanOrEqual(10, count($dates));
        $this->assertSame(count($dates), DB::table('subscription_lessons')->where('subscription_instance_id', $active->id)->count() - 1);
    }

    public function test_sans_abonnement_actif_rien_n_est_planifie(): void
    {
        $old = $this->makeInstance('completed', '2026-03-13', '2026-07-13', 11);
        SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $old->id, 'teacher_id' => $this->teacher->id, 'student_id' => $this->student->id,
            'day_of_week' => Carbon::SATURDAY, 'start_time' => '10:00:00', 'end_time' => '10:20:00',
            'start_date' => '2026-04-11', 'end_date' => '2026-10-10', 'status' => 'active',
        ]);

        $this->postJson("/api/club/students/{$this->student->id}/plan-future")
            ->assertOk()
            ->assertJsonPath('data.prolonged', 0)
            ->assertJsonPath('data.lessons_generated', 0);
    }

    public function test_eleve_d_un_autre_club_refuse(): void
    {
        $foreign = Student::factory()->create();
        $this->postJson("/api/club/students/{$foreign->id}/plan-future")->assertNotFound();
    }

    private function makeInstance(string $status, string $start, string $end, int $total): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id, 'model_number' => 'TPL-'.uniqid(), 'total_lessons' => $total,
            'free_lessons' => 0, 'validity_months' => 4, 'price' => 180, 'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);
        $subscription = Subscription::create([
            'club_id' => $this->club->id, 'subscription_template_id' => $template->id, 'subscription_number' => 'SUB-'.uniqid(),
        ]);
        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id, 'status' => $status, 'lessons_used' => 0, 'manual_lessons_used' => 0,
            'started_at' => Carbon::parse($start), 'expires_at' => Carbon::parse($end),
        ]);
        $instance->students()->attach($this->student->id);

        return $instance;
    }
}
