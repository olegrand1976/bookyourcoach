<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Discipline;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected $club;
    protected $teacher;
    protected $student;
    protected $courseType;
    protected $discipline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsClub();
        $this->club = \App\Models\Club::find(Auth::user()->club_id);
        $this->teacher = Teacher::factory()->create(['club_id' => $this->club->id]);
        $this->student = Student::factory()->create(['club_id' => $this->club->id]);
        
        // Créer une discipline et un type de cours
        $this->discipline = Discipline::factory()->create();
        $this->courseType = CourseType::factory()->create([
            'discipline_id' => $this->discipline->id,
        ]);

        // Associer l'élève au club
        DB::table('club_students')->insert([
            'club_id' => $this->club->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_marks_future_lessons_as_uncovered_when_no_active_subscription()
    {
        // Créer un cours futur sans abonnement
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->addWeek(),
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['stats']['uncovered_future_lessons']);
        
        // Vérifier le cours
        $lesson = $data['lessons'][0];
        $this->assertTrue($lesson['subscription_coverage']['is_future']);
        $this->assertFalse($lesson['subscription_coverage']['is_covered']);
        $this->assertNotNull($lesson['subscription_coverage']['warning']);
    }

    /** @test */
    public function it_marks_future_lessons_as_covered_when_subscription_valid()
    {
        // Créer un template d'abonnement avec ce type de cours
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TEST-001',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        // Créer un abonnement actif
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6), // Expire dans 6 mois
        ]);
        $subscriptionInstance->students()->attach($this->student->id);

        // Créer un cours futur (dans 1 semaine, donc couvert par l'abonnement)
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->addWeek(),
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(0, $data['stats']['uncovered_future_lessons']);
        
        $lesson = $data['lessons'][0];
        $this->assertTrue($lesson['subscription_coverage']['is_future']);
        $this->assertTrue($lesson['subscription_coverage']['is_covered']);
        $this->assertNotNull($lesson['subscription_coverage']['coverage_end_date']);
    }

    /** @test */
    public function it_marks_lessons_as_uncovered_when_subscription_expired_before_lesson()
    {
        // Créer un template d'abonnement avec ce type de cours
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TEST-002',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        // Créer un abonnement qui expire bientôt
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now()->subMonths(5),
            'expires_at' => now()->addDays(3), // Expire dans 3 jours
        ]);
        $subscriptionInstance->students()->attach($this->student->id);

        // Créer un cours qui est APRÈS l'expiration de l'abonnement
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->addWeeks(2), // Dans 2 semaines (après expiration)
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Le cours devrait être non couvert car l'abonnement expire avant
        $this->assertEquals(1, $data['stats']['uncovered_future_lessons']);
        
        $lesson = $data['lessons'][0];
        $this->assertTrue($lesson['subscription_coverage']['is_future']);
        $this->assertFalse($lesson['subscription_coverage']['is_covered']);
    }

    /** @test */
    public function it_marks_lessons_as_uncovered_when_subscription_does_not_cover_course_type()
    {
        // Créer un autre type de cours (non couvert par l'abonnement)
        $otherCourseType = CourseType::factory()->create();

        // Créer un template d'abonnement qui ne couvre PAS le type de cours du cours
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TEST-003',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($otherCourseType->id); // Autre type de cours

        // Créer un abonnement actif
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
        ]);

        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(6),
        ]);
        $subscriptionInstance->students()->attach($this->student->id);

        // Créer un cours avec un type de cours NON couvert par l'abonnement
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id, // Ce type n'est PAS dans l'abonnement
            'start_time' => now()->addWeek(),
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Le cours devrait être non couvert car le type de cours n'est pas dans l'abonnement
        $this->assertEquals(1, $data['stats']['uncovered_future_lessons']);
    }

    /** @test */
    public function it_uses_latest_expiring_subscription_for_coverage()
    {
        // Créer un template d'abonnement avec ce type de cours
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TEST-004',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($this->courseType->id);

        // Créer un premier abonnement qui expire bientôt
        $subscription1 = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
        ]);
        $instance1 = SubscriptionInstance::create([
            'subscription_id' => $subscription1->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now()->subMonths(5),
            'expires_at' => now()->addDays(3), // Expire dans 3 jours
        ]);
        $instance1->students()->attach($this->student->id);

        // Créer un second abonnement qui expire plus tard
        $subscription2 = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
        ]);
        $instance2 = SubscriptionInstance::create([
            'subscription_id' => $subscription2->id,
            'status' => 'active',
            'lessons_used' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMonths(3), // Expire dans 3 mois
        ]);
        $instance2->students()->attach($this->student->id);

        // Créer un cours dans 2 semaines (couvert par le second abonnement seulement)
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->addWeeks(2),
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Le cours devrait être couvert grâce au second abonnement
        $this->assertEquals(0, $data['stats']['uncovered_future_lessons']);
        
        $lesson = $data['lessons'][0];
        $this->assertTrue($lesson['subscription_coverage']['is_covered']);
        // La date de couverture devrait être celle du second abonnement (la plus tardive)
        $this->assertEquals(
            now()->addMonths(3)->format('Y-m-d'), 
            $lesson['subscription_coverage']['coverage_end_date']
        );
    }

    /** @test */
    public function past_lessons_are_always_considered_covered()
    {
        // Créer un cours passé sans abonnement
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->subWeek(), // Cours passé
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Pas de cours futur non couvert
        $this->assertEquals(0, $data['stats']['uncovered_future_lessons']);
        
        $lesson = $data['lessons'][0];
        $this->assertFalse($lesson['subscription_coverage']['is_future']);
        $this->assertTrue($lesson['subscription_coverage']['is_covered']);
    }

    /** @test */
    public function cancelled_lessons_are_not_counted_as_uncovered()
    {
        // Créer un cours futur annulé sans abonnement
        Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'start_time' => now()->addWeek(),
            'status' => 'cancelled', // Cours annulé
        ]);

        $response = $this->getJson("/api/club/students/{$this->student->id}/history");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Les cours annulés ne doivent pas être comptés comme non couverts
        $this->assertEquals(0, $data['stats']['uncovered_future_lessons']);
    }
}

