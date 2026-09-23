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
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Services\SubscriptionProjectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Date de fin théorique d'un abonnement : quand le dernier crédit sera-t-il consommé ?
 *
 * La projection consomme les cours planifiés, puis prolonge la cadence des séries récurrentes.
 * Elle saute ce qui ne consomme rien : congés du club et annulations qui libèrent la place.
 */
class SubscriptionProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private $club;

    private Teacher $teacher;

    private Student $student;

    private CourseType $courseType;

    private Location $location;

    private SubscriptionTemplate $template;

    private SubscriptionInstance $instance;

    private SubscriptionProjectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);
        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $this->template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'PROJ-'.uniqid(),
            'total_lessons' => 4,
            'free_lessons' => 0,
            'validity_months' => 12,
            'price' => 100.00,
            'is_active' => true,
        ]);
        $this->template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $this->template->id,
            'subscription_number' => 'SUB-PROJ-'.uniqid(),
        ]);

        $this->instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(11),
        ]);
        $this->instance->students()->attach($this->student->id);

        $this->service = app(SubscriptionProjectionService::class);
    }

    /** Lundi de la semaine prochaine, 10h — ancre stable des séries hebdomadaires. */
    private function anchor(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
    }

    private function planLesson(Carbon $start, array $attributes = []): Lesson
    {
        $lesson = Lesson::create(array_merge([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'price' => 25.00,
        ], $attributes));

        $this->instance->lessons()->attach($lesson->id);

        return $lesson;
    }

    private function makeSeries(Carbon $anchor, int $interval = 1): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $this->instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'day_of_week' => $anchor->dayOfWeek,
            'start_time' => $anchor->format('H:i:s'),
            'end_time' => $anchor->copy()->addHour()->format('H:i:s'),
            'recurring_interval' => $interval,
            'start_date' => $anchor->copy()->startOfDay(),
            'end_date' => $anchor->copy()->addWeeks(26),
            'status' => 'active',
        ]);
    }

    private function project(): array
    {
        return $this->service->project($this->instance->fresh());
    }

    /** Carnet épuisé : rien à projeter. */
    public function test_carnet_epuise_ne_projette_rien(): void
    {
        $this->instance->update(['lessons_used' => 4, 'manual_lessons_used' => 4]);

        $result = $this->project();

        $this->assertSame('exhausted', $result['status']);
        $this->assertNull($result['projected_end_date']);
        $this->assertSame(0, $result['remaining_consumed']);
    }

    /** Assez de cours planifiés : la date est celle du 4e cours. */
    public function test_date_de_fin_sur_les_cours_planifies(): void
    {
        $anchor = $this->anchor();
        for ($i = 0; $i < 4; $i++) {
            $this->planLesson($anchor->copy()->addWeeks($i));
        }

        $result = $this->project();

        $this->assertSame('planned', $result['status']);
        $this->assertSame($anchor->copy()->addWeeks(3)->toDateString(), $result['projected_end_date']);
        $this->assertSame(4, $result['planned_consuming_lessons']);
        $this->assertFalse($result['expires_before_exhaustion']);
    }

    /** Un jour de congé club ne consomme pas : la fin est repoussée d'une occurrence. */
    public function test_conge_club_repousse_la_date_de_fin(): void
    {
        $anchor = $this->anchor();
        for ($i = 0; $i < 5; $i++) {
            $this->planLesson($anchor->copy()->addWeeks($i));
        }

        ClubClosureDay::create([
            'club_id' => $this->club->id,
            'closed_on' => $anchor->copy()->addWeeks(1)->toDateString(),
        ]);

        $result = $this->project();

        // Le 2e cours tombe un congé : la 4e séance consommée est celle de la 5e semaine.
        $this->assertSame($anchor->copy()->addWeeks(4)->toDateString(), $result['projected_end_date']);
        $this->assertSame(1, $result['skipped_closure_days']);
        $this->assertSame(4, $result['planned_consuming_lessons']);
    }

    /** Une annulation qui libère la place ne consomme pas non plus. */
    public function test_annulation_liberee_repousse_la_date_de_fin(): void
    {
        $anchor = $this->anchor();
        for ($i = 0; $i < 5; $i++) {
            $this->planLesson($anchor->copy()->addWeeks($i), $i === 1
                ? ['status' => 'cancelled', 'cancellation_count_in_subscription' => false]
                : []);
        }

        $result = $this->project();

        $this->assertSame($anchor->copy()->addWeeks(4)->toDateString(), $result['projected_end_date']);
        $this->assertSame(1, $result['skipped_released_cancellations']);
    }

    /**
     * Une annulation tardive est déjà décomptée dans lessons_used : elle ne doit pas être
     * reprise dans la projection, sinon la séance serait facturée deux fois.
     */
    public function test_annulation_tardive_n_est_pas_comptee_deux_fois(): void
    {
        $anchor = $this->anchor();
        $late = $this->planLesson($anchor->copy(), ['status' => 'cancelled', 'cancellation_count_in_subscription' => true]);
        for ($i = 1; $i <= 4; $i++) {
            $this->planLesson($anchor->copy()->addWeeks($i));
        }

        $this->instance->fresh()->recalculateLessonsUsed();
        $this->assertSame(1, (int) $this->instance->fresh()->lessons_used, 'la séance tardive est déjà décomptée');

        $result = $this->project();

        // 3 crédits restants → les 3 premiers cours actifs suffisent.
        $this->assertSame(3, $result['remaining_consumed']);
        $this->assertSame($anchor->copy()->addWeeks(3)->toDateString(), $result['projected_end_date']);
        $this->assertSame(0, $result['skipped_released_cancellations']);
        $this->assertSame((int) $late->id, (int) $late->id);
    }

    /** Pas assez de cours planifiés : on prolonge la cadence de la série. */
    public function test_extrapolation_sur_la_cadence_de_la_serie(): void
    {
        $anchor = $this->anchor();
        $this->planLesson($anchor->copy());
        $this->planLesson($anchor->copy()->addWeek());
        $this->makeSeries($anchor);

        $result = $this->project();

        $this->assertSame('extrapolated', $result['status']);
        $this->assertSame(2, $result['planned_consuming_lessons']);
        $this->assertSame(2, $result['extrapolated_occurrences']);
        $this->assertSame($anchor->copy()->addWeeks(3)->toDateString(), $result['projected_end_date']);
    }

    /** L'extrapolation respecte l'intervalle : une série bi-hebdomadaire étale la fin. */
    public function test_extrapolation_respecte_l_intervalle_bihebdomadaire(): void
    {
        $anchor = $this->anchor();
        $this->makeSeries($anchor, interval: 2);

        $result = $this->project();

        $this->assertSame('extrapolated', $result['status']);
        $this->assertSame(4, $result['extrapolated_occurrences']);
        $this->assertSame($anchor->copy()->addWeeks(6)->toDateString(), $result['projected_end_date']);
    }

    /** L'extrapolation saute aussi les congés club. */
    public function test_extrapolation_saute_les_conges_club(): void
    {
        $anchor = $this->anchor();
        $this->makeSeries($anchor);
        ClubClosureDay::create([
            'club_id' => $this->club->id,
            'closed_on' => $anchor->copy()->addWeeks(2)->toDateString(),
        ]);

        $result = $this->project();

        $this->assertSame($anchor->copy()->addWeeks(4)->toDateString(), $result['projected_end_date']);
        $this->assertSame(1, $result['skipped_closure_days']);
    }

    /** Sans cours planifié ni série : la date est indéterminable, et on le dit. */
    public function test_sans_planning_la_date_est_indeterminable(): void
    {
        $result = $this->project();

        $this->assertSame('no_schedule', $result['status']);
        $this->assertNull($result['projected_end_date']);
    }

    /** Le carnet expirera avant d'être consommé : l'information est remontée. */
    public function test_signale_une_expiration_avant_epuisement(): void
    {
        $anchor = $this->anchor();
        $this->makeSeries($anchor);
        $this->instance->update(['expires_at' => $anchor->copy()->addWeek()->toDateString()]);

        $result = $this->project();

        $this->assertSame($anchor->copy()->addWeeks(3)->toDateString(), $result['projected_end_date']);
        $this->assertTrue($result['expires_before_exhaustion']);
    }
}
