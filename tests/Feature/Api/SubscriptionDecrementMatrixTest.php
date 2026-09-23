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
use App\Models\User;
use App\Services\CancellationCertificateReviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Matrice du décompte d'abonnement : un cas par situation du cycle de vie d'un cours.
 *
 * Les tests unitaires de SubscriptionInstance couvrent les méthodes isolément ; ici on
 * vérifie le résultat **de bout en bout**, c'est-à-dire en passant par LessonObserver,
 * la commande horaire et les services métier — là où les règles se combinent.
 *
 * Règle de référence (docs/SUBSCRIPTION_REMAINING_LESSONS.md) :
 *   lessons_used = manual_lessons_used + cours passés consommés + annulations tardives comptées
 */
class SubscriptionDecrementMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $clubUser;

    private $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionTemplate $template;

    private SubscriptionInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubUser = $this->actingAsClub();
        $this->club = Club::find($this->clubUser->club_id);

        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);

        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $this->template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MATRIX-'.uniqid(),
            'total_lessons' => 10,
            'free_lessons' => 0,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $this->template->courseTypes()->attach($this->courseType->id);

        $this->instance = $this->makeInstance();
    }

    private function makeInstance(int $manualUsed = 0, ?Student $student = null): SubscriptionInstance
    {
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $this->template->id,
            'subscription_number' => 'SUB-MATRIX-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => $manualUsed,
            'manual_lessons_used' => $manualUsed,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(11),
        ]);
        $instance->students()->attach(($student ?? $this->student)->id);

        return $instance;
    }

    private function makeLesson(Carbon $start, array $attributes = [], ?Student $student = null): Lesson
    {
        return Lesson::create(array_merge([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => ($student ?? $this->student)->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ], $attributes));
    }

    private function used(?SubscriptionInstance $instance = null): int
    {
        return (int) ($instance ?? $this->instance)->fresh()->lessons_used;
    }

    private function attachedCount(?SubscriptionInstance $instance = null): int
    {
        return DB::table('subscription_lessons')
            ->where('subscription_instance_id', ($instance ?? $this->instance)->id)
            ->count();
    }

    // ---------------------------------------------------------------- consommation

    /** Un cours passé consomme immédiatement. */
    public function test_cours_passe_est_consomme(): void
    {
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(3)->setTime(10, 0)));

        $this->assertSame(1, $this->used());
    }

    /** Un cours futur est rattaché (la place est réservée) mais ne consomme pas encore. */
    public function test_cours_futur_est_rattache_sans_consommer(): void
    {
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->addDays(3)->setTime(10, 0)));

        $this->assertSame(0, $this->used());
        $this->assertSame(1, $this->attachedCount());
    }

    /** Le passage du temps consomme le cours : c'est le rôle de la commande horaire. */
    public function test_cours_futur_devenu_passe_est_consomme_par_la_commande_horaire(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->addHours(2));
        $this->instance->consumeLesson($lesson);
        $this->assertSame(0, $this->used());

        // Le cours a eu lieu.
        $lesson->forceFill(['start_time' => Carbon::now()->subHour(), 'end_time' => Carbon::now()])->saveQuietly();
        Artisan::call('subscriptions:consume-past-lessons');

        $this->assertSame(1, $this->used());
    }

    /** manual_lessons_used (cours pré-digitalisation) s'ajoute toujours au décompte. */
    public function test_manual_lessons_used_s_ajoute_aux_cours_consommes(): void
    {
        $instance = $this->makeInstance(manualUsed: 5);
        $instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(2)->setTime(9, 0)));

        $this->assertSame(6, $this->used($instance));
    }

    /** Séance libre : le rattachement est refusé au point de passage, quel que soit l'appelant. */
    public function test_seance_libre_n_est_jamais_rattachee_meme_en_appel_direct(): void
    {
        $this->instance->consumeLesson(
            $this->makeLesson(Carbon::now()->subDay()->setTime(10, 0), ['deduct_from_subscription' => false])
        );

        $this->assertSame(0, $this->attachedCount());
        $this->assertSame(0, $this->used());
    }

    /** Et de bout en bout, à la création par l'API. */
    public function test_seance_libre_creee_par_l_api_n_est_pas_rattachee(): void
    {
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'deduct_from_subscription' => false,
            'recurring_interval' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertSame(0, $this->attachedCount());
        $this->assertSame(0, $this->used());
    }

    // ---------------------------------------------------------------- annulations

    /** Annulation dans les délais : le cours est détaché, le crédit rendu. */
    public function test_annulation_dans_les_delais_rend_le_credit(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $this->assertSame(1, $this->used());

        $lesson->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => false]);

        $this->assertSame(0, $this->used());
        $this->assertSame(0, $this->attachedCount());
    }

    /** Annulation hors délai : le cours reste rattaché et la séance reste due. */
    public function test_annulation_hors_delai_reste_due(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);

        $lesson->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => true]);

        $this->assertSame(1, $this->used());
        $this->assertSame(1, $this->attachedCount());
    }

    /** Cas le plus fréquent : annulation tardive d'un cours encore à venir → due immédiatement. */
    public function test_annulation_hors_delai_d_un_cours_futur_est_due_immediatement(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->addDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $this->assertSame(0, $this->used());

        $lesson->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => true]);

        $this->assertSame(1, $this->used());
    }

    /** La séance due le reste après le recalcul complet (commande horaire). */
    public function test_annulation_hors_delai_survit_au_recalcul(): void
    {
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(5)->setTime(9, 0)));
        $late = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($late);
        $late->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => true]);

        Artisan::call('subscriptions:consume-past-lessons');

        $this->assertSame(2, $this->used());
    }

    /** Certificat médical accepté après coup : le drapeau retombe, le crédit est rendu. */
    public function test_certificat_accepte_rend_le_credit(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->addDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $lesson->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'medical',
            'cancellation_count_in_subscription' => true,
            'cancellation_certificate_status' => 'pending',
        ]);
        $this->assertSame(1, $this->used());

        app(CancellationCertificateReviewService::class)->accept($lesson->fresh(), $this->clubUser);

        $this->assertSame(0, $this->used());
    }

    /** Certificat refusé : la séance reste due. */
    public function test_certificat_refuse_laisse_la_seance_due(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->addDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $lesson->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'medical',
            'cancellation_count_in_subscription' => true,
            'cancellation_certificate_status' => 'pending',
        ]);

        app(CancellationCertificateReviewService::class)->reject($lesson->fresh(), $this->clubUser, 'illisible');

        $this->assertSame(1, $this->used());
    }

    /** Réactivation d'un cours annulé : rattaché à nouveau, sans double comptage. */
    public function test_reactivation_ne_double_pas_le_decompte(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $lesson->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => false]);
        $this->assertSame(0, $this->used());

        $lesson->fresh()->update(['status' => 'confirmed']);

        $this->assertSame(1, $this->used());
        $this->assertSame(1, $this->attachedCount());
    }

    // ---------------------------------------------------------------- suppressions

    /** Un cours soft-supprimé ne compte jamais. */
    public function test_cours_soft_supprime_ne_compte_pas(): void
    {
        $lesson = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $this->assertSame(1, $this->used());

        $lesson->delete();

        $this->assertSame(0, $this->used());
    }

    // ---------------------------------------------------------------- plafond

    /** Le plafond du carnet est opposé à la création : on ne peut pas dépasser le total. */
    public function test_plafond_du_carnet_refuse_le_cours_excedentaire(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(30 - $i)->setTime(10, 0)));
        }
        $this->assertSame(10, $this->used());

        $this->expectException(\Exception::class);
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDay()->setTime(11, 0)));
    }

    /** Forçage club : le plafond peut être outrepassé explicitement. */
    public function test_forcage_club_outrepasse_le_plafond(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(30 - $i)->setTime(10, 0)));
        }

        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDay()->setTime(11, 0)), force: true);

        $this->assertSame(11, $this->used());
    }

    /** Carnet plein → completed ; libération d'une séance → réouverture. */
    public function test_statut_bascule_completed_puis_revient_actif(): void
    {
        $lessons = [];
        for ($i = 1; $i <= 10; $i++) {
            $lessons[] = $lesson = $this->makeLesson(Carbon::now()->subDays(30 - $i)->setTime(10, 0));
            $this->instance->consumeLesson($lesson);
        }
        $this->assertSame('completed', $this->instance->fresh()->status);

        $lessons[0]->update(['status' => 'cancelled', 'cancellation_count_in_subscription' => false]);

        $this->assertSame('active', $this->instance->fresh()->status);
        $this->assertSame(9, $this->used());
    }

    // ---------------------------------------------------------------- famille / collectif

    /** Abonnement familial : seuls les cours d'un bénéficiaire sont décomptés. */
    public function test_abonnement_familial_ne_compte_que_les_beneficiaires(): void
    {
        $sibling = Student::factory()->create(['club_id' => $this->club->id]);
        $outsider = Student::factory()->create(['club_id' => $this->club->id]);
        $this->instance->students()->attach($sibling->id);

        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(3)->setTime(10, 0)));
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDays(2)->setTime(11, 0), [], $sibling));
        $this->assertSame(2, $this->used());

        // Un cours d'un élève hors abonnement ne peut pas y être rattaché.
        $this->expectException(\Exception::class);
        $this->instance->consumeLesson($this->makeLesson(Carbon::now()->subDay()->setTime(12, 0), [], $outsider));
    }

    /** Contrainte structurelle : un cours ne peut débiter qu'un seul abonnement. */
    public function test_un_cours_ne_debite_qu_un_seul_abonnement(): void
    {
        $other = $this->makeInstance();
        $lesson = $this->makeLesson(Carbon::now()->subDays(2)->setTime(10, 0));
        $this->instance->consumeLesson($lesson);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $other->lessons()->attach($lesson->id);
    }

    // ---------------------------------------------------------------- fermeture club

    /** Jour de fermeture du club : les cours sont détachés et les crédits rendus. */
    public function test_jour_de_fermeture_rend_les_credits(): void
    {
        $day = Carbon::now()->addDays(3)->startOfDay();
        $lesson = $this->makeLesson($day->copy()->setTime(10, 0));
        $this->instance->consumeLesson($lesson);
        $this->assertSame(1, $this->attachedCount());

        app(\App\Services\ClubClosureDayService::class)->closeDay($this->club, $day->format('Y-m-d'));

        $this->assertSame(0, $this->attachedCount());
        $this->assertSame(0, $this->used());
        $this->assertTrue(ClubClosureDay::query()->where('club_id', $this->club->id)->exists());
    }
}
