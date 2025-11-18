<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CommissionCalculationService;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour CommissionCalculationService avec les cours individuels
 * 
 * Ce fichier teste l'inclusion des cours individuels (non liés à un abonnement)
 * dans le calcul des commissions pour les rapports de paie.
 * 
 * Scénario de test : Validation du calcul incluant cours individuels pour Novembre 2025
 * 
 * Données de test :
 * - prof_alpha : 2 abonnements + 1 cours individuel DCL + 1 cours individuel NDCL
 * - prof_beta : 1 abonnement + 1 cours individuel DCL
 * 
 * Règles de calcul :
 * - DCL (est_legacy=false) : 100% de commission
 * - NDCL (est_legacy=true) : 100% de commission
 */
class CommissionCalculationServiceWithLessonsTest extends TestCase
{
    use RefreshDatabase;

    private CommissionCalculationService $service;
    private Teacher $teacherAlpha;
    private Teacher $teacherBeta;
    private Subscription $subscription;
    private Club $club;
    private CourseType $courseType;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CommissionCalculationService();

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

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
        ]);

        // Créer une location
        $this->location = Location::create([
            'name' => 'Location Test',
            'address' => '123 Test Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        // Créer prof_alpha
        $userAlpha = User::create([
            'name' => 'Alpha Teacher',
            'first_name' => 'Alpha',
            'last_name' => 'Teacher',
            'email' => 'prof_alpha@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacherAlpha = Teacher::create([
            'user_id' => $userAlpha->id,
            'hourly_rate' => 50.00,
            'is_available' => true,
        ]);

        // Créer prof_beta
        $userBeta = User::create([
            'name' => 'Beta Teacher',
            'first_name' => 'Beta',
            'last_name' => 'Teacher',
            'email' => 'prof_beta@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacherBeta = Teacher::create([
            'user_id' => $userBeta->id,
            'hourly_rate' => 50.00,
            'is_available' => true,
        ]);

        // Créer un abonnement modèle
        $this->subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'price' => 100.00,
            'is_active' => true,
        ]);
    }

    /**
     * Test : Inclusion des cours individuels dans le rapport
     * 
     * BUT : Vérifier que les cours individuels (non liés à un abonnement) sont inclus dans le calcul
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 abonnement DCL (100€) + 1 cours individuel DCL (50€)
     * - prof_beta : 1 cours individuel DCL (75€)
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=150€, total_commissions_ndcl=0€, total_a_payer=150€
     * - prof_beta : total_commissions_dcl=75€, total_commissions_ndcl=0€, total_a_payer=75€
     * 
     * POURQUOI : Les cours individuels doivent être comptabilisés dans les rapports de paie
     */
    #[Test]
    public function test_includes_individual_lessons_in_report(): void
    {
        // Créer un abonnement pour prof_alpha
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-10',
            'started_at' => '2025-11-10',
            'status' => 'active',
        ]);

        // Créer un cours individuel DCL pour prof_alpha (non lié à un abonnement)
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Créer un cours individuel DCL pour prof_beta
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherBeta->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-20 14:00:00'),
            'end_time' => Carbon::parse('2025-11-20 15:00:00'),
            'price' => 75.00,
            'montant' => 75.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-20',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        $this->assertEquals(150.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 150.00 € (100 abonnement + 50 cours individuel)');
        $this->assertEquals(0.00, $alphaData['total_commissions_ndcl'], 
            'prof_alpha total_commissions_ndcl doit être 0.00 €');
        $this->assertEquals(150.00, $alphaData['total_a_payer'], 
            'prof_alpha total_a_payer doit être 150.00 €');

        // Vérifier prof_beta
        $this->assertArrayHasKey($this->teacherBeta->id, $report);
        $betaData = $report[$this->teacherBeta->id];
        
        $this->assertEquals(75.00, $betaData['total_commissions_dcl'], 
            'prof_beta total_commissions_dcl doit être 75.00 € (cours individuel uniquement)');
        $this->assertEquals(0.00, $betaData['total_commissions_ndcl'], 
            'prof_beta total_commissions_ndcl doit être 0.00 €');
        $this->assertEquals(75.00, $betaData['total_a_payer'], 
            'prof_beta total_a_payer doit être 75.00 €');
    }

    /**
     * Test : Distinction DCL/NDCL pour les cours individuels
     * 
     * BUT : Vérifier que les cours individuels sont correctement classés en DCL ou NDCL
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 cours individuel DCL (50€) + 1 cours individuel NDCL (80€)
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=50€, total_commissions_ndcl=80€, total_a_payer=130€
     * 
     * POURQUOI : Les cours individuels doivent respecter la classification DCL/NDCL
     */
    #[Test]
    public function test_distinguishes_dcl_ndcl_for_individual_lessons(): void
    {
        // Créer un cours individuel DCL pour prof_alpha
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-10 10:00:00'),
            'end_time' => Carbon::parse('2025-11-10 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-10',
            'status' => 'confirmed',
        ]);

        // Créer un cours individuel NDCL pour prof_alpha
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 14:00:00'),
            'end_time' => Carbon::parse('2025-11-15 15:00:00'),
            'price' => 80.00,
            'montant' => 80.00,
            'est_legacy' => true, // NDCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        $this->assertEquals(50.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 50.00 €');
        $this->assertEquals(80.00, $alphaData['total_commissions_ndcl'], 
            'prof_alpha total_commissions_ndcl doit être 80.00 €');
        $this->assertEquals(130.00, $alphaData['total_a_payer'], 
            'prof_alpha total_a_payer doit être 130.00 € (50 + 80)');
    }

    /**
     * Test : Exclusion des cours liés à un abonnement
     * 
     * BUT : Vérifier que les cours liés à un abonnement ne sont pas comptés deux fois
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 abonnement (100€) + 1 cours lié à cet abonnement (50€)
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=100€ (seulement l'abonnement, pas le cours)
     * 
     * POURQUOI : Un cours lié à un abonnement ne doit pas être compté comme cours individuel
     */
    #[Test]
    public function test_excludes_lessons_linked_to_subscriptions(): void
    {
        // Créer un abonnement pour prof_alpha
        $subscriptionInstance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-10',
            'started_at' => '2025-11-10',
            'status' => 'active',
        ]);

        // Créer un cours lié à cet abonnement
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Lier le cours à l'abonnement
        $subscriptionInstance->lessons()->attach($lesson->id);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        // Seul l'abonnement doit être compté, pas le cours individuel (car il est lié)
        $this->assertEquals(100.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 100.00 € (seulement l\'abonnement)');
        $this->assertEquals(0.00, $alphaData['total_commissions_ndcl'], 
            'prof_alpha total_commissions_ndcl doit être 0.00 €');
    }

    /**
     * Test : Utilisation de montant au lieu de price pour les cours individuels
     * 
     * BUT : Vérifier que le montant réellement payé est utilisé si disponible
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 cours individuel avec price=50€ mais montant=45€
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=45€ (utilise montant, pas price)
     * 
     * POURQUOI : Le montant réellement payé peut différer du prix du cours
     */
    #[Test]
    public function test_uses_montant_instead_of_price_for_lessons(): void
    {
        // Créer un cours individuel avec montant différent de price
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00, // Prix du cours
            'montant' => 45.00, // Montant réellement payé (remise)
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        // Le montant réellement payé doit être utilisé
        $this->assertEquals(45.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 45.00 € (utilise montant, pas price)');
    }

    /**
     * Test : Utilisation de price si montant n'est pas défini
     * 
     * BUT : Vérifier que price est utilisé comme fallback si montant est null
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 cours individuel avec price=50€ mais montant=null
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=50€ (utilise price comme fallback)
     * 
     * POURQUOI : Si montant n'est pas défini, utiliser price comme valeur par défaut
     */
    #[Test]
    public function test_uses_price_as_fallback_when_montant_is_null(): void
    {
        // Créer un cours individuel sans montant
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => null, // Montant non défini
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        // Le prix doit être utilisé comme fallback
        $this->assertEquals(50.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 50.00 € (utilise price comme fallback)');
    }

    /**
     * Test : Exclusion des cours sans montant valide
     * 
     * BUT : Vérifier que les cours sans montant ou price valide sont ignorés
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 cours individuel avec montant=0 et price=0
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : n'apparaît pas dans le rapport
     * 
     * POURQUOI : Les cours sans montant valide ne doivent pas être comptabilisés
     */
    #[Test]
    public function test_excludes_lessons_without_valid_amount(): void
    {
        // Créer un cours individuel sans montant valide
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 0,
            'montant' => 0,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Le rapport ne doit pas contenir prof_alpha car son cours n'a pas de montant valide
        $this->assertArrayNotHasKey($this->teacherAlpha->id, $report);
    }

    /**
     * Test : Exclusion des cours avec statut invalide
     * 
     * BUT : Vérifier que seuls les cours confirmés ou complétés sont inclus
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 cours individuel avec status='pending'
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : n'apparaît pas dans le rapport
     * 
     * POURQUOI : Seuls les cours confirmés ou complétés doivent être comptabilisés
     */
    #[Test]
    public function test_excludes_lessons_with_invalid_status(): void
    {
        // Créer un cours individuel avec status='pending'
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'pending', // Statut invalide
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Le rapport ne doit pas contenir prof_alpha car son cours n'est pas confirmé/complété
        $this->assertArrayNotHasKey($this->teacherAlpha->id, $report);
    }

    /**
     * Test : Filtrage par club
     * 
     * BUT : Vérifier que le filtrage par club fonctionne pour les cours individuels
     * 
     * ENTRÉE : 
     * - Club 1 : prof_alpha avec 1 cours individuel (50€)
     * - Club 2 : prof_beta avec 1 cours individuel (75€)
     * 
     * SORTIE ATTENDUE :
     * - Rapport pour Club 1 : seulement prof_alpha (50€)
     * - Rapport pour Club 2 : seulement prof_beta (75€)
     * 
     * POURQUOI : Les rapports doivent être filtrés par club
     */
    #[Test]
    public function test_filters_lessons_by_club(): void
    {
        // Créer un deuxième club
        $club2 = Club::create([
            'name' => 'Club Test 2',
            'email' => 'test2@club.com',
            'phone' => '+33123456789',
            'address' => '456 Test Street',
            'city' => 'Paris',
            'postal_code' => '75002',
            'country' => 'France',
        ]);

        // Créer un cours individuel pour prof_alpha dans Club 1
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Créer un cours individuel pour prof_beta dans Club 2
        Lesson::create([
            'club_id' => $club2->id,
            'teacher_id' => $this->teacherBeta->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-20 14:00:00'),
            'end_time' => Carbon::parse('2025-11-20 15:00:00'),
            'price' => 75.00,
            'montant' => 75.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-20',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Club 1
        $reportClub1 = $this->service->generatePayrollReport(2025, 11, $this->club->id);
        
        // Générer le rapport pour Club 2
        $reportClub2 = $this->service->generatePayrollReport(2025, 11, $club2->id);

        // Vérifier Club 1
        $this->assertArrayHasKey($this->teacherAlpha->id, $reportClub1);
        $this->assertArrayNotHasKey($this->teacherBeta->id, $reportClub1);
        $this->assertEquals(50.00, $reportClub1[$this->teacherAlpha->id]['total_commissions_dcl']);

        // Vérifier Club 2
        $this->assertArrayHasKey($this->teacherBeta->id, $reportClub2);
        $this->assertArrayNotHasKey($this->teacherAlpha->id, $reportClub2);
        $this->assertEquals(75.00, $reportClub2[$this->teacherBeta->id]['total_commissions_dcl']);
    }

    /**
     * Test : Rapport combiné abonnements + cours individuels
     * 
     * BUT : Vérifier que les abonnements et cours individuels sont correctement combinés
     * 
     * ENTRÉE : 
     * - prof_alpha : 1 abonnement DCL (100€) + 1 cours individuel DCL (50€) + 1 cours individuel NDCL (80€)
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : total_commissions_dcl=150€, total_commissions_ndcl=80€, total_a_payer=230€
     * 
     * POURQUOI : Les abonnements et cours individuels doivent être agrégés correctement
     */
    #[Test]
    public function test_combines_subscriptions_and_individual_lessons(): void
    {
        // Créer un abonnement DCL pour prof_alpha
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-10',
            'started_at' => '2025-11-10',
            'status' => 'active',
        ]);

        // Créer un cours individuel DCL pour prof_alpha
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-15 10:00:00'),
            'end_time' => Carbon::parse('2025-11-15 11:00:00'),
            'price' => 50.00,
            'montant' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'status' => 'confirmed',
        ]);

        // Créer un cours individuel NDCL pour prof_alpha
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacherAlpha->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2025-11-20 14:00:00'),
            'end_time' => Carbon::parse('2025-11-20 15:00:00'),
            'price' => 80.00,
            'montant' => 80.00,
            'est_legacy' => true, // NDCL
            'date_paiement' => '2025-11-20',
            'status' => 'confirmed',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        $this->assertEquals(150.00, $alphaData['total_commissions_dcl'], 
            'prof_alpha total_commissions_dcl doit être 150.00 € (100 abonnement + 50 cours individuel)');
        $this->assertEquals(80.00, $alphaData['total_commissions_ndcl'], 
            'prof_alpha total_commissions_ndcl doit être 80.00 € (cours individuel NDCL)');
        $this->assertEquals(230.00, $alphaData['total_a_payer'], 
            'prof_alpha total_a_payer doit être 230.00 € (150 + 80)');
    }
}

