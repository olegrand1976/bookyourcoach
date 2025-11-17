<?php

namespace Tests\Unit\Models;

use App\Models\SubscriptionTemplate;
use App\Models\Subscription;
use App\Models\Club;
use App\Models\CourseType;
use App\Models\Discipline;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle SubscriptionTemplate
 * 
 * Ce fichier teste les fonctionnalités principales d'un modèle d'abonnement (template) :
 * - Les relations avec d'autres modèles (Club, CourseType, Subscription)
 * - Les attributs par défaut (free_lessons, validity_months, is_active)
 * - L'accesseur total_available_lessons
 * - La création et la gestion des templates
 * 
 * Note : SubscriptionTemplate représente un modèle d'abonnement défini par un club.
 *        Les abonnements (Subscription) sont créés à partir de ces templates.
 */
class SubscriptionTemplateTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private SubscriptionTemplate $template;
    private Discipline $discipline;
    private CourseType $courseType;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club
     * - Une discipline et un type de cours
     * - Un template d'abonnement avec 10 cours payés, 2 gratuits, prix 200€, validité 3 mois
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

        // Créer un template d'abonnement
        $this->template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-001',
            'total_lessons' => 10,
            'free_lessons' => 2,
            'price' => 200.00,
            'validity_months' => 3,
            'is_active' => true,
        ]);
    }

    /**
     * Test : Vérification de l'instanciation du modèle
     * 
     * BUT : S'assurer que le modèle SubscriptionTemplate peut être instancié correctement
     * 
     * ENTRÉE : Une nouvelle instance vide de SubscriptionTemplate
     * 
     * SORTIE ATTENDUE : L'instance doit être du type SubscriptionTemplate
     * 
     * POURQUOI : Test de base pour vérifier que le modèle fonctionne correctement
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $template = new SubscriptionTemplate();

        $this->assertInstanceOf(SubscriptionTemplate::class, $template);
    }

    /**
     * Test : Vérification du nom de table
     * 
     * BUT : S'assurer que le modèle utilise le bon nom de table
     * 
     * ENTRÉE : Une nouvelle instance de SubscriptionTemplate
     * 
     * SORTIE ATTENDUE : Le nom de table doit être 'subscription_templates'
     * 
     * POURQUOI : Important pour les requêtes SQL et la cohérence avec la base de données
     */
    #[Test]
    public function it_has_correct_table_name(): void
    {
        $template = new SubscriptionTemplate();

        $this->assertEquals('subscription_templates', $template->getTable());
    }

    /**
     * Test : Vérification de l'utilisation des timestamps
     * 
     * BUT : S'assurer que le modèle enregistre automatiquement created_at et updated_at
     * 
     * ENTRÉE : Une nouvelle instance de SubscriptionTemplate
     * 
     * SORTIE ATTENDUE : timestamps doit être true
     * 
     * POURQUOI : Les timestamps permettent de tracer la création et les modifications des templates
     */
    #[Test]
    public function it_uses_timestamps(): void
    {
        $template = new SubscriptionTemplate();

        $this->assertTrue($template->timestamps);
    }

    /**
     * Test : Vérification de la relation avec Club
     * 
     * BUT : S'assurer que la relation club() fonctionne correctement
     * 
     * ENTRÉE : Un template lié à un club (créé dans setUp)
     * 
     * SORTIE ATTENDUE : template->club doit retourner une instance de Club
     * 
     * POURQUOI : Un template appartient à un club. Cette relation est essentielle pour filtrer
     *            les templates par club et gérer les permissions.
     */
    #[Test]
    public function it_has_club_relationship(): void
    {
        $this->assertInstanceOf(Club::class, $this->template->club);
        $this->assertEquals($this->club->id, $this->template->club->id);
    }

    /**
     * Test : Vérification de la relation avec CourseType
     * 
     * BUT : S'assurer que la relation courseTypes() fonctionne correctement
     * 
     * ENTRÉE : Un template avec des types de cours attachés
     * 
     * SORTIE ATTENDUE : template->courseTypes doit retourner une collection de CourseType
     * 
     * POURQUOI : Un template peut être limité à certains types de cours. Cette relation permet
     *            de définir quels types de cours peuvent être consommés avec les abonnements
     *            créés à partir de ce template.
     */
    #[Test]
    public function it_has_course_types_relationship(): void
    {
        // Attacher le type de cours au template
        $this->template->courseTypes()->attach($this->courseType->id);

        $courseTypes = $this->template->courseTypes;

        $this->assertCount(1, $courseTypes);
        $this->assertTrue($courseTypes->contains($this->courseType->id));
    }

    /**
     * Test : Vérification de la relation avec Subscription
     * 
     * BUT : S'assurer que la relation subscriptions() fonctionne correctement
     * 
     * ENTRÉE : Un template avec des abonnements créés à partir de ce template
     * 
     * SORTIE ATTENDUE : template->subscriptions doit retourner une collection de Subscription
     * 
     * POURQUOI : Un template peut avoir plusieurs abonnements créés à partir de lui.
     *            Cette relation permet de lister tous les abonnements basés sur ce template.
     */
    #[Test]
    public function it_has_subscriptions_relationship(): void
    {
        $hasTemplateColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id');
        
        if (!$hasTemplateColumn) {
            $this->markTestSkipped('La colonne subscription_template_id n\'existe pas dans la table subscriptions');
        }

        // Créer des abonnements à partir du template
        $subscription1 = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $this->template->id,
            'name' => 'Abonnement 1',
            'is_active' => true,
        ]);

        $subscription2 = Subscription::create([
            'club_id' => $this->club->id,
            'subscription_template_id' => $this->template->id,
            'name' => 'Abonnement 2',
            'is_active' => true,
        ]);

        $subscriptions = $this->template->subscriptions;

        $this->assertCount(2, $subscriptions);
        $this->assertTrue($subscriptions->contains($subscription1->id));
        $this->assertTrue($subscriptions->contains($subscription2->id));
    }

    /**
     * Test : Attributs par défaut - free_lessons
     * 
     * BUT : Vérifier que free_lessons a une valeur par défaut de 0
     * 
     * ENTRÉE : Création d'un template sans spécifier free_lessons
     * 
     * SORTIE ATTENDUE : free_lessons = 0
     * 
     * POURQUOI : Par défaut, un template n'offre pas de cours gratuits. Cette valeur par défaut
     *            simplifie la création de templates standards.
     */
    #[Test]
    public function it_has_default_free_lessons(): void
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-002',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $this->assertEquals(0, $template->free_lessons);
    }

    /**
     * Test : Attributs par défaut - validity_months
     * 
     * BUT : Vérifier que validity_months a une valeur par défaut de 12
     * 
     * ENTRÉE : Création d'un template sans spécifier validity_months
     * 
     * SORTIE ATTENDUE : validity_months = 12
     * 
     * POURQUOI : Par défaut, un template a une validité de 12 mois (1 an). Cette valeur par défaut
     *            correspond à la durée standard d'un abonnement annuel.
     */
    #[Test]
    public function it_has_default_validity_months(): void
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-003',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $this->assertEquals(12, $template->validity_months);
    }

    /**
     * Test : Attributs par défaut - is_active
     * 
     * BUT : Vérifier que is_active a une valeur par défaut de true
     * 
     * ENTRÉE : Création d'un template sans spécifier is_active
     * 
     * SORTIE ATTENDUE : is_active = true
     * 
     * POURQUOI : Par défaut, un template est actif et peut être utilisé pour créer des abonnements.
     *            Cette valeur par défaut permet de créer des templates directement utilisables.
     */
    #[Test]
    public function it_has_default_is_active(): void
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-004',
            'total_lessons' => 10,
            'price' => 200.00,
        ]);

        $this->assertTrue($template->is_active);
    }

    /**
     * Test : Accesseur total_available_lessons
     * 
     * BUT : Vérifier que total_available_lessons calcule correctement total_lessons + free_lessons
     * 
     * ENTRÉE : 
     * - Un template avec total_lessons = 10
     * - free_lessons = 2
     * 
     * SORTIE ATTENDUE : total_available_lessons = 12
     * 
     * POURQUOI : Le nombre total de cours disponibles est la somme des cours payés et des cours gratuits.
     *            Cet accesseur permet d'obtenir facilement le nombre total sans calcul manuel.
     */
    #[Test]
    public function it_calculates_total_available_lessons(): void
    {
        $this->assertEquals(12, $this->template->total_available_lessons);
    }

    /**
     * Test : Accesseur total_available_lessons avec free_lessons = 0
     * 
     * BUT : Vérifier que total_available_lessons fonctionne même sans cours gratuits
     * 
     * ENTRÉE : 
     * - Un template avec total_lessons = 10
     * - free_lessons = 0
     * 
     * SORTIE ATTENDUE : total_available_lessons = 10
     * 
     * POURQUOI : Même sans cours gratuits, l'accesseur doit fonctionner correctement.
     */
    #[Test]
    public function it_calculates_total_available_lessons_without_free_lessons(): void
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-005',
            'total_lessons' => 10,
            'free_lessons' => 0,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $this->assertEquals(10, $template->total_available_lessons);
    }

    /**
     * Test : Accesseur total_available_lessons dans JSON
     * 
     * BUT : Vérifier que total_available_lessons est inclus dans la sérialisation JSON
     * 
     * ENTRÉE : Un template avec total_lessons = 10, free_lessons = 2
     * 
     * SORTIE ATTENDUE : Le JSON contient total_available_lessons = 12
     * 
     * POURQUOI : L'attribut est dans $appends, donc il doit être automatiquement inclus
     *            lors de la sérialisation JSON. C'est utile pour les API et les réponses JSON.
     */
    #[Test]
    public function it_includes_total_available_lessons_in_json(): void
    {
        $json = $this->template->toJson();
        $data = json_decode($json, true);

        $this->assertArrayHasKey('total_available_lessons', $data);
        $this->assertEquals(12, $data['total_available_lessons']);
    }

    /**
     * Test : Création d'un template avec toutes les propriétés
     * 
     * BUT : Vérifier qu'un template peut être créé avec toutes ses propriétés
     * 
     * ENTRÉE : Création d'un template avec toutes les propriétés remplies
     * 
     * SORTIE ATTENDUE : Le template est créé avec toutes les valeurs correctes
     * 
     * POURQUOI : Un template doit pouvoir être créé avec toutes ses propriétés pour être complet
     *            et utilisable pour créer des abonnements.
     */
    #[Test]
    public function it_can_be_created_with_all_properties(): void
    {
        $template = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-001',
            'total_lessons' => 15,
            'free_lessons' => 3,
            'price' => 250.00,
            'validity_months' => 6,
            'validity_value' => 6,
            'validity_unit' => 'months',
            'is_active' => true,
        ]);

        $this->assertEquals($this->club->id, $template->club_id);
        $this->assertEquals('MODEL-001', $template->model_number);
        $this->assertEquals(15, $template->total_lessons);
        $this->assertEquals(3, $template->free_lessons);
        $this->assertEquals(250.00, $template->price);
        $this->assertEquals(6, $template->validity_months);
        $this->assertEquals(6, $template->validity_value);
        $this->assertEquals('months', $template->validity_unit);
        $this->assertTrue($template->is_active);
    }

    /**
     * Test : Mise à jour d'un template
     * 
     * BUT : Vérifier qu'un template peut être mis à jour
     * 
     * ENTRÉE : 
     * - Un template existant
     * - Modification de certaines propriétés
     * 
     * SORTIE ATTENDUE : Les propriétés sont mises à jour correctement
     * 
     * POURQUOI : Les templates doivent pouvoir être modifiés pour s'adapter aux changements
     *            de tarifs ou de conditions. Les abonnements existants ne sont pas affectés
     *            car ils stockent leurs propres valeurs.
     */
    #[Test]
    public function it_can_be_updated(): void
    {
        $this->template->update([
            'total_lessons' => 15,
            'price' => 250.00,
            'is_active' => false,
        ]);

        $this->template->refresh();

        $this->assertEquals(15, $this->template->total_lessons);
        $this->assertEquals(250.00, $this->template->price);
        $this->assertFalse($this->template->is_active);
    }

    /**
     * Test : Suppression d'un template
     * 
     * BUT : Vérifier qu'un template peut être supprimé
     * 
     * ENTRÉE : Un template existant
     * 
     * SORTIE ATTENDUE : Le template est supprimé de la base de données
     * 
     * POURQUOI : Les templates doivent pouvoir être supprimés s'ils ne sont plus utilisés.
     *            Note : La suppression peut être empêchée par des contraintes de clé étrangère
     *            si des abonnements existent encore basés sur ce template.
     */
    #[Test]
    public function it_can_be_deleted(): void
    {
        $templateId = $this->template->id;
        
        $this->template->delete();

        $this->assertNull(SubscriptionTemplate::find($templateId));
    }

    /**
     * Test : Filtrage des templates actifs
     * 
     * BUT : Vérifier qu'on peut filtrer les templates par statut actif/inactif
     * 
     * ENTRÉE : 
     * - Un template actif
     * - Un template inactif
     * 
     * SORTIE ATTENDUE : Seuls les templates actifs sont retournés
     * 
     * POURQUOI : Il faut pouvoir filtrer les templates actifs pour n'afficher que ceux
     *            disponibles pour créer de nouveaux abonnements.
     */
    #[Test]
    public function it_can_filter_active_templates(): void
    {
        // Créer un template inactif
        $inactiveTemplate = SubscriptionTemplate::create([
            'club_id' => $this->club->id,
            'model_number' => 'MODEL-TEST-006',
            'total_lessons' => 5,
            'price' => 100.00,
            'is_active' => false,
        ]);

        $activeTemplates = SubscriptionTemplate::where('is_active', true)->get();

        $this->assertTrue($activeTemplates->contains($this->template->id));
        $this->assertFalse($activeTemplates->contains($inactiveTemplate->id));
    }

    /**
     * Test : Filtrage des templates par club
     * 
     * BUT : Vérifier qu'on peut filtrer les templates par club
     * 
     * ENTRÉE : 
     * - Deux clubs avec chacun un template
     * - Filtrage par le premier club
     * 
     * SORTIE ATTENDUE : Seuls les templates du club spécifié sont retournés
     * 
     * POURQUOI : Chaque club doit voir uniquement ses propres templates. C'est essentiel
     *            pour la gestion multi-clubs et les permissions.
     */
    #[Test]
    public function it_can_filter_templates_by_club(): void
    {
        // Créer un deuxième club avec un template
        $club2 = Club::create([
            'name' => 'Club Test 2',
            'email' => 'test2@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $template2 = SubscriptionTemplate::create([
            'club_id' => $club2->id,
            'model_number' => 'MODEL-TEST-007',
            'total_lessons' => 5,
            'price' => 100.00,
            'is_active' => true,
        ]);

        $club1Templates = SubscriptionTemplate::where('club_id', $this->club->id)->get();

        $this->assertTrue($club1Templates->contains($this->template->id));
        $this->assertFalse($club1Templates->contains($template2->id));
    }
}

