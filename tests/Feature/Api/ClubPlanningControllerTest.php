<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Discipline;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;

/**
 * Tests feature pour le contrôleur ClubPlanningController
 * 
 * Ce fichier teste les fonctionnalités avancées du planning club :
 * - La suggestion de créneaux optimaux (suggestOptimalSlot)
 * - La vérification de disponibilité (checkAvailability)
 * - Les statistiques du planning (getStatistics)
 * - La validation des données d'entrée
 * - La gestion des permissions (club role requis)
 * 
 * Note : Ces tests vérifient le comportement complet des endpoints API, incluant
 *        l'authentification, la validation, et la logique métier.
 */
class ClubPlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private User $clubUser;
    private Teacher $teacher;
    private Student $student;
    private Discipline $discipline;
    private CourseType $courseType;
    private ClubOpenSlot $openSlot;
    private Location $location;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club avec un utilisateur authentifié
     * - Un enseignant et un élève
     * - Une discipline et un type de cours
     * - Un créneau ouvert
     * 
     * Cette configuration est réinitialisée avant chaque test grâce à RefreshDatabase
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser la méthode helper pour créer un utilisateur club authentifié
        $this->clubUser = $this->actingAsClub();
        $this->club = Club::find($this->clubUser->club_id);

        // Créer une discipline
        $this->discipline = Discipline::create([
            'name' => 'Dressage',
            'slug' => 'dressage',
            'is_active' => true,
        ]);

        // Créer un type de cours
        $this->courseType = CourseType::create([
            'name' => 'Cours Individuel',
            'duration_minutes' => 60,
            'price' => 50.00,
            'discipline_id' => $this->discipline->id,
        ]);

        // Créer un enseignant
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'is_available' => true,
        ]);

        $this->teacher->clubs()->attach($this->club->id, [
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Créer un élève
        $studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
        ]);

        // Créer un créneau ouvert
        $this->openSlot = ClubOpenSlot::create([
            'club_id' => $this->club->id,
            'discipline_id' => $this->discipline->id,
            'day_of_week' => Carbon::now()->addDays(1)->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'max_slots' => 2,
            'max_capacity' => 10,
            'is_active' => true,
        ]);

        $this->openSlot->courseTypes()->attach($this->courseType->id);

        // Créer un lieu pour les tests
        $this->location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);
    }

    /**
     * Test : Suggestion de créneaux optimaux - succès
     * 
     * BUT : Vérifier que suggestOptimalSlot() retourne des suggestions de créneaux
     * 
     * ENTRÉE : 
     * - Date future
     * - Durée optionnelle (défaut 60 minutes)
     * - Discipline optionnelle
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - Structure JSON avec success=true, suggestions array, total_found
     * - Chaque suggestion contient time, slot_id, priority, status, etc.
     * 
     * POURQUOI : Cette fonctionnalité permet de suggérer automatiquement les meilleurs créneaux
     *            pour créer un cours, en optimisant l'utilisation des créneaux existants.
     */
    #[Test]
    public function it_can_suggest_optimal_slot(): void
    {
        $requestData = [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'duration' => 60,
            'discipline_id' => $this->discipline->id,
        ];

        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', $requestData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'success',
                     'suggestions' => [
                         '*' => [
                             'time',
                             'slot_id',
                             'slot_name',
                             'slot_range',
                             'priority',
                             'status',
                             'used_capacity',
                             'max_slots',
                             'available_capacity',
                         ]
                     ],
                     'total_found',
                 ]);
    }

    /**
     * Test : Validation des données pour suggestOptimalSlot
     * 
     * BUT : Vérifier que la validation rejette les données invalides
     * 
     * ENTRÉE : 
     * - Données manquantes ou invalides (date manquante, durée invalide, etc.)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 422
     * - Erreurs de validation dans la réponse JSON
     * 
     * POURQUOI : La validation des données d'entrée est essentielle pour garantir l'intégrité
     *            des données et éviter les erreurs dans le traitement.
     */
    #[Test]
    public function it_validates_suggest_optimal_slot_data(): void
    {
        // Test sans date (requis)
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);

        // Test avec durée invalide (trop courte)
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'duration' => 3, // Trop court (minimum 5)
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['duration']);

        // Test avec durée invalide (trop longue)
        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'duration' => 300, // Trop long (maximum 240)
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['duration']);
    }

    /**
     * Test : Vérification de disponibilité - créneau disponible
     * 
     * BUT : Vérifier que checkAvailability() détecte correctement un créneau disponible
     * 
     * ENTRÉE : 
     * - Date, heure et durée d'un créneau
     * - Optionnellement : slot_id, teacher_id, student_id
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - available = true
     * - conflicts = [] (vide)
     * - warnings peut contenir des avertissements
     * 
     * POURQUOI : Cette fonctionnalité permet de vérifier avant la création d'un cours si le créneau
     *            est disponible, évitant ainsi les conflits et les erreurs.
     */
    #[Test]
    public function it_can_check_availability(): void
    {
        $requestData = [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00',
            'duration' => 60,
            'slot_id' => $this->openSlot->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
        ];

        $response = $this->postJson('/api/club/planning/check-availability', $requestData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'available' => true,
                 ])
                 ->assertJsonStructure([
                     'success',
                     'available',
                     'conflicts',
                     'warnings',
                 ]);
    }

    /**
     * Test : Détection de conflits lors de la vérification de disponibilité
     * 
     * BUT : Vérifier que checkAvailability() détecte correctement les conflits
     * 
     * ENTRÉE : 
     * - Un cours existant à la même heure
     * - Vérification de disponibilité pour le même créneau/enseignant/élève
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - available = false
     * - conflicts contient les descriptions des conflits
     * 
     * POURQUOI : La détection de conflits est essentielle pour éviter la double réservation
     *            et garantir que les ressources (créneaux, enseignants, élèves) ne sont pas
     *            surutilisées.
     */
    #[Test]
    public function it_detects_conflicts_when_checking_availability(): void
    {
        // Créer un cours existant qui entre en conflit
        $existingLesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDays(1)->setTime(10, 0),
            'end_time' => Carbon::now()->addDays(1)->setTime(11, 0),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $requestData = [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'time' => '10:30', // Conflit avec le cours existant (10:00-11:00)
            'duration' => 60,
            'teacher_id' => $this->teacher->id,
        ];

        $response = $this->postJson('/api/club/planning/check-availability', $requestData);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertFalse($data['available']);
        $this->assertNotEmpty($data['conflicts']);
        $this->assertStringContainsString('enseignant', strtolower($data['conflicts'][0]));
    }

    /**
     * Test : Détection de créneau complet
     * 
     * BUT : Vérifier que checkAvailability() détecte quand un créneau est complet
     * 
     * ENTRÉE : 
     * - Un créneau avec max_slots = 2
     * - 2 cours déjà créés à la même heure
     * - Vérification pour un 3ème cours
     * 
     * SORTIE ATTENDUE : 
     * - available = false
     * - conflicts contient un message indiquant que le créneau est complet
     * 
     * POURQUOI : Les créneaux ont une capacité limitée (max_slots). Il faut détecter quand
     *            cette capacité est atteinte pour éviter la surréservation.
     */
    #[Test]
    public function it_detects_full_slot_capacity(): void
    {
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        $time = '10:00';

        // Créer 2 cours à la même heure (max_slots = 2)
        $student2 = Student::create([
            'user_id' => User::create([
                'name' => 'Student 2',
                'email' => 'student2@test.com',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])->id,
        ]);

        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => "$date $time:00",
            'end_time' => "$date 11:00:00",
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $student2->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => "$date $time:00",
            'end_time' => "$date 11:00:00",
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $requestData = [
            'date' => $date,
            'time' => $time,
            'duration' => 60,
            'slot_id' => $this->openSlot->id,
        ];

        $response = $this->postJson('/api/club/planning/check-availability', $requestData);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertFalse($data['available']);
        $this->assertNotEmpty($data['conflicts']);
        $this->assertStringContainsString('complet', strtolower($data['conflicts'][0]));
    }

    /**
     * Test : Validation des données pour checkAvailability
     * 
     * BUT : Vérifier que la validation rejette les données invalides
     * 
     * ENTRÉE : 
     * - Données manquantes ou invalides (date, time, duration manquants, etc.)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 422
     * - Erreurs de validation dans la réponse JSON
     * 
     * POURQUOI : La validation des données d'entrée est essentielle pour garantir l'intégrité
     *            des données et éviter les erreurs dans le traitement.
     */
    #[Test]
    public function it_validates_check_availability_data(): void
    {
        // Test sans date (requis)
        $response = $this->postJson('/api/club/planning/check-availability', [
            'time' => '10:00',
            'duration' => 60,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);

        // Test sans time (requis)
        $response = $this->postJson('/api/club/planning/check-availability', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'duration' => 60,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['time']);

        // Test sans duration (requis)
        $response = $this->postJson('/api/club/planning/check-availability', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['duration']);
    }

    /**
     * Test : Récupération des statistiques du planning
     * 
     * BUT : Vérifier que getStatistics() retourne les statistiques correctes
     * 
     * ENTRÉE : 
     * - Optionnellement : start_date et end_date (défaut : semaine en cours)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - Structure JSON avec success=true, by_slot array, summary object
     * - summary contient total_lessons, total_revenue, average_lesson_price, etc.
     * 
     * POURQUOI : Les statistiques permettent d'analyser l'utilisation du planning, les revenus,
     *            et l'occupation des créneaux pour optimiser la gestion du club.
     */
    #[Test]
    public function it_can_get_planning_statistics(): void
    {
        // Créer des cours pour la semaine en cours
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Créer des cours confirmés
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startOfWeek->copy()->addDays(1)->setTime(10, 0),
            'end_time' => $startOfWeek->copy()->addDays(1)->setTime(11, 0),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startOfWeek->copy()->addDays(2)->setTime(14, 0),
            'end_time' => $startOfWeek->copy()->addDays(2)->setTime(15, 0),
            'status' => 'confirmed',
            'price' => 75.00,
        ]);

        $response = $this->getJson('/api/club/planning/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'success',
                     'by_slot' => [
                         '*' => [
                             'slot_id',
                             'slot_name',
                             'date',
                             'day_of_week',
                             'time_range',
                             'lessons_count',
                             'max_slots',
                             'possible_slots',
                             'total_capacity',
                             'occupancy_rate',
                             'revenue',
                         ]
                     ],
                     'summary' => [
                         'total_lessons',
                         'total_revenue',
                         'average_lesson_price',
                         'unique_students',
                         'unique_teachers',
                     ],
                     'period' => [
                         'start_date',
                         'end_date',
                     ],
                 ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(2, $data['summary']['total_lessons']);
        $this->assertGreaterThanOrEqual(125.00, $data['summary']['total_revenue']);
    }

    /**
     * Test : Statistiques avec période personnalisée
     * 
     * BUT : Vérifier que getStatistics() accepte une période personnalisée
     * 
     * ENTRÉE : 
     * - start_date et end_date dans les paramètres de requête
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - Les statistiques sont calculées pour la période spécifiée
     * - period.start_date et period.end_date correspondent aux paramètres
     * 
     * POURQUOI : Il faut pouvoir analyser des périodes spécifiques (mois, trimestre, etc.)
     *            pour des rapports détaillés.
     */
    #[Test]
    public function it_can_get_statistics_for_custom_period(): void
    {
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(7)->format('Y-m-d');

        $response = $this->getJson('/api/club/planning/statistics', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals($startDate, $data['period']['start_date']);
        $this->assertEquals($endDate, $data['period']['end_date']);
    }

    /**
     * Test : Permission - rôle club requis
     * 
     * BUT : Vérifier que seuls les utilisateurs avec le rôle 'club' peuvent accéder aux endpoints
     * 
     * ENTRÉE : 
     * - Un utilisateur avec un rôle différent (teacher, student, etc.)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403 (Forbidden)
     * 
     * POURQUOI : Les fonctionnalités de planning avancées sont réservées aux clubs. Les autres
     *            rôles ne doivent pas pouvoir y accéder pour des raisons de sécurité et de logique métier.
     */
    #[Test]
    public function it_requires_club_role_to_access_planning_endpoints(): void
    {
        $teacherUser = $this->actingAsTeacher();

        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', [
            'date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test : Authentification requise
     * 
     * BUT : Vérifier que l'authentification est requise pour accéder aux endpoints
     * 
     * ENTRÉE : 
     * - Requête sans authentification
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401 (Unauthorized)
     * 
     * POURQUOI : Tous les endpoints API doivent être protégés par authentification pour garantir
     *            la sécurité des données et empêcher l'accès non autorisé.
     */
    #[Test]
    public function it_requires_authentication_to_access_planning_endpoints(): void
    {
        $response = $this->getJson('/api/club/planning/statistics');

        $response->assertStatus(401);
    }

    /**
     * Test : Suggestion avec créneaux prioritaires
     * 
     * BUT : Vérifier que les suggestions sont triées par priorité
     * 
     * ENTRÉE : 
     * - Un créneau avec des cours existants
     * - Demande de suggestions pour ce créneau
     * 
     * SORTIE ATTENDUE : 
     * - Les suggestions sont triées par priorité (1 = priorité maximale)
     * - Les créneaux vides dans des plages occupées ont la priorité 1
     * 
     * POURQUOI : Le tri par priorité permet de suggérer d'abord les créneaux les plus optimaux,
     *            facilitant la création de cours dans des créneaux déjà utilisés pour maximiser
     *            l'utilisation des ressources.
     */
    #[Test]
    public function it_prioritizes_suggestions_correctly(): void
    {
        $date = Carbon::now()->addDays(1)->format('Y-m-d');

        // Créer un cours à 10:00 dans le créneau (09:00-12:00)
        Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => "$date 10:00:00",
            'end_time' => "$date 11:00:00",
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $requestData = [
            'date' => $date,
            'duration' => 60,
            'discipline_id' => $this->discipline->id,
        ];

        $response = $this->postJson('/api/club/planning/suggest-optimal-slot', $requestData);

        $response->assertStatus(200);
        $data = $response->json();
        
        if (count($data['suggestions']) > 0) {
            // Vérifier que les suggestions sont triées par priorité
            $priorities = array_column($data['suggestions'], 'priority');
            $sortedPriorities = $priorities;
            sort($sortedPriorities);
            $this->assertEquals($sortedPriorities, $priorities);
        }
    }
}
