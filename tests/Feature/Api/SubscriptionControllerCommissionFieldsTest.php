<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\SubscriptionTemplate;
use App\Models\Student;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\CourseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

/**
 * Tests d'intégration pour les champs de commission dans SubscriptionController
 * 
 * Ce fichier teste que les champs est_legacy, date_paiement et montant
 * sont correctement validés et sauvegardés lors de l'assignation d'abonnements.
 */
class SubscriptionControllerCommissionFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $clubUser;
    private Club $club;
    private SubscriptionTemplate $template;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur club
        $this->clubUser = User::create([
            'name' => 'Club User',
            'first_name' => 'Club',
            'last_name' => 'User',
            'email' => 'club@test.com',
            'password' => bcrypt('password'),
            'role' => 'club',
        ]);

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '+33123456789',
            'address' => '123 Test Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        // Associer l'utilisateur au club
        $this->club->users()->attach($this->clubUser->id, ['is_admin' => true]);

        // Créer une discipline
        $discipline = Discipline::create([
            'name' => 'Équitation',
            'slug' => 'equitation',
            'is_active' => true,
        ]);

        // Créer un type de cours
        $courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $discipline->id,
        ]);

        // Créer un template d'abonnement
        $this->template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'TEMPLATE-001',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 500.00,
            'validity_months' => 12,
            'is_active' => true,
        ]);

        // La table pivot subscription_template_course_types n'a pas de colonne discipline_id
        // On attache simplement le courseType au template
        $this->template->courseTypes()->attach($courseType->id);

        // Créer un élève
        $studentUser = User::create([
            'name' => 'Student User',
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
        ]);
    }

    /**
     * Test : Assignation d'un abonnement avec les champs de commission DCL
     * 
     * BUT : Vérifier qu'un abonnement peut être assigné avec est_legacy=false (DCL)
     * 
     * ENTRÉE : Requête POST avec est_legacy=false, date_paiement et montant
     * SORTIE ATTENDUE : L'abonnement est créé avec tous les champs correctement sauvegardés
     * 
     * POURQUOI : Permet d'assigner des abonnements avec classification DCL pour les commissions
     */
    #[Test]
    public function test_can_assign_subscription_with_dcl_fields(): void
    {
        Sanctum::actingAs($this->clubUser);

        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'montant' => 500.00,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'instance' => [
                    'id',
                    'est_legacy',
                    'date_paiement',
                    'montant',
                ],
            ],
        ]);

        $instance = SubscriptionInstance::find($response->json('data.instance.id'));
        $this->assertFalse($instance->est_legacy);
        $this->assertEquals('2025-11-15', $instance->date_paiement->format('Y-m-d'));
        $this->assertEquals(500.00, $instance->montant);
    }

    /**
     * Test : Assignation d'un abonnement avec les champs de commission NDCL
     * 
     * BUT : Vérifier qu'un abonnement peut être assigné avec est_legacy=true (NDCL)
     * 
     * ENTRÉE : Requête POST avec est_legacy=true
     * SORTIE ATTENDUE : L'abonnement est créé avec est_legacy=true
     * 
     * POURQUOI : Permet d'assigner des abonnements avec classification NDCL pour les commissions
     */
    #[Test]
    public function test_can_assign_subscription_with_ndcl_fields(): void
    {
        Sanctum::actingAs($this->clubUser);

        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'est_legacy' => true, // NDCL
            'date_paiement' => '2025-11-15',
            'montant' => 500.00,
        ]);

        $response->assertStatus(201);

        $instance = SubscriptionInstance::find($response->json('data.instance.id'));
        $this->assertTrue($instance->est_legacy);
    }

    /**
     * Test : Validation de est_legacy comme boolean
     * 
     * BUT : Vérifier que est_legacy doit être un boolean
     * 
     * ENTRÉE : Requête POST avec est_legacy='invalid'
     * SORTIE ATTENDUE : Erreur de validation 422
     * 
     * POURQUOI : Assure la cohérence des types de données
     */
    #[Test]
    public function test_validates_est_legacy_as_boolean(): void
    {
        Sanctum::actingAs($this->clubUser);

        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'expires_at' => null, // Ajouter pour éviter l'erreur Undefined array key
            'est_legacy' => 'invalid', // Doit être boolean
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test : Validation de date_paiement comme date
     * 
     * BUT : Vérifier que date_paiement doit être une date valide
     * 
     * ENTRÉE : Requête POST avec date_paiement='invalid-date'
     * SORTIE ATTENDUE : Erreur de validation 422
     * 
     * POURQUOI : Assure que la date de paiement est valide
     */
    #[Test]
    public function test_validates_date_paiement_as_date(): void
    {
        Sanctum::actingAs($this->clubUser);

        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'expires_at' => null, // Ajouter pour éviter l'erreur Undefined array key
            'date_paiement' => 'invalid-date', // Doit être une date valide
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test : Validation de montant comme numeric
     * 
     * BUT : Vérifier que montant doit être un nombre positif
     * 
     * ENTRÉE : Requête POST avec montant='invalid' ou montant=-10
     * SORTIE ATTENDUE : Erreur de validation 422
     * 
     * POURQUOI : Assure que le montant est valide et positif
     */
    #[Test]
    public function test_validates_montant_as_numeric(): void
    {
        Sanctum::actingAs($this->clubUser);

        // Test avec montant invalide
        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'expires_at' => null, // Ajouter pour éviter l'erreur Undefined array key
            'montant' => 'invalid', // Doit être un nombre
        ]);

        $response->assertStatus(422);

        // Test avec montant négatif
        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            'expires_at' => null, // Ajouter pour éviter l'erreur Undefined array key
            'montant' => -10, // Doit être positif
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test : Champs de commission optionnels
     * 
     * BUT : Vérifier qu'un abonnement peut être assigné sans les champs de commission
     * 
     * ENTRÉE : Requête POST sans est_legacy, date_paiement ni montant
     * SORTIE ATTENDUE : L'abonnement est créé avec succès
     * 
     * POURQUOI : Les champs de commission sont optionnels
     */
    #[Test]
    public function test_commission_fields_are_optional(): void
    {
        Sanctum::actingAs($this->clubUser);

        $response = $this->postJson('/api/club/subscriptions/assign', [
            'subscription_template_id' => $this->template->id,
            'student_ids' => [$this->student->id],
            'started_at' => Carbon::now()->format('Y-m-d'),
            // Pas de champs de commission
        ]);

        $response->assertStatus(201);

        $instance = SubscriptionInstance::find($response->json('data.instance.id'));
        $this->assertNotNull($instance);
    }
}

