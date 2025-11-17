<?php

namespace Tests\Unit\Models;

use App\Models\Club;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\RecurringSlot;
use App\Models\CourseType;
use App\Models\Discipline;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle Club
 * 
 * Ce fichier teste les fonctionnalités principales d'un club :
 * - La création et les attributs de base
 * - La construction automatique de l'adresse complète
 * - Les relations avec d'autres modèles (User, Teacher, Student, Subscription, etc.)
 * - Les accesseurs (full_address)
 * - Les scopes de requête (active, byCity)
 * - Les relations actives (activeTeachers, activeStudents)
 * 
 * Note : Un club représente une structure sportive (ex: club équestre) qui peut avoir
 *        plusieurs utilisateurs, enseignants, élèves, abonnements et créneaux récurrents.
 */
class ClubTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private User $user;
    private Teacher $teacher;
    private Student $student;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club avec adresse complète
     * - Un utilisateur, un enseignant et un élève
     * - Les associations nécessaires
     * 
     * Cette configuration est réinitialisée avant chaque test grâce à RefreshDatabase
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Créer un club
        $this->club = Club::create([
            'name' => 'Club Équestre de Paris',
            'description' => 'Un club d\'équitation situé à Paris',
            'street' => 'Rue de la Selle',
            'street_number' => '123',
            'street_box' => 'A',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@club-paris.fr',
            'max_students' => 100,
            'subscription_price' => 150.00,
            'is_active' => true,
        ]);

        // Créer un utilisateur
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'club',
        ]);

        // Créer un enseignant
        $teacherUser = User::create([
            'name' => 'Enseignant Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'is_available' => true,
        ]);

        // Créer un élève
        $studentUser = User::create([
            'name' => 'Élève Test',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
        ]);
    }

    /**
     * Test : Vérification de l'instanciation du modèle
     * 
     * BUT : S'assurer que le modèle Club peut être instancié correctement
     * 
     * ENTRÉE : Une nouvelle instance vide de Club
     * 
     * SORTIE ATTENDUE : L'instance doit être du type Club
     * 
     * POURQUOI : Test de base pour vérifier que le modèle fonctionne correctement
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $club = new Club();

        $this->assertInstanceOf(Club::class, $club);
    }

    /**
     * Test : Création avec tous les champs requis
     * 
     * BUT : Vérifier qu'un club peut être créé avec tous ses attributs
     * 
     * ENTRÉE : Données complètes d'un club (nom, adresse, contact, etc.)
     * 
     * SORTIE ATTENDUE : Le club est créé avec toutes les valeurs correctes
     * 
     * POURQUOI : Un club doit pouvoir être créé avec toutes ses informations pour être complet
     *            et utilisable dans l'application.
     */
    #[Test]
    public function it_can_be_created_with_required_fields(): void
    {
        $clubData = [
            'name' => 'Club Équestre de Lyon',
            'description' => 'Un club d\'équitation situé à Lyon',
            'street' => 'Rue de la Selle',
            'street_number' => '123',
            'city' => 'Lyon',
            'postal_code' => '69000',
            'country' => 'France',
            'phone' => '04 78 90 12 34',
            'email' => 'contact@club-lyon.fr',
            'max_students' => 100,
            'subscription_price' => 150.00,
            'is_active' => true,
        ];

        $club = Club::create($clubData);

        $this->assertInstanceOf(Club::class, $club);
        $this->assertEquals($clubData['name'], $club->name);
        $this->assertEquals($clubData['description'], $club->description);
        $this->assertEquals('Rue de la Selle 123', $club->address); // Construit automatiquement
        $this->assertEquals($clubData['phone'], $club->phone);
        $this->assertEquals($clubData['email'], $club->email);
        $this->assertEquals($clubData['max_students'], $club->max_students);
        $this->assertEquals($clubData['subscription_price'], $club->subscription_price);
        $this->assertTrue($club->is_active);
    }

    /**
     * Test : Construction automatique de l'adresse complète
     * 
     * BUT : Vérifier que l'adresse complète est construite automatiquement lors de la sauvegarde
     * 
     * ENTRÉE : 
     * - street = "Rue de la Selle"
     * - street_number = "123"
     * - street_box = "A"
     * 
     * SORTIE ATTENDUE : address = "Rue de la Selle 123 A"
     * 
     * POURQUOI : Le boot() method construit automatiquement l'adresse complète à partir des
     *            composants individuels. Cela garantit la cohérence des données et simplifie
     *            l'affichage.
     */
    #[Test]
    public function it_automatically_builds_full_address(): void
    {
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'street' => 'Rue Test',
            'street_number' => '456',
            'street_box' => 'B',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'France',
            'is_active' => true,
        ]);

        $this->assertEquals('Rue Test 456 B', $club->address);
    }

    /**
     * Test : Accesseur full_address
     * 
     * BUT : Vérifier que full_address retourne l'adresse complète formatée
     * 
     * ENTRÉE : Un club avec tous les composants d'adresse remplis
     * 
     * SORTIE ATTENDUE : full_address = "Rue de la Selle 123 A, 75001 Paris, France"
     * 
     * POURQUOI : L'accesseur full_address permet d'obtenir facilement l'adresse complète
     *            formatée pour l'affichage dans l'interface utilisateur.
     */
    #[Test]
    public function it_has_full_address_accessor(): void
    {
        $this->assertStringContainsString('Rue de la Selle', $this->club->full_address);
        $this->assertStringContainsString('123', $this->club->full_address);
        $this->assertStringContainsString('75001', $this->club->full_address);
        $this->assertStringContainsString('Paris', $this->club->full_address);
        $this->assertStringContainsString('France', $this->club->full_address);
    }

    /**
     * Test : Relation avec User
     * 
     * BUT : S'assurer que la relation users() fonctionne correctement
     * 
     * ENTRÉE : Un club avec des utilisateurs attachés
     * 
     * SORTIE ATTENDUE : club->users doit retourner une collection de User avec les pivots
     * 
     * POURQUOI : Un club peut avoir plusieurs utilisateurs (propriétaires, administrateurs, etc.).
     *            Cette relation permet de gérer les permissions et les rôles au sein du club.
     */
    #[Test]
    public function it_has_users_relationship(): void
    {
        $this->club->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $this->club->users());
        $this->assertTrue($this->club->users->contains($this->user));
        $this->assertEquals('owner', $this->club->users->first()->pivot->role);
        // is_admin peut être stocké comme entier (0/1) dans la base de données
        $isAdmin = $this->club->users->first()->pivot->is_admin;
        $this->assertTrue($isAdmin === true || $isAdmin === 1 || $isAdmin === '1');
    }

    /**
     * Test : Relation admins
     * 
     * BUT : Vérifier que admins() retourne uniquement les utilisateurs administrateurs
     * 
     * ENTRÉE : Un club avec des utilisateurs admin et non-admin
     * 
     * SORTIE ATTENDUE : Seuls les utilisateurs avec is_admin = true sont retournés
     * 
     * POURQUOI : Les administrateurs ont des permissions spéciales. Cette relation permet
     *            de filtrer facilement les administrateurs du club.
     */
    #[Test]
    public function it_has_admins_relationship(): void
    {
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'club',
        ]);

        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'role' => 'club',
        ]);

        $this->club->users()->attach($adminUser->id, [
            'role' => 'admin',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $this->club->users()->attach($regularUser->id, [
            'role' => 'member',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        // Recharger la relation pour avoir les données à jour
        $this->club->load('users');
        
        $admins = $this->club->admins()->get();

        $this->assertTrue($admins->contains($adminUser));
        $this->assertFalse($admins->contains($regularUser));
    }

    /**
     * Test : Relation avec Teacher
     * 
     * BUT : S'assurer que la relation teachers() fonctionne correctement
     * 
     * ENTRÉE : Un club avec des enseignants attachés
     * 
     * SORTIE ATTENDUE : club->teachers doit retourner une collection de Teacher avec les pivots
     * 
     * POURQUOI : Un club peut avoir plusieurs enseignants. Cette relation permet de gérer
     *            les enseignants affiliés au club avec leurs informations spécifiques
     *            (disciplines autorisées, tarif horaire, etc.).
     */
    #[Test]
    public function it_has_teachers_relationship(): void
    {
        $this->club->teachers()->attach($this->teacher->id, [
            'allowed_disciplines' => json_encode(['dressage', 'obstacle']),
            'restricted_disciplines' => json_encode([]),
            'hourly_rate' => 50.00,
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $this->club->teachers());
        $this->assertTrue($this->club->teachers->contains($this->teacher));
        $this->assertEquals(50.00, $this->club->teachers->first()->pivot->hourly_rate);
    }

    /**
     * Test : Relation activeTeachers
     * 
     * BUT : Vérifier que activeTeachers() retourne uniquement les enseignants actifs
     * 
     * ENTRÉE : Un club avec des enseignants actifs et inactifs
     * 
     * SORTIE ATTENDUE : Seuls les enseignants avec is_active = true sont retournés
     * 
     * POURQUOI : Seuls les enseignants actifs doivent être disponibles pour les cours.
     *            Cette relation permet de filtrer facilement les enseignants disponibles.
     */
    #[Test]
    public function it_has_active_teachers_relationship(): void
    {
        $activeTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Active Teacher',
                'email' => 'active@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'is_available' => true,
        ]);

        $inactiveTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Inactive Teacher',
                'email' => 'inactive@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'is_available' => true,
        ]);

        $this->club->teachers()->attach($activeTeacher->id, [
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->club->teachers()->attach($inactiveTeacher->id, [
            'is_active' => false,
            'joined_at' => now()
        ]);

        $activeTeachers = $this->club->activeTeachers()->get();

        $this->assertTrue($activeTeachers->contains($activeTeacher));
        $this->assertFalse($activeTeachers->contains($inactiveTeacher));
    }

    /**
     * Test : Relation avec Student
     * 
     * BUT : S'assurer que la relation students() fonctionne correctement
     * 
     * ENTRÉE : Un club avec des élèves attachés
     * 
     * SORTIE ATTENDUE : club->students doit retourner une collection de Student avec les pivots
     * 
     * POURQUOI : Un club peut avoir plusieurs élèves. Cette relation permet de gérer
     *            les élèves inscrits au club avec leurs informations spécifiques
     *            (niveau, objectifs, informations médicales, etc.).
     */
    #[Test]
    public function it_has_students_relationship(): void
    {
        $this->club->students()->attach($this->student->id, [
            'level' => 'debutant',
            'goals' => 'Apprendre les bases',
            'medical_info' => null,
            'preferred_disciplines' => json_encode(['dressage']),
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $this->club->students());
        $this->assertTrue($this->club->students->contains($this->student));
        $this->assertEquals('debutant', $this->club->students->first()->pivot->level);
    }

    /**
     * Test : Relation activeStudents
     * 
     * BUT : Vérifier que activeStudents() retourne uniquement les élèves actifs
     * 
     * ENTRÉE : Un club avec des élèves actifs et inactifs
     * 
     * SORTIE ATTENDUE : Seuls les élèves avec is_active = true sont retournés
     * 
     * POURQUOI : Seuls les élèves actifs doivent être disponibles pour les cours.
     *            Cette relation permet de filtrer facilement les élèves disponibles.
     */
    #[Test]
    public function it_has_active_students_relationship(): void
    {
        $activeStudent = Student::create([
            'user_id' => User::create([
                'name' => 'Active Student',
                'email' => 'active-student@test.com',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])->id,
        ]);

        $inactiveStudent = Student::create([
            'user_id' => User::create([
                'name' => 'Inactive Student',
                'email' => 'inactive-student@test.com',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])->id,
        ]);

        $this->club->students()->attach($activeStudent->id, [
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->club->students()->attach($inactiveStudent->id, [
            'is_active' => false,
            'joined_at' => now()
        ]);

        $activeStudents = $this->club->activeStudents()->get();

        $this->assertTrue($activeStudents->contains($activeStudent));
        $this->assertFalse($activeStudents->contains($inactiveStudent));
    }

    /**
     * Test : Relation avec Subscription (via club_id)
     * 
     * BUT : Vérifier que les abonnements peuvent être récupérés via club_id
     * 
     * ENTRÉE : Un club avec des abonnements créés (via club_id)
     * 
     * SORTIE ATTENDUE : Les abonnements peuvent être récupérés via Subscription::where('club_id', ...)
     * 
     * POURQUOI : Un club peut proposer plusieurs modèles d'abonnements. Même si la relation
     *            n'est pas définie dans le modèle Club, les abonnements sont liés via club_id.
     */
    #[Test]
    public function it_can_have_subscriptions(): void
    {
        $hasClubIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'club_id');
        
        if ($hasClubIdColumn) {
            $subscription1 = Subscription::create([
                'club_id' => $this->club->id,
                'name' => 'Abonnement 10 cours',
                'total_lessons' => 10,
                'price' => 200.00,
                'is_active' => true,
            ]);

            $subscription2 = Subscription::create([
                'club_id' => $this->club->id,
                'name' => 'Abonnement 20 cours',
                'total_lessons' => 20,
                'price' => 350.00,
                'is_active' => true,
            ]);

            $subscriptions = Subscription::where('club_id', $this->club->id)->get();

            $this->assertCount(2, $subscriptions);
            $this->assertTrue($subscriptions->contains($subscription1));
            $this->assertTrue($subscriptions->contains($subscription2));
        } else {
            $this->markTestSkipped('La colonne club_id n\'existe pas dans la table subscriptions');
        }
    }

    /**
     * Test : Relation avec RecurringSlot
     * 
     * BUT : S'assurer que la relation recurringSlots() fonctionne correctement
     * 
     * ENTRÉE : Un club avec des créneaux récurrents créés
     * 
     * SORTIE ATTENDUE : club->recurringSlots doit retourner une collection de RecurringSlot
     * 
     * POURQUOI : Un club peut avoir des créneaux récurrents (blocages long terme).
     *            Cette relation permet de lister tous les créneaux récurrents du club.
     */
    #[Test]
    public function it_has_recurring_slots_relationship(): void
    {
        // Note: Ce test nécessite que la table recurring_slots existe
        $hasTable = \Illuminate\Support\Facades\Schema::hasTable('recurring_slots');
        
        if ($hasTable) {
            $recurringSlot = RecurringSlot::create([
                'club_id' => $this->club->id,
                'teacher_id' => $this->teacher->id,
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
            ]);

            $recurringSlots = $this->club->recurringSlots;

            $this->assertTrue($recurringSlots->contains($recurringSlot));
        } else {
            $this->markTestSkipped('La table recurring_slots n\'existe pas');
        }
    }

    /**
     * Test : Casts - subscription_price en decimal
     * 
     * BUT : Vérifier que subscription_price est casté en decimal avec 2 décimales
     * 
     * ENTRÉE : Un club avec subscription_price = 150.50
     * 
     * SORTIE ATTENDUE : subscription_price = "150.50" (string avec 2 décimales)
     * 
     * POURQUOI : Les prix doivent être stockés avec précision décimale pour éviter les erreurs
     *            d'arrondi dans les calculs financiers.
     */
    #[Test]
    public function it_casts_subscription_price_as_decimal(): void
    {
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'subscription_price' => 150.50,
            'is_active' => true,
        ]);

        $this->assertEquals('150.50', $club->subscription_price);
    }

    /**
     * Test : Casts - is_active en boolean
     * 
     * BUT : Vérifier que is_active est casté en boolean
     * 
     * ENTRÉE : Un club avec is_active = true
     * 
     * SORTIE ATTENDUE : is_active est un booléen true
     * 
     * POURQUOI : Le statut actif/inactif doit être un booléen pour faciliter les vérifications
     *            et les filtres dans les requêtes.
     */
    #[Test]
    public function it_casts_is_active_as_boolean(): void
    {
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $this->assertIsBool($club->is_active);
        $this->assertTrue($club->is_active);
    }

    /**
     * Test : Attributs par défaut - country
     * 
     * BUT : Vérifier que country a une valeur par défaut de "France"
     * 
     * ENTRÉE : Création d'un club sans spécifier country
     * 
     * SORTIE ATTENDUE : country = "France"
     * 
     * POURQUOI : Par défaut, les clubs sont en France. Cette valeur par défaut simplifie
     *            la création de clubs français.
     */
    #[Test]
    public function it_has_default_country(): void
    {
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $this->assertEquals('France', $club->country);
    }

    /**
     * Test : Attributs par défaut - is_active
     * 
     * BUT : Vérifier que is_active a une valeur par défaut de true
     * 
     * ENTRÉE : Création d'un club sans spécifier is_active
     * 
     * SORTIE ATTENDUE : is_active = true
     * 
     * POURQUOI : Par défaut, un club est actif et peut être utilisé. Cette valeur par défaut
     *            permet de créer des clubs directement utilisables.
     */
    #[Test]
    public function it_has_default_is_active(): void
    {
        $club = Club::create([
            'name' => 'Club Test',
            'email' => 'test@club.com',
            'phone' => '0123456789',
        ]);

        $this->assertTrue($club->is_active);
    }

    /**
     * Test : Scope active
     * 
     * BUT : Vérifier que le scope active() filtre uniquement les clubs actifs
     * 
     * ENTRÉE : 
     * - Un club actif
     * - Un club inactif
     * 
     * SORTIE ATTENDUE : Seuls les clubs avec is_active = true sont retournés
     * 
     * POURQUOI : Il faut pouvoir filtrer les clubs actifs pour n'afficher que ceux disponibles.
     *            Ce scope simplifie cette opération courante.
     */
    #[Test]
    public function it_can_filter_active_clubs(): void
    {
        $activeClub = Club::create([
            'name' => 'Club Actif',
            'email' => 'actif@club.com',
            'phone' => '0123456789',
            'is_active' => true,
        ]);

        $inactiveClub = Club::create([
            'name' => 'Club Inactif',
            'email' => 'inactif@club.com',
            'phone' => '0123456789',
            'is_active' => false,
        ]);

        $activeClubs = Club::active()->get();

        $this->assertTrue($activeClubs->contains($activeClub));
        $this->assertFalse($activeClubs->contains($inactiveClub));
    }

    /**
     * Test : Scope byCity
     * 
     * BUT : Vérifier que le scope byCity() filtre les clubs par ville
     * 
     * ENTRÉE : 
     * - Deux clubs dans des villes différentes
     * - Filtrage par la première ville
     * 
     * SORTIE ATTENDUE : Seuls les clubs de la ville spécifiée sont retournés
     * 
     * POURQUOI : Il faut pouvoir rechercher des clubs par ville. Ce scope facilite
     *            cette recherche géographique.
     */
    #[Test]
    public function it_can_filter_clubs_by_city(): void
    {
        $parisClub = Club::create([
            'name' => 'Club Paris',
            'email' => 'paris@club.com',
            'phone' => '0123456789',
            'city' => 'Paris',
            'is_active' => true,
        ]);

        $lyonClub = Club::create([
            'name' => 'Club Lyon',
            'email' => 'lyon@club.com',
            'phone' => '0123456789',
            'city' => 'Lyon',
            'is_active' => true,
        ]);

        $parisClubs = Club::byCity('Paris')->get();

        $this->assertTrue($parisClubs->contains($parisClub));
        $this->assertFalse($parisClubs->contains($lyonClub));
    }

    /**
     * Test : Mise à jour d'un club
     * 
     * BUT : Vérifier qu'un club peut être mis à jour
     * 
     * ENTRÉE : 
     * - Un club existant
     * - Modification de certaines propriétés
     * 
     * SORTIE ATTENDUE : Les propriétés sont mises à jour correctement
     * 
     * POURQUOI : Les clubs doivent pouvoir être modifiés pour mettre à jour leurs informations.
     */
    #[Test]
    public function it_can_be_updated(): void
    {
        $this->club->update([
            'name' => 'Club Modifié',
            'max_students' => 150,
            'is_active' => false,
        ]);

        $this->club->refresh();

        $this->assertEquals('Club Modifié', $this->club->name);
        $this->assertEquals(150, $this->club->max_students);
        $this->assertFalse($this->club->is_active);
    }

    /**
     * Test : Suppression d'un club
     * 
     * BUT : Vérifier qu'un club peut être supprimé
     * 
     * ENTRÉE : Un club existant
     * 
     * SORTIE ATTENDUE : Le club est supprimé de la base de données
     * 
     * POURQUOI : Les clubs doivent pouvoir être supprimés s'ils ne sont plus utilisés.
     *            Note : La suppression peut être empêchée par des contraintes de clé étrangère
     *            si des données dépendantes existent encore.
     */
    #[Test]
    public function it_can_be_deleted(): void
    {
        $clubId = $this->club->id;
        
        $this->club->delete();

        $this->assertNull(Club::find($clubId));
    }
}
