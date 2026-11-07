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
use App\Models\SubscriptionRebalance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Remise en cohérence (cas réel : carnet 1 en dépassement, cours rattachés hors période,
 * cours de jours de fermeture encore rattachés, compteur manuel issu d'une correction).
 */
class ClubSubscriptionRebalanceTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionInstance $a;

    private SubscriptionInstance $b;

    /** @var array<string, Lesson> */
    private array $lessons = [];

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->a = $this->makeInstance('2026-01-01', '2026-02-28');
        $this->b = $this->makeInstance('2026-03-01', '2026-04-30');
        DB::table('subscription_instances')->where('id', $this->b->id)->update([
            'manual_lessons_used' => 1,
            'lessons_used_reset_at' => '2026-03-20 10:00:00',
        ]);
        ClubClosureDay::create(['club_id' => $this->club->id, 'closed_on' => '2026-03-21']);

        // Carnet A en dépassement (3 cours pour 2 places), un cours de mars non rattaché,
        // un cours de mars sur B, un cours de jour de fermeture encore rattaché à B.
        $this->lessons['01-10'] = $this->lesson('2026-01-10', $this->a);
        $this->lessons['01-17'] = $this->lesson('2026-01-17', $this->a);
        $this->lessons['01-24'] = $this->lesson('2026-01-24', $this->a);
        $this->lessons['03-07'] = $this->lesson('2026-03-07', null);
        $this->lessons['03-14'] = $this->lesson('2026-03-14', $this->b);
        $this->lessons['03-21'] = $this->lesson('2026-03-21', $this->b);
    }

    public function test_simulation_reaffecte_dans_l_ordre_sans_rien_ecrire(): void
    {
        $response = $this->getJson("/api/club/subscriptions/rebalance/students/{$this->student->id}")->assertOk();

        $plan = $response->json('data.plan');
        $this->assertTrue($plan['has_changes']);
        $this->assertSame([$this->lessons['03-21']->id], array_column($plan['closure_detach'], 'lesson_id'));
        $this->assertSame([$this->lessons['03-14']->id], array_column($plan['uncovered'], 'lesson_id'));
        $moves = collect($plan['moves'])->keyBy('lesson_id');
        $this->assertSame($this->b->id, $moves[$this->lessons['01-24']->id]['to']);
        $this->assertSame($this->b->id, $moves[$this->lessons['03-07']->id]['to']);
        $this->assertSame(1, $plan['manual_changes']);

        // Rien n'a bougé
        $this->assertSame($this->a->id, $this->linkOf('01-24'));
        $this->assertNull($this->linkOf('03-07'));
    }

    public function test_application_puis_annulation_restaure_l_etat_exact(): void
    {
        $beforeLinks = array_map(fn ($k) => $this->linkOf($k), array_keys($this->lessons));
        $beforeB = DB::table('subscription_instances')->where('id', $this->b->id)->first();

        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply")
            ->assertOk()
            ->assertJsonPath('data.summary.moved', 3)
            ->assertJsonPath('data.summary.closure_detached', 1)
            ->assertJsonPath('data.summary.uncovered', 1);

        $this->assertSame($this->a->id, $this->linkOf('01-10'));
        $this->assertSame($this->a->id, $this->linkOf('01-17'));
        $this->assertSame($this->b->id, $this->linkOf('01-24'));
        $this->assertSame($this->b->id, $this->linkOf('03-07'));
        $this->assertNull($this->linkOf('03-14'));
        $this->assertNull($this->linkOf('03-21'));
        $b = $this->b->fresh();
        $this->assertSame(0, (int) $b->manual_lessons_used);
        $this->assertNull($b->lessons_used_reset_at);
        $this->assertSame(2, (int) $b->lessons_used);
        $this->assertSame(2, (int) $this->a->fresh()->lessons_used);

        // Déjà cohérent : rien à refaire
        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply")
            ->assertOk()->assertJsonPath('data', null);

        $rebalance = SubscriptionRebalance::firstOrFail();
        $this->postJson("/api/club/subscriptions/rebalance/{$rebalance->id}/revert")->assertOk();

        $this->assertSame($beforeLinks, array_map(fn ($k) => $this->linkOf($k), array_keys($this->lessons)));
        $restoredB = DB::table('subscription_instances')->where('id', $this->b->id)->first();
        $this->assertSame((int) $beforeB->manual_lessons_used, (int) $restoredB->manual_lessons_used);
        $this->assertSame($beforeB->lessons_used_reset_at, $restoredB->lessons_used_reset_at);
        $this->assertSame((int) $beforeB->lessons_used, (int) $restoredB->lessons_used);
        $this->assertSame($beforeB->status, $restoredB->status);

        $this->postJson("/api/club/subscriptions/rebalance/{$rebalance->id}/revert")->assertStatus(422);
    }

    public function test_seule_la_derniere_correction_peut_etre_annulee(): void
    {
        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply")->assertOk();
        $first = SubscriptionRebalance::firstOrFail();

        // Nouvel écart, nouvelle correction
        $this->lesson('2026-04-04', $this->a);
        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply")->assertOk();

        $this->postJson("/api/club/subscriptions/rebalance/{$first->id}/revert")->assertStatus(422);
    }

    public function test_controle_du_club_liste_l_eleve_et_respecte_le_cloisonnement(): void
    {
        $this->getJson('/api/club/subscriptions/rebalance/audit')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_ids', [$this->student->id]);

        $foreign = Student::factory()->create();
        $this->getJson("/api/club/subscriptions/rebalance/students/{$foreign->id}")->assertNotFound();
        $this->postJson("/api/club/subscriptions/rebalance/students/{$foreign->id}/apply")->assertNotFound();
    }

    private function linkOf(string $key): ?int
    {
        $id = DB::table('subscription_lessons')->where('lesson_id', $this->lessons[$key]->id)->value('subscription_instance_id');

        return $id !== null ? (int) $id : null;
    }

    private function makeInstance(string $start, string $end): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TPL-'.uniqid(),
            'total_lessons' => 2,
            'free_lessons' => 0,
            'validity_months' => 2,
            'price' => 100.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::parse($start),
            'expires_at' => Carbon::parse($end),
        ]);
        $instance->students()->attach($this->student->id);

        return $instance;
    }

    private function lesson(string $ymd, ?SubscriptionInstance $instance): Lesson
    {
        $lesson = Lesson::createQuietly([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse("{$ymd} 10:00:00"),
            'end_time' => Carbon::parse("{$ymd} 10:20:00"),
            'status' => 'confirmed',
            'price' => 18.00,
        ]);
        if ($instance) {
            DB::table('subscription_lessons')->insert([
                'subscription_instance_id' => $instance->id,
                'lesson_id' => $lesson->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $lesson;
    }

    public function test_fiche_detaille_chaque_cours_avec_carnet_propose_et_carnets_eligibles(): void
    {
        $lessons = collect($this->getJson("/api/club/subscriptions/rebalance/students/{$this->student->id}")
            ->assertOk()->json('data.plan.lessons'))->keyBy('lesson_id');

        $this->assertCount(5, $lessons, 'les cours de fermeture ne font pas partie de la répartition');
        $row = $lessons[$this->lessons['01-24']->id];
        $this->assertSame($this->a->id, $row['current']);
        $this->assertSame($this->b->id, $row['proposed']);
        $this->assertEqualsCanonicalizing([$this->a->id, $this->b->id], $row['eligible']);
        $this->assertTrue($row['past']);
    }

    public function test_application_avec_ajustements_de_la_fiche(): void
    {
        // Le club garde le 24/01 sur le carnet A (dépassement accepté) et fixe 1 cours manuel sur B.
        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply", [
            'assignments' => [['lesson_id' => $this->lessons['01-24']->id, 'instance_id' => $this->a->id]],
            'manual' => [['instance_id' => $this->b->id, 'value' => 1]],
        ])->assertOk()->assertJsonPath('data.summary.customized', true);

        $this->assertSame($this->a->id, $this->linkOf('01-24'));
        $this->assertSame($this->b->id, $this->linkOf('03-07'));
        $this->assertNull($this->linkOf('03-21'), 'jour de fermeture toujours détaché');
        $b = $this->b->fresh();
        $this->assertSame(1, (int) $b->manual_lessons_used);
        $this->assertNull($b->lessons_used_reset_at);

        // Annulable comme une correction standard
        $rebalance = SubscriptionRebalance::latest('id')->firstOrFail();
        $this->postJson("/api/club/subscriptions/rebalance/{$rebalance->id}/revert")->assertOk();
        $this->assertSame($this->a->id, $this->linkOf('01-24'));
        $this->assertNull($this->linkOf('03-07'));
    }

    public function test_ajustement_hors_perimetre_refuse_sans_rien_modifier(): void
    {
        $foreignLesson = Lesson::createQuietly([
            'club_id' => $this->club->id, 'teacher_id' => $this->teacher->id,
            'student_id' => Student::factory()->create(['club_id' => $this->club->id])->id,
            'course_type_id' => $this->courseType->id, 'location_id' => $this->location->id,
            'start_time' => '2026-02-07 10:00:00', 'end_time' => '2026-02-07 10:20:00', 'status' => 'confirmed', 'price' => 18,
        ]);

        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply", [
            'assignments' => [['lesson_id' => $foreignLesson->id, 'instance_id' => $this->a->id]],
        ])->assertStatus(422);

        $this->postJson("/api/club/subscriptions/rebalance/students/{$this->student->id}/apply", [
            'manual' => [['instance_id' => 999999, 'value' => 2]],
        ])->assertStatus(422);

        $this->assertSame($this->a->id, $this->linkOf('01-24'));
        $this->assertSame(0, SubscriptionRebalance::count());
    }
}
