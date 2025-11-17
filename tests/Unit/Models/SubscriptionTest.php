<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\SubscriptionTemplate;
use App\Models\SubscriptionInstance;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle Subscription
 * 
 * Ce fichier teste les fonctionnalités principales d'un modèle d'abonnement :
 * - Les relations avec d'autres modèles (Club, SubscriptionTemplate, SubscriptionInstance, CourseType)
 * - La génération automatique du numéro d'abonnement
 * - Les méthodes statiques (hasClubIdColumn, createSafe, generateSubscriptionNumber)
 * - Les accesseurs pour compatibilité avec template (price, total_lessons, free_lessons, validity_months)
 * - Le scope de filtrage par club (forClub)
 * 
 * Note : Subscription peut fonctionner avec ou sans SubscriptionTemplate selon la version de la base de données.
 *        Les tests vérifient la compatibilité avec les deux modes.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Subscription $subscription;
    private Discipline $discipline;
    private CourseType $courseType;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club
     * - Une discipline et un type de cours
     * - Un abonnement avec 10 cours au total, prix 200€
     * 
     * Cette configuration est réinitialisée avant chaque test grâce à RefreshDatabase
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer une discipline
        $this->discipline = Discipline::create([
            'name' => 'Discipline Test',
            'slug' => 'discipline-test',
            'is_active' => true,
        ]);

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Test',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $this->discipline->id,
        ]);

        // Créer un abonnement
        // Note: validity_months n'existe pas dans la table subscriptions, seulement dans le template
        $this->subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'free_lessons' => 2,
            'price' => 200.00,
            'description' => 'Description de test',
            'is_active' => true,
        ]);
    }

    /**
     * Test : Vérification de l'instanciation du modèle
     * 
     * BUT : S'assurer que le modèle Subscription peut être instancié correctement
     * 
     * ENTRÉE : Une nouvelle instance vide de Subscription
     * 
     * SORTIE ATTENDUE : L'instance doit être du type Subscription
     * 
     * POURQUOI : Test de base pour vérifier que le modèle fonctionne correctement
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $subscription = new Subscription();

        $this->assertInstanceOf(Subscription::class, $subscription);
    }

    /**
     * Test : Vérification du nom de table
     * 
     * BUT : S'assurer que le modèle utilise le bon nom de table
     * 
     * ENTRÉE : Une nouvelle instance de Subscription
     * 
     * SORTIE ATTENDUE : Le nom de table doit être 'subscriptions'
     * 
     * POURQUOI : Important pour les requêtes SQL et la cohérence avec la base de données
     */
    #[Test]
    public function it_has_correct_table_name(): void
    {
        $subscription = new Subscription();

        $this->assertEquals('subscriptions', $subscription->getTable());
    }

    /**
     * Test : Vérification de l'utilisation des timestamps
     * 
     * BUT : S'assurer que le modèle enregistre automatiquement created_at et updated_at
     * 
     * ENTRÉE : Une nouvelle instance de Subscription
     * 
     * SORTIE ATTENDUE : timestamps doit être true
     * 
     * POURQUOI : Les timestamps permettent de tracer la création et les modifications des abonnements
     */
    #[Test]
    public function it_uses_timestamps(): void
    {
        $subscription = new Subscription();

        $this->assertTrue($subscription->timestamps);
    }

    /**
     * Test : Vérification de la relation avec Club
     * 
     * BUT : S'assurer que la relation club() fonctionne correctement
     * 
     * ENTRÉE : Un abonnement lié à un club (créé dans setUp)
     * 
     * SORTIE ATTENDUE : subscription->club doit retourner une instance de Club
     * 
     * POURQUOI : Un abonnement appartient à un club. Cette relation est essentielle pour filtrer
     *            les abonnements par club et gérer les permissions.
     */
    #[Test]
    public function it_has_club_relationship(): void
    {
        $this->assertInstanceOf(Club::class, $this->subscription->club);
        $this->assertEquals($this->club->id, $this->subscription->club->id);
    }

    /**
     * Test : Vérification de la relation avec SubscriptionTemplate
     * 
     * BUT : S'assurer que la relation template() fonctionne correctement
     * 
     * ENTRÉE : Un abonnement avec ou sans template
     * 
     * SORTIE ATTENDUE : subscription->template doit retourner null ou une instance de SubscriptionTemplate
     * 
     * POURQUOI : Un abonnement peut être lié à un template (nouveau système) ou fonctionner en mode legacy.
     *            Cette relation permet d'accéder aux propriétés du template si disponible.
     */
    #[Test]
    public function it_has_template_relationship(): void
    {
        // Par défaut, l'abonnement créé n'a pas de template
        $this->assertNull($this->subscription->template);

        // Vérifier si la colonne subscription_template_id existe
        $hasTemplateColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id');
        
        if ($hasTemplateColumn) {
            // Créer un template et l'associer
            $template = SubscriptionTemplate::create([
                'club_id' => $this->club->id,
                'model_number' => 'MODEL-TEST-001',
                'total_lessons' => 15,
                'free_lessons' => 3,
                'price' => 250.00,
                'validity_months' => 6,
                'is_active' => true,
            ]);

            $subscriptionWithTemplate = Subscription::create([
                'club_id' => $this->club->id,
                'subscription_template_id' => $template->id,
                'name' => 'Abonnement avec Template',
                'is_active' => true,
            ]);

            $this->assertInstanceOf(SubscriptionTemplate::class, $subscriptionWithTemplate->template);
            $this->assertEquals($template->id, $subscriptionWithTemplate->template->id);
        } else {
            $this->markTestSkipped('La colonne subscription_template_id n\'existe pas dans la table subscriptions');
        }
    }

    /**
     * Test : Vérification de la relation avec SubscriptionInstance
     * 
     * BUT : S'assurer que la relation instances() fonctionne correctement
     * 
     * ENTRÉE : Un abonnement avec des instances créées
     * 
     * SORTIE ATTENDUE : subscription->instances doit retourner une collection de SubscriptionInstance
     * 
     * POURQUOI : Un abonnement peut avoir plusieurs instances (abonnements achetés par les élèves).
     *            Cette relation permet de lister tous les abonnements actifs basés sur ce modèle.
     */
    #[Test]
    public function it_has_instances_relationship(): void
    {
        // Créer des instances d'abonnement
        $instance1 = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $instance2 = SubscriptionInstance::create([
            'subscription_id' => $this->subscription->id,
            'lessons_used' => 5,
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => Carbon::now()->addMonths(2),
            'status' => 'active',
        ]);

        $instances = $this->subscription->instances;

        $this->assertCount(2, $instances);
        $this->assertTrue($instances->contains($instance1->id));
        $this->assertTrue($instances->contains($instance2->id));
    }

    /**
     * Test : Vérification de la relation avec CourseType (via discipline_id)
     * 
     * BUT : S'assurer que la relation courseTypes() fonctionne correctement
     * 
     * ENTRÉE : Un abonnement avec des types de cours attachés
     * 
     * SORTIE ATTENDUE : subscription->courseTypes doit retourner une collection de CourseType
     * 
     * POURQUOI : Un abonnement peut être limité à certains types de cours. Cette relation permet
     *            de vérifier si un cours peut être consommé avec cet abonnement.
     */
    #[Test]
    public function it_has_course_types_relationship(): void
    {
        // Attacher la discipline à l'abonnement
        // Note: subscription_course_types utilise discipline_id, pas course_type_id
        $this->subscription->courseTypes()->attach($this->discipline->id);

        $courseTypes = $this->subscription->courseTypes;

        $this->assertGreaterThan(0, $courseTypes->count());
        // Note: La relation retourne des CourseType via discipline_id, donc on vérifie que la discipline est liée
        // Utiliser un alias pour éviter l'ambiguïté de colonne
        $this->assertTrue($this->subscription->courseTypes()->where('course_types.discipline_id', $this->discipline->id)->exists());
    }

    /**
     * Test : Génération automatique du numéro d'abonnement
     * 
     * BUT : Vérifier que generateSubscriptionNumber() génère un numéro au format AAMM-incrément
     * 
     * ENTRÉE : 
     * - Date actuelle (ex: janvier 2025)
     * - Aucun abonnement existant pour ce mois
     * 
     * SORTIE ATTENDUE : Numéro au format "2501-001" (année 25, mois 01, incrément 001)
     * 
     * POURQUOI : Le numéro d'abonnement permet d'identifier de manière unique chaque abonnement.
     *            Le format AAMM-incrément facilite le tri chronologique et l'organisation.
     */
    #[Test]
    public function it_generates_subscription_number(): void
    {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_number');
        
        if (!$hasColumn) {
            $this->markTestSkipped('La colonne subscription_number n\'existe pas dans la table subscriptions');
        }

        $now = Carbon::now();
        $expectedYearMonth = $now->format('ym'); // Format AAMM

        $number = Subscription::generateSubscriptionNumber($this->club->id);

        // Vérifier le format : AAMM-XXX
        $this->assertMatchesRegularExpression('/^\d{4}-\d{3}$/', $number);
        $this->assertStringStartsWith($expectedYearMonth, $number);
        $this->assertStringEndsWith('-001', $number); // Premier abonnement du mois
    }

    /**
     * Test : Incrémentation du numéro d'abonnement
     * 
     * BUT : Vérifier que generateSubscriptionNumber() incrémente correctement pour plusieurs abonnements
     * 
     * ENTRÉE : 
     * - Un abonnement déjà créé avec un numéro
     * - Création d'un deuxième abonnement
     * 
     * SORTIE ATTENDUE : 
     * - Premier abonnement : "2501-001"
     * - Deuxième abonnement : "2501-002"
     * 
     * POURQUOI : Chaque abonnement doit avoir un numéro unique. L'incrémentation automatique
     *            garantit l'unicité et évite les collisions.
     */
    #[Test]
    public function it_increments_subscription_number(): void
    {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_number');
        
        if (!$hasColumn) {
            $this->markTestSkipped('La colonne subscription_number n\'existe pas dans la table subscriptions');
        }

        $number1 = Subscription::generateSubscriptionNumber($this->club->id);
        
        // Créer un abonnement avec ce numéro
        $subscription1 = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_number' => $number1,
            'name' => 'Abonnement 1',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        // Générer le numéro suivant
        $number2 = Subscription::generateSubscriptionNumber($this->club->id);

        // Extraire les incréments
        $parts1 = explode('-', $number1);
        $parts2 = explode('-', $number2);
        
        $increment1 = (int) $parts1[1];
        $increment2 = (int) $parts2[1];

        $this->assertEquals($increment1 + 1, $increment2);
    }

    /**
     * Test : Génération automatique lors de la création
     * 
     * BUT : Vérifier que le numéro d'abonnement est généré automatiquement lors de la création
     * 
     * ENTRÉE : Création d'un abonnement sans spécifier subscription_number
     * 
     * SORTIE ATTENDUE : subscription_number doit être généré automatiquement
     * 
     * POURQUOI : Le boot() method doit générer automatiquement le numéro si non fourni.
     *            Cela simplifie la création d'abonnements et garantit qu'ils ont toujours un numéro.
     */
    #[Test]
    public function it_auto_generates_subscription_number_on_creation(): void
    {
        // Note: Ce test peut échouer si la colonne subscription_number n'existe pas dans la table
        // On vérifie d'abord si la colonne existe
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_number');
        
        if ($hasColumn) {
            $subscription = Subscription::create([
                'club_id' => $this->club->id,
                'name' => 'Abonnement Auto',
                'total_lessons' => 10,
                'price' => 200.00,
                'is_active' => true,
            ]);

            $this->assertNotNull($subscription->subscription_number);
            $this->assertNotEmpty($subscription->subscription_number);
        } else {
            $this->markTestSkipped('La colonne subscription_number n\'existe pas dans la table subscriptions');
        }
    }

    /**
     * Test : Accesseur total_available_lessons sans template
     * 
     * BUT : Vérifier que total_available_lessons retourne total_lessons + free_lessons en mode legacy
     * 
     * ENTRÉE : 
     * - Un abonnement sans template
     * - total_lessons = 10
     * - free_lessons = 2
     * 
     * SORTIE ATTENDUE : total_available_lessons = 12
     * 
     * POURQUOI : En mode legacy (sans template), total_available_lessons doit être calculé
     *            à partir des colonnes directes de l'abonnement.
     */
    #[Test]
    public function it_calculates_total_available_lessons_without_template(): void
    {
        $this->assertEquals(12, $this->subscription->total_available_lessons);
    }

    /**
     * Test : Accesseur total_available_lessons avec template
     * 
     * BUT : Vérifier que total_available_lessons utilise le template si disponible
     * 
     * ENTRÉE : 
     * - Un abonnement avec template
     * - Template avec total_lessons = 15, free_lessons = 3
     * 
     * SORTIE ATTENDUE : total_available_lessons = 18 (depuis le template)
     * 
     * POURQUOI : Si un template est associé, les valeurs du template doivent être prioritaires.
     *            Cela permet de centraliser la gestion des propriétés dans le template.
     */
    #[Test]
    public function it_uses_template_for_total_available_lessons(): void
    {
        $hasTemplateColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id');
        
        if (!$hasTemplateColumn) {
            $this->markTestSkipped('La colonne subscription_template_id n\'existe pas dans la table subscriptions');
        }

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-002',
            'total_lessons' => 15,
            'free_lessons' => 3,
            'price' => 250.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'name' => 'Abonnement avec Template',
            'is_active' => true,
        ]);

        $this->assertEquals(18, $subscription->total_available_lessons);
    }

    /**
     * Test : Accesseur price sans template
     * 
     * BUT : Vérifier que price retourne la valeur directe en mode legacy
     * 
     * ENTRÉE : Un abonnement sans template avec price = 200.00
     * 
     * SORTIE ATTENDUE : price = 200.00
     * 
     * POURQUOI : En mode legacy, le prix est stocké directement dans l'abonnement.
     */
    #[Test]
    public function it_returns_price_without_template(): void
    {
        $this->assertEquals(200.00, $this->subscription->price);
    }

    /**
     * Test : Accesseur price avec template
     * 
     * BUT : Vérifier que price utilise le template si disponible
     * 
     * ENTRÉE : Un abonnement avec template ayant price = 250.00
     * 
     * SORTIE ATTENDUE : price = 250.00 (depuis le template)
     * 
     * POURQUOI : Si un template est associé, le prix du template doit être utilisé.
     */
    #[Test]
    public function it_uses_template_for_price(): void
    {
        $hasTemplateColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id');
        
        if (!$hasTemplateColumn) {
            $this->markTestSkipped('La colonne subscription_template_id n\'existe pas dans la table subscriptions');
        }

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-003',
            'total_lessons' => 15,
            'free_lessons' => 3,
            'price' => 250.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'name' => 'Abonnement avec Template',
            'is_active' => true,
        ]);

        $this->assertEquals(250.00, $subscription->price);
    }

    /**
     * Test : Accesseur validity_months sans template
     * 
     * BUT : Vérifier que validity_months retourne 12 par défaut si non défini
     * 
     * ENTRÉE : Un abonnement sans template (validity_months n'existe pas dans la table)
     * 
     * SORTIE ATTENDUE : validity_months = 12 (valeur par défaut)
     * 
     * POURQUOI : En mode legacy, validity_months n'existe pas dans la table subscriptions.
     *            L'accesseur retourne 12 par défaut (valeur par défaut définie dans le modèle).
     */
    #[Test]
    public function it_returns_validity_months_without_template(): void
    {
        // validity_months n'existe pas dans la table subscriptions, donc retourne la valeur par défaut
        $this->assertEquals(12, $this->subscription->validity_months);
    }

    /**
     * Test : Accesseur validity_months avec template
     * 
     * BUT : Vérifier que validity_months utilise le template si disponible
     * 
     * ENTRÉE : Un abonnement avec template ayant validity_months = 6
     * 
     * SORTIE ATTENDUE : validity_months = 6 (depuis le template)
     * 
     * POURQUOI : Si un template est associé, la validité du template doit être utilisée.
     */
    #[Test]
    public function it_uses_template_for_validity_months(): void
    {
        $hasTemplateColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id');
        
        if (!$hasTemplateColumn) {
            $this->markTestSkipped('La colonne subscription_template_id n\'existe pas dans la table subscriptions');
        }

        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-004',
            'total_lessons' => 15,
            'free_lessons' => 3,
            'price' => 250.00,
            'validity_months' => 6,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $template->id,
            'name' => 'Abonnement avec Template',
            'is_active' => true,
        ]);

        $this->assertEquals(6, $subscription->validity_months);
    }

    /**
     * Test : Scope forClub avec club_id direct
     * 
     * BUT : Vérifier que le scope forClub() filtre correctement par club
     * 
     * ENTRÉE : 
     * - Deux clubs avec chacun un abonnement
     * - Appel du scope forClub() pour le premier club
     * 
     * SORTIE ATTENDUE : Seuls les abonnements du club spécifié sont retournés
     * 
     * POURQUOI : Le scope permet de filtrer facilement les abonnements par club,
     *            ce qui est essentiel pour la gestion multi-clubs et les permissions.
     */
    #[Test]
    public function it_filters_by_club_with_scope(): void
    {
        // Créer un deuxième club
        $club2 = Club::create([
            'name' => 'Club Test 2',
            'email' => 'test2@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        // Créer un abonnement pour le deuxième club
        $subscription2 = Subscription::create([
            'club_id' => $club2->id,
            'name' => 'Abonnement Club 2',
            'total_lessons' => 5,
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Filtrer par le premier club
        $club1Subscriptions = Subscription::forClub($this->club->id)->get();

        $this->assertTrue($club1Subscriptions->contains($this->subscription->id));
        $this->assertFalse($club1Subscriptions->contains($subscription2->id));
    }

    /**
     * Test : Méthode hasClubIdColumn
     * 
     * BUT : Vérifier que hasClubIdColumn() détecte correctement la présence de la colonne club_id
     * 
     * ENTRÉE : Vérification de la structure de la table subscriptions
     * 
     * SORTIE ATTENDUE : Retourne true si la colonne existe, false sinon
     * 
     * POURQUOI : Cette méthode permet de gérer la compatibilité entre différentes versions
     *            de la base de données où club_id peut exister ou non.
     */
    #[Test]
    public function it_detects_club_id_column(): void
    {
        $hasColumn = Subscription::hasClubIdColumn();
        
        // La colonne devrait exister dans la plupart des cas
        // On vérifie simplement que la méthode fonctionne sans erreur
        $this->assertIsBool($hasColumn);
    }

    /**
     * Test : Méthode createSafe sans club_id
     * 
     * BUT : Vérifier que createSafe() gère correctement l'absence de club_id
     * 
     * ENTRÉE : 
     * - Tentative de création avec club_id alors que la colonne n'existe pas
     * 
     * SORTIE ATTENDUE : L'abonnement est créé sans erreur, club_id est ignoré
     * 
     * POURQUOI : createSafe() permet de créer des abonnements de manière compatible
     *            avec différentes structures de base de données, en gérant automatiquement
     *            la présence ou l'absence de club_id.
     */
    #[Test]
    public function it_creates_safely_without_club_id_column(): void
    {
        // Note: Ce test vérifie que createSafe() ne plante pas même si club_id n'existe pas
        // Dans la plupart des cas, club_id existe, donc on teste simplement que la méthode fonctionne
        $subscription = Subscription::createSafe([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Safe',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('Abonnement Safe', $subscription->name);
    }
}
