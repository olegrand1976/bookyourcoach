<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CommissionCalculationService;
use App\Models\SubscriptionInstance;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Tests unitaires pour CommissionCalculationService
 * 
 * Scénario de test : Validation du calcul de commissions pour Novembre 2025
 * 
 * Données de test :
 * - prof_alpha : 3 abonnements (2 Type 1, 1 Type 2) en Novembre
 * - prof_beta : 1 abonnement Type 1 en Novembre
 * - 2 abonnements hors période (Octobre) doivent être ignorés
 * 
 * Règles de calcul :
 * - Type 1 : 100% de commission (montant complet)
 * - Type 2 : 100% de commission (montant complet)
 */
class CommissionCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommissionCalculationService $service;
    private Teacher $teacherAlpha;
    private Teacher $teacherBeta;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CommissionCalculationService();

        // Créer un club
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '+33123456789',
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
            'club_id' => $club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'price' => 100.00,
            'is_active' => true,
        ]);
    }

    /**
     * Test : Calcul de commission Type 1
     * 
     * BUT : Vérifier que la commission Type 1 est calculée à 70%
     * 
     * ENTRÉE : Abonnement Type 1 avec montant 100.00 €
     * SORTIE ATTENDUE : 100.00 € (100.00 × 1.00 = 100%)
     */
    public function test_calculates_type1_commission_correctly(): void
    {
        $instance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-05',
            'started_at' => '2025-11-05',
            'status' => 'active',
        ]);

        $commission = $this->service->calculateCommission($instance);

        $this->assertEquals(100.00, $commission);
    }

    /**
     * Test : Calcul de commission Type 2
     * 
     * BUT : Vérifier que la commission Type 2 est calculée à 100%
     * 
     * ENTRÉE : Abonnement Type 2 avec montant 80.00 €
     * SORTIE ATTENDUE : 80.00 € (80.00 × 1.00 = 100%)
     */
    public function test_calculates_type2_commission_correctly(): void
    {
        $instance = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 80.00,
            'est_legacy' => true,
            'date_paiement' => '2025-11-15',
            'started_at' => '2025-11-15',
            'status' => 'active',
        ]);

        $commission = $this->service->calculateCommission($instance);

        $this->assertEquals(80.00, $commission);
    }

    /**
     * Test : Génération du rapport mensuel complet
     * 
     * BUT : Vérifier que le rapport génère les bons totaux pour Novembre 2025
     * 
     * ENTRÉE : 
     * - prof_alpha : 100€ T1 (100€), 50€ T1 (50€), 80€ T2 (80€)
     * - prof_beta : 100€ T1 (100€)
     * - 2 abonnements Octobre (doivent être ignorés)
     * 
     * SORTIE ATTENDUE :
     * - prof_alpha : T1=150€, T2=80€, Total=230€
     * - prof_beta : T1=100€, T2=0€, Total=100€
     */
    public function test_generates_correct_payroll_report_for_november_2025(): void
    {
        // Créer les abonnements de Novembre 2025
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-05',
            'started_at' => '2025-11-05',
            'status' => 'active',
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 50.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-10',
            'started_at' => '2025-11-10',
            'status' => 'active',
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 80.00,
            'est_legacy' => true,
            'date_paiement' => '2025-11-15',
            'started_at' => '2025-11-15',
            'status' => 'active',
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherBeta->id,
            'montant' => 100.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-20',
            'started_at' => '2025-11-20',
            'status' => 'active',
        ]);

        // Créer les abonnements d'Octobre (doivent être ignorés)
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 1000.00,
            'est_legacy' => false,
            'date_paiement' => '2025-10-30',
            'started_at' => '2025-10-30',
            'status' => 'active',
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherBeta->id,
            'montant' => 200.00,
            'est_legacy' => true,
            'date_paiement' => '2025-10-28',
            'started_at' => '2025-10-28',
            'status' => 'active',
        ]);

        // Générer le rapport pour Novembre 2025
        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier prof_alpha
        $this->assertArrayHasKey($this->teacherAlpha->id, $report);
        $alphaData = $report[$this->teacherAlpha->id];
        
        $this->assertEquals(150.00, $alphaData['total_commissions_dcl'] ?? $alphaData['total_commissions_type1'] ?? 0, 
            'prof_alpha total_commissions_dcl doit être 150.00 € (100 + 50)');
        $this->assertEquals(80.00, $alphaData['total_commissions_ndcl'] ?? $alphaData['total_commissions_type2'] ?? 0, 
            'prof_alpha total_commissions_ndcl doit être 80.00 €');
        $this->assertEquals(230.00, $alphaData['total_a_payer'], 
            'prof_alpha total_a_payer doit être 230.00 € (150 + 80)');

        // Vérifier prof_beta
        $this->assertArrayHasKey($this->teacherBeta->id, $report);
        $betaData = $report[$this->teacherBeta->id];
        
        $this->assertEquals(100.00, $betaData['total_commissions_dcl'] ?? $betaData['total_commissions_type1'] ?? 0, 
            'prof_beta total_commissions_dcl doit être 100.00 €');
        $this->assertEquals(0.00, $betaData['total_commissions_ndcl'] ?? $betaData['total_commissions_type2'] ?? 0, 
            'prof_beta total_commissions_ndcl doit être 0.00 €');
        $this->assertEquals(100.00, $betaData['total_a_payer'], 
            'prof_beta total_a_payer doit être 100.00 €');

        // Vérifier que seuls 2 enseignants sont dans le rapport
        $this->assertCount(2, $report, 'Le rapport doit contenir exactement 2 enseignants');
    }

    /**
     * Test : Filtre de date fonctionne correctement
     * 
     * BUT : Vérifier que les abonnements hors période sont ignorés
     * 
     * ENTRÉE : Abonnements en Octobre et Novembre
     * SORTIE ATTENDUE : Seulement les abonnements de Novembre dans le rapport
     */
    public function test_filters_subscriptions_by_date_correctly(): void
    {
        // Abonnement Novembre (doit être inclus)
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-15',
            'started_at' => '2025-11-15',
            'status' => 'active',
        ]);

        // Abonnement Octobre (doit être exclu)
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 1000.00,
            'est_legacy' => false,
            'date_paiement' => '2025-10-30',
            'started_at' => '2025-10-30',
            'status' => 'active',
        ]);

        $report = $this->service->generatePayrollReport(2025, 11);

        // Vérifier que seul l'abonnement de Novembre est compté
        $alphaData = $report[$this->teacherAlpha->id];
        $this->assertEquals(100.00, $alphaData['total_commissions_dcl'] ?? $alphaData['total_commissions_type1'] ?? 0, 
            'Seul l\'abonnement de Novembre doit être compté (100 × 1.00 = 100)');
    }

    /**
     * Test : Gestion des abonnements sans montant
     * 
     * BUT : Vérifier que les abonnements sans montant sont ignorés
     * 
     * ENTRÉE : Abonnement avec montant null ou 0
     * SORTIE ATTENDUE : Abonnement ignoré dans le rapport
     */
    public function test_ignores_subscriptions_without_amount(): void
    {
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => null,
            'est_legacy' => false,
            'date_paiement' => '2025-11-15',
            'started_at' => '2025-11-15',
            'status' => 'active',
        ]);

        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 0,
            'est_legacy' => false,
            'date_paiement' => '2025-11-20',
            'started_at' => '2025-11-20',
            'status' => 'active',
        ]);

        $report = $this->service->generatePayrollReport(2025, 11);

        // Le rapport ne doit pas contenir prof_alpha car ses abonnements n'ont pas de montant valide
        $this->assertArrayNotHasKey($this->teacherAlpha->id, $report);
    }

    /**
     * Test : Gestion des abonnements annulés
     * 
     * BUT : Vérifier que les abonnements annulés sont ignorés
     * 
     * ENTRÉE : Abonnement avec status 'cancelled'
     * SORTIE ATTENDUE : Abonnement ignoré dans le rapport
     */
    public function test_ignores_cancelled_subscriptions(): void
    {
        SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'teacher_id' => $this->teacherAlpha->id,
            'montant' => 100.00,
            'est_legacy' => false,
            'date_paiement' => '2025-11-15',
            'started_at' => '2025-11-15',
            'status' => 'cancelled',
        ]);

        $report = $this->service->generatePayrollReport(2025, 11);

        // Le rapport ne doit pas contenir prof_alpha car son abonnement est annulé
        $this->assertArrayNotHasKey($this->teacherAlpha->id, $report);
    }
}

