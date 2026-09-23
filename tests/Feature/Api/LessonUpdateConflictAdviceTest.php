<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\ClubOpenSlot;
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

/**
 * Le 422 d'un déplacement de série porte les mêmes alternatives que celui de la création.
 *
 * Jusqu'ici l'utilisateur ne recevait qu'un refus sec à l'édition, là où la création lui
 * proposait des créneaux libres revalidés sur 26 semaines.
 */
class LessonUpdateConflictAdviceTest extends TestCase
{
    use RefreshDatabase;

    private $club;

    private Teacher $teacher;

    private Student $studentA;

    private Student $studentB;

    private CourseType $courseType;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        config(['bookyourcoach.recurring_planning_advice.attach_on_validation_failure' => true]);
        config(['bookyourcoach.recurring_planning_advice.use_ai' => false]);

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);

        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);

        $this->studentA = Student::factory()->create(['club_id' => $this->club->id]);
        $this->studentB = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create(['duration_minutes' => 60]);
        $this->location = Location::factory()->create();
    }

    private function makeInstanceFor(Student $student): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'ADV-'.uniqid(),
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-ADV-'.uniqid(),
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'manual_lessons_used' => 0,
            'started_at' => Carbon::now()->subDays(30),
            'expires_at' => Carbon::now()->addDays(335),
        ]);
        $instance->students()->attach($student->id);

        return $instance;
    }

    private function makeLesson(Student $student, Carbon $start): Lesson
    {
        return Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
    }

    private function makeSeries(Student $student, SubscriptionInstance $instance, Carbon $anchor): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'day_of_week' => $anchor->dayOfWeek,
            'start_time' => $anchor->format('H:i:s'),
            'end_time' => $anchor->copy()->addHour()->format('H:i:s'),
            'recurring_interval' => 1,
            'start_date' => $anchor->copy()->startOfDay(),
            'end_date' => $anchor->copy()->addWeeks(26),
            'status' => 'active',
        ]);
    }

    /**
     * Prépare un déplacement refusé par la validation 26 semaines.
     *
     * Le conflit doit surgir sur une occurrence FUTURE, pas sur la date d'arrivée : le contrôle
     * de chevauchement direct s'exécute avant la validation de déplacement et produirait sinon
     * son propre 422, sans alternatives. La série de B démarre donc trois semaines plus tard.
     *
     * @return array{0: Lesson, 1: Carbon}
     */
    private function prepareBlockedRelocation(): array
    {
        $anchor = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        $target = $anchor->copy()->addHours(4);

        // Créneau ouvert du club, pour que des alternatives existent.
        ClubOpenSlot::create([
            'club_id' => $this->club->id,
            'day_of_week' => $anchor->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'max_slots' => 3,
            'max_capacity' => 6,
            'is_active' => true,
        ]);

        // Série de B sur le créneau d'arrivée, mais à partir de la 3e semaine : la date
        // d'arrivée elle-même reste libre, la collision n'apparaît qu'en projetant la série.
        $instanceB = $this->makeInstanceFor($this->studentB);
        $this->makeSeries($this->studentB, $instanceB, $target->copy()->addWeeks(3));

        // Série de A, qu'on tente de déplacer sur ce créneau.
        $instanceA = $this->makeInstanceFor($this->studentA);
        $this->makeSeries($this->studentA, $instanceA, $anchor);
        $lessonA = $this->makeLesson($this->studentA, $anchor->copy());
        $instanceA->lessons()->attach($lessonA->id);

        return [$lessonA, $target];
    }

    private function attemptRelocation(Lesson $lesson, Carbon $target)
    {
        return $this->putJson("/api/lessons/{$lesson->id}", [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $target->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'update_scope' => 'all_future',
            'recurring_interval' => 1,
        ]);
    }

    /** Déplacement refusé : le refus s'accompagne des alternatives, comme à la création. */
    public function test_le_422_de_deplacement_porte_les_alternatives(): void
    {
        [$lessonA, $target] = $this->prepareBlockedRelocation();

        $response = $this->attemptRelocation($lessonA, $target);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        $body = $response->json();
        $this->assertArrayHasKey('conflicts', $body, 'le refus doit rester explicite');
        $this->assertNotEmpty($body['conflicts']);

        $this->assertArrayHasKey(
            'planning_advice',
            $body,
            'le déplacement doit proposer des alternatives, comme la création'
        );
        $this->assertArrayHasKey('requested', $body['planning_advice']);
        $this->assertArrayHasKey('alternatives', $body['planning_advice']);
        $this->assertArrayHasKey('meta', $body['planning_advice']);
    }

    /** Le drapeau de configuration reste respecté : pas d'alternatives quand il est désactivé. */
    public function test_les_alternatives_suivent_la_configuration(): void
    {
        config(['bookyourcoach.recurring_planning_advice.attach_on_validation_failure' => false]);

        [$lessonA, $target] = $this->prepareBlockedRelocation();

        $response = $this->attemptRelocation($lessonA, $target);

        $response->assertStatus(422);
        $body = $response->json();
        $this->assertArrayHasKey('conflicts', $body, 'le refus reste explicite');
        $this->assertArrayNotHasKey('planning_advice', $body);
    }
}
