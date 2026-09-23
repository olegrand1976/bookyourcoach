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

/**
 * Garde-fous autour du contrôle « enseignant en parallèle » à la mise à jour d'un cours.
 *
 * À la mise à jour, le cours édité matérialise lui-même sa propre série récurrente : il doit
 * être exclu du contrôle (sinon il se déclare en conflit avec elle), mais l'exclusion ne doit
 * pas masquer une vraie collision avec la série d'un autre élève.
 */
class LessonSelfConflictTest extends TestCase
{
    use RefreshDatabase;

    protected $club;

    protected Teacher $teacher;

    protected Student $studentA;

    protected CourseType $courseType;

    protected Location $location;

    protected SubscriptionInstance $instanceA;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->actingAsClub();
        $this->club = Club::find($user->club_id);

        $this->teacher = Teacher::factory()->create();
        $this->teacher->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);

        $this->studentA = Student::factory()->create(['club_id' => $this->club->id]);
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();

        $this->instanceA = $this->makeInstanceFor($this->studentA);
    }

    /** Abonnement avec template (capacité connue) rattaché à un élève. */
    private function makeInstanceFor(Student $student): SubscriptionInstance
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'SELF-'.uniqid(),
            'total_lessons' => 20,
            'validity_months' => 12,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => 'SUB-SELF-'.uniqid(),
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

    private function makeLesson(Student $student, Teacher $teacher, Carbon $start): Lesson
    {
        return Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
    }

    private function makeSeries(Student $student, Teacher $teacher, SubscriptionInstance $instance, Carbon $anchor): SubscriptionRecurringSlot
    {
        return SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $instance->id,
            'teacher_id' => $teacher->id,
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

    /** Un cours qui matérialise sa propre série ne doit pas entrer en conflit avec elle. */
    public function test_un_cours_de_sa_propre_serie_peut_etre_modifie(): void
    {
        $start = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        $lesson = $this->makeLesson($this->studentA, $this->teacher, $start);
        $lesson->subscriptionInstances()->attach($this->instanceA->id);
        $this->makeSeries($this->studentA, $this->teacher, $this->instanceA, $start);

        $response = $this->putJson("/api/lessons/{$lesson->id}", [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 65.00,
            'update_scope' => 'single',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(65.00, (float) $lesson->fresh()->price);
    }

    /**
     * L'exclusion ne porte que sur le cours édité : déplacer un cours sur l'occurrence
     * matérialisée de la série d'un AUTRE élève reste refusé.
     *
     * L'occurrence de B est matérialisée par un cours donné par un remplaçant : ainsi seule
     * la branche « réservation récurrente » du contrôle peut détecter la collision (la branche
     * « cours de cet enseignant » ne voit rien).
     */
    public function test_deplacer_un_cours_sur_la_serie_d_un_autre_eleve_reste_refuse(): void
    {
        $studentB = Student::factory()->create(['club_id' => $this->club->id]);
        $instanceB = $this->makeInstanceFor($studentB);
        $replacement = Teacher::factory()->create();
        $replacement->clubs()->attach($this->club->id, ['is_active' => true, 'joined_at' => now()]);

        $target = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        $this->makeSeries($studentB, $this->teacher, $instanceB, $target);
        $this->makeLesson($studentB, $replacement, $target)
            ->subscriptionInstances()->attach($instanceB->id);

        $lessonA = $this->makeLesson($this->studentA, $this->teacher, $target->copy()->setTime(14, 0));
        $lessonA->subscriptionInstances()->attach($this->instanceA->id);

        $response = $this->putJson("/api/lessons/{$lessonA->id}", [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->studentA->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $target->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 50.00,
            'update_scope' => 'single',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('réservation récurrente', (string) $response->json('message'));
    }
}
