<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Club;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class ClubPayrollPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected $clubUser;
    protected $club;
    protected $teacher;
    protected $year;
    protected $month;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur club
        $this->clubUser = User::factory()->create([
            'role' => 'club',
            'email' => 'club@test.com',
        ]);

        // Créer un club
        $this->club = Club::factory()->create([
            'name' => 'Test Club',
        ]);

        // Associer l'utilisateur au club
        $this->club->users()->attach($this->clubUser->id, [
            'is_admin' => true,
            'role' => 'admin',
        ]);

        // Créer un enseignant
        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@test.com',
        ]);

        $this->teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        // Période de test (mois actuel)
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
    }

    /**
     * Test de récupération des détails des paiements pour un enseignant
     */
    public function test_get_teacher_payments_details()
    {
        // Créer un cours payé ce mois-ci
        $courseType = CourseType::factory()->create([
            'name' => 'Cours Test',
        ]);

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => 50.00,
            'date_paiement' => Carbon::create($this->year, $this->month, 15),
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->getJson("/api/club/payroll/reports/{$this->year}/{$this->month}/teachers/{$this->teacher->id}/payments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teacher_id',
                    'period',
                    'lessons',
                    'subscriptions',
                    'totals',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->teacher->id, $response->json('data.teacher_id'));
        $this->assertCount(1, $response->json('data.lessons'));
    }

    /**
     * Test de validation d'un paiement
     */
    public function test_validate_payment()
    {
        $courseType = CourseType::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => null, // Pas encore payé
            'date_paiement' => null,
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->putJson("/api/club/payroll/reports/{$this->year}/{$this->month}/teachers/{$this->teacher->id}/payments", [
                'updates' => [
                    [
                        'id' => $lesson->id,
                        'type' => 'lesson',
                        'action' => 'validate',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le paiement a été validé
        $lesson->refresh();
        $this->assertNotNull($lesson->date_paiement);
        $this->assertEquals($lesson->price, $lesson->montant);
    }

    /**
     * Test de modification d'un paiement
     */
    public function test_modify_payment()
    {
        $courseType = CourseType::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => 50.00,
            'date_paiement' => Carbon::create($this->year, $this->month, 15),
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $newAmount = 45.00;
        $newDate = Carbon::create($this->year, $this->month, 20)->format('Y-m-d');

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->putJson("/api/club/payroll/reports/{$this->year}/{$this->month}/teachers/{$this->teacher->id}/payments", [
                'updates' => [
                    [
                        'id' => $lesson->id,
                        'type' => 'lesson',
                        'action' => 'modify',
                        'montant' => $newAmount,
                        'date_paiement' => $newDate,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le paiement a été modifié
        $lesson->refresh();
        $this->assertEquals($newAmount, (float) $lesson->montant);
        $this->assertEquals($newDate, $lesson->date_paiement->format('Y-m-d'));
    }

    /**
     * Test de report d'un paiement au mois suivant
     */
    public function test_defer_payment()
    {
        $courseType = CourseType::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => 50.00,
            'date_paiement' => Carbon::create($this->year, $this->month, 15),
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->putJson("/api/club/payroll/reports/{$this->year}/{$this->month}/teachers/{$this->teacher->id}/payments", [
                'updates' => [
                    [
                        'id' => $lesson->id,
                        'type' => 'lesson',
                        'action' => 'defer',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le paiement a été reporté au mois suivant
        $lesson->refresh();
        $nextMonth = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->assertEquals($nextMonth->month, $lesson->date_paiement->month);
        $this->assertEquals($nextMonth->year, $lesson->date_paiement->year);
    }

    /**
     * Test de rechargement d'un rapport sans réinitialiser
     */
    public function test_reload_report_keep_changes()
    {
        $courseType = CourseType::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => 45.00, // Montant modifié manuellement
            'date_paiement' => Carbon::create($this->year, $this->month, 15),
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->postJson("/api/club/payroll/reports/{$this->year}/{$this->month}/reload", [
                'reset_manual_changes' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le montant modifié est toujours là
        $lesson->refresh();
        $this->assertEquals(45.00, (float) $lesson->montant);
    }

    /**
     * Test de rechargement d'un rapport avec réinitialisation
     */
    public function test_reload_report_reset_changes()
    {
        $courseType = CourseType::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'course_type_id' => $courseType->id,
            'price' => 50.00,
            'montant' => 45.00, // Montant modifié manuellement
            'date_paiement' => Carbon::create($this->year, $this->month, 15),
            'start_time' => Carbon::create($this->year, $this->month, 15, 10, 0),
            'status' => 'completed',
            'est_legacy' => false,
        ]);

        $response = $this->actingAs($this->clubUser, 'sanctum')
            ->postJson("/api/club/payroll/reports/{$this->year}/{$this->month}/reload", [
                'reset_manual_changes' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Vérifier que le montant modifié a été réinitialisé
        $lesson->refresh();
        $this->assertNull($lesson->montant); // Réinitialisé à null
    }

    /**
     * Test d'accès non autorisé pour un utilisateur non-club
     */
    public function test_unauthorized_access()
    {
        $otherUser = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/club/payroll/reports/{$this->year}/{$this->month}/teachers/{$this->teacher->id}/payments");

        $response->assertStatus(403);
    }
}

