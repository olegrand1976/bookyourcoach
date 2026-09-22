<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deux règles de consommation d'abonnement non couvertes ailleurs.
 *
 * 1. Séance libre et récurrence : ProcessLessonPostCreationJobTest vérifie que le cours
 *    déclencheur n'est pas déduit, mais pas les cours générés par la série.
 * 2. Validité : `status` seul ne suffit pas à écarter un abonnement dont `expires_at`
 *    est dépassé, aucune tâche planifiée ne les bascule en 'expired'.
 */
class SubscriptionConsumptionRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $clubUser;

    protected $club;

    protected $teacher;

    protected $student;

    protected $courseType;

    protected $location;

    protected SubscriptionInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubUser = $this->actingAsClub();
        $this->club = Club::find($this->clubUser->club_id);

        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, [
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'CONSO-'.uniqid(),
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-CONSO-'.uniqid(),
        ]);

        $this->instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::now()->subDays(30),
            'expires_at' => Carbon::now()->addDays(335),
        ]);
        $this->instance->students()->attach($this->student->id);
    }

    private function expireInstance(): void
    {
        DB::table('subscription_instances')->where('id', $this->instance->id)->update([
            'expires_at' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'status' => 'active',
        ]);
    }

    private function attachedLessonCount(): int
    {
        return DB::table('subscription_lessons')
            ->where('subscription_instance_id', $this->instance->id)
            ->count();
    }

    /**
     * Séance libre avec récurrence : ni le cours déclencheur ni la série générée
     * ne doivent être rattachés à l'abonnement.
     */
    public function test_seance_libre_ne_deduit_ni_le_cours_ni_sa_serie(): void
    {
        $startTime = Carbon::now()->subDays(7)->setTime(10, 0);

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => false,
            'recurring_interval' => 1,
        ]);

        $response->assertStatus(201);

        $this->assertFalse(
            DB::table('subscription_lessons')->where('lesson_id', $response->json('data.id'))->exists(),
            'le cours déclencheur ne doit pas être rattaché'
        );
        $this->assertSame(0, $this->attachedLessonCount(), 'la série générée ne doit pas être rattachée non plus');

        $this->instance->refresh();
        $this->assertSame(0, $this->instance->lessons_used);
    }

    /** Contrôle positif : une série normale consomme bien l'abonnement. */
    public function test_serie_deductible_consomme_bien_l_abonnement(): void
    {
        $startTime = Carbon::now()->subDays(7)->setTime(15, 0);

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            'recurring_interval' => 1,
        ]);

        $response->assertStatus(201);

        $this->assertTrue(
            DB::table('subscription_lessons')->where('lesson_id', $response->json('data.id'))->exists()
        );
        $this->assertGreaterThan(1, $this->attachedLessonCount(), 'la série doit être rattachée');
    }

    /** Un abonnement dont la validité est dépassée n'est plus sélectionnable. */
    public function test_abonnement_perime_nest_pas_selectionne(): void
    {
        $this->expireInstance();

        $found = SubscriptionInstance::findActiveSubscriptionForLesson(
            (int) $this->student->id,
            (int) $this->courseType->id,
            (int) $this->club->id
        );

        $this->assertNull($found);
    }

    /** Et la création d'un cours avec déduction est refusée plutôt que de débiter un carnet périmé. */
    public function test_creation_de_cours_sur_abonnement_perime_est_refusee(): void
    {
        $this->expireInstance();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDays(3)->setTime(9, 0)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => true,
            'recurring_interval' => 0,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Encodage rétroactif : un cours qui a eu lieu pendant la validité reste couvert,
     * même si le carnet a expiré depuis. La validité s'évalue à la date du cours.
     */
    public function test_cours_passe_dans_la_validite_reste_couvert_apres_expiration(): void
    {
        DB::table('subscription_instances')->where('id', $this->instance->id)->update([
            'started_at' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'expires_at' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'status' => 'active',
        ]);

        $found = SubscriptionInstance::findActiveSubscriptionForLesson(
            (int) $this->student->id,
            (int) $this->courseType->id,
            (int) $this->club->id,
            Carbon::now()->subMonths(4)
        );

        $this->assertNotNull($found, 'le cours a eu lieu pendant la validité du carnet');
        $this->assertSame($this->instance->id, $found->id);
    }

    /** Un abonnement sans date d'expiration reste sélectionnable (garde-fou anti sur-filtrage). */
    public function test_abonnement_sans_expiration_reste_selectionnable(): void
    {
        DB::table('subscription_instances')->where('id', $this->instance->id)->update([
            'expires_at' => null,
            'status' => 'active',
        ]);

        $found = SubscriptionInstance::findActiveSubscriptionForLesson(
            (int) $this->student->id,
            (int) $this->courseType->id,
            (int) $this->club->id
        );

        $this->assertNotNull($found);
        $this->assertSame($this->instance->id, $found->id);
    }
}
