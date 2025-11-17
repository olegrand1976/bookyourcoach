<?php

namespace Tests\Unit\Models;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Discipline;
use App\Models\CourseType;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle Teacher
 * 
 * Ce fichier teste les fonctionnalités principales d'un enseignant :
 * - Les relations avec d'autres modèles (User, Club, Lesson, Discipline, CourseType)
 * - Les attributs JSON (specialties, certifications, preferred_locations)
 * - Les méthodes métier (canTeachAt, getContractForClub)
 * - L'accesseur experience_years (calcul automatique)
 * - Les relations actives (activeContracts, activeCourseAssignments)
 * 
 * Note : Teacher représente un enseignant qui peut être affilié à plusieurs clubs
 *        et donner des cours dans différentes disciplines.
 */
class TeacherTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private User $user;
    private Teacher $teacher;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club
     * - Un utilisateur enseignant
     * - Un enseignant lié à l'utilisateur et au club
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

        // Créer un utilisateur pour l'enseignant
        $this->user = User::create([
            'name' => 'Enseignant Test',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'experience_start_date' => Carbon::now()->subYears(5),
        ]);

        // Créer un enseignant
        $this->teacher = Teacher::create([
            'user_id' => $this->user->id,
            'specialties' => ['dressage', 'obstacle'],
            'experience_years' => 5,
            'certifications' => ['BFEE', 'BPJEPS'],
            'hourly_rate' => 50.00,
            'bio' => 'Enseignant expérimenté',
            'is_available' => true,
            'max_travel_distance' => 50,
            'preferred_locations' => ['Paris', 'Lyon'],
            'rating' => 4.5,
            'total_lessons' => 100,
        ]);

        // Associer l'enseignant au club
        $this->teacher->clubs()->attach($this->club->id, [
            'allowed_disciplines' => json_encode(['dressage', 'obstacle']),
            'restricted_disciplines' => json_encode([]),
            'hourly_rate' => 50.00,
            'is_active' => true,
            'joined_at' => now()
        ]);
    }

    /**
     * Test : Vérification de l'instanciation du modèle
     * 
     * BUT : S'assurer que le modèle Teacher peut être instancié correctement
     * 
     * ENTRÉE : Une nouvelle instance vide de Teacher
     * 
     * SORTIE ATTENDUE : L'instance doit être du type Teacher
     * 
     * POURQUOI : Test de base pour vérifier que le modèle fonctionne correctement
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $teacher = new Teacher();

        $this->assertInstanceOf(Teacher::class, $teacher);
    }

    /**
     * Test : Relation avec User
     * 
     * BUT : S'assurer que la relation user() fonctionne correctement
     * 
     * ENTRÉE : Un enseignant lié à un utilisateur (créé dans setUp)
     * 
     * SORTIE ATTENDUE : teacher->user doit retourner une instance de User
     * 
     * POURQUOI : Un enseignant appartient à un utilisateur. Cette relation est essentielle
     *            pour accéder aux informations de l'utilisateur (nom, email, etc.).
     */
    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $this->assertInstanceOf(User::class, $this->teacher->user);
        $this->assertEquals($this->user->id, $this->teacher->user->id);
    }

    /**
     * Test : Relation avec Club
     * 
     * BUT : S'assurer que la relation clubs() fonctionne correctement
     * 
     * ENTRÉE : Un enseignant lié à un club (créé dans setUp)
     * 
     * SORTIE ATTENDUE : teacher->clubs doit retourner une collection de Club avec les pivots
     * 
     * POURQUOI : Un enseignant peut être affilié à plusieurs clubs. Cette relation permet
     *            de gérer les affiliations avec les informations spécifiques (disciplines autorisées,
     *            tarif horaire par club, etc.).
     */
    #[Test]
    public function it_belongs_to_clubs(): void
    {
        $this->assertTrue($this->teacher->clubs->contains($this->club));
        $this->assertEquals($this->club->id, $this->teacher->clubs->first()->id);
        $this->assertEquals(50.00, $this->teacher->clubs->first()->pivot->hourly_rate);
    }

    /**
     * Test : Relation avec Lesson
     * 
     * BUT : S'assurer que la relation lessons() fonctionne correctement
     * 
     * ENTRÉE : Un enseignant avec plusieurs cours créés
     * 
     * SORTIE ATTENDUE : teacher->lessons doit retourner une collection de Lesson
     * 
     * POURQUOI : Un enseignant peut avoir plusieurs cours. Cette relation permet de lister
     *            tous les cours donnés par l'enseignant.
     */
    #[Test]
    public function it_can_have_multiple_lessons(): void
    {
        $location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        $lesson1 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => Student::create([
                'user_id' => User::create([
                    'name' => 'Student Test',
                    'email' => 'student@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'student',
                ])->id,
            ])->id,
            'course_type_id' => CourseType::create([
                'name' => 'Cours Test',
                'duration_minutes' => 60,
                'price' => 50.00,
                'discipline_id' => Discipline::create([
                    'name' => 'Discipline Test',
                    'slug' => 'discipline-test',
                    'is_active' => true,
                ])->id,
            ])->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $lesson2 = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => Student::create([
                'user_id' => User::create([
                    'name' => 'Student Test 2',
                    'email' => 'student2@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'student',
                ])->id,
            ])->id,
            'course_type_id' => CourseType::create([
                'name' => 'Cours Test 2',
                'duration_minutes' => 60,
                'price' => 50.00,
                'discipline_id' => Discipline::create([
                    'name' => 'Discipline Test 2',
                    'slug' => 'discipline-test-2',
                    'is_active' => true,
                ])->id,
            ])->id,
            'location_id' => $location->id,
            'start_time' => Carbon::now()->addDays(2),
            'end_time' => Carbon::now()->addDays(2)->addHour(),
            'status' => 'confirmed',
            'price' => 50.00,
        ]);
        
        $this->assertCount(2, $this->teacher->lessons);
        $this->assertTrue($this->teacher->lessons->contains($lesson1));
        $this->assertTrue($this->teacher->lessons->contains($lesson2));
    }

    /**
     * Test : Casts - specialties en array
     * 
     * BUT : Vérifier que specialties est casté en array
     * 
     * ENTRÉE : Un enseignant avec specialties = ['dressage', 'obstacle']
     * 
     * SORTIE ATTENDUE : specialties est un array PHP
     * 
     * POURQUOI : Les spécialités sont stockées en JSON dans la base de données mais doivent
     *            être accessibles comme un array PHP pour faciliter leur manipulation.
     */
    #[Test]
    public function it_casts_specialties_as_array(): void
    {
        $this->assertIsArray($this->teacher->specialties);
        $this->assertContains('dressage', $this->teacher->specialties);
        $this->assertContains('obstacle', $this->teacher->specialties);
    }

    /**
     * Test : Casts - certifications en array
     * 
     * BUT : Vérifier que certifications est casté en array
     * 
     * ENTRÉE : Un enseignant avec certifications = ['BFEE', 'BPJEPS']
     * 
     * SORTIE ATTENDUE : certifications est un array PHP
     * 
     * POURQUOI : Les certifications sont stockées en JSON dans la base de données mais doivent
     *            être accessibles comme un array PHP pour faciliter leur manipulation.
     */
    #[Test]
    public function it_casts_certifications_as_array(): void
    {
        $this->assertIsArray($this->teacher->certifications);
        $this->assertContains('BFEE', $this->teacher->certifications);
        $this->assertContains('BPJEPS', $this->teacher->certifications);
    }

    /**
     * Test : Casts - hourly_rate en decimal
     * 
     * BUT : Vérifier que hourly_rate est casté en decimal avec 2 décimales
     * 
     * ENTRÉE : Un enseignant avec hourly_rate = 50.00
     * 
     * SORTIE ATTENDUE : hourly_rate = "50.00" (string avec 2 décimales)
     * 
     * POURQUOI : Les tarifs horaires doivent être stockés avec précision décimale pour éviter
     *            les erreurs d'arrondi dans les calculs financiers.
     */
    #[Test]
    public function it_casts_hourly_rate_as_decimal(): void
    {
        $this->assertEquals('50.00', $this->teacher->hourly_rate);
    }

    /**
     * Test : Accesseur experience_years avec experience_start_date
     * 
     * BUT : Vérifier que experience_years est calculé automatiquement depuis experience_start_date
     * 
     * ENTRÉE : 
     * - Un utilisateur avec experience_start_date = il y a 5 ans
     * - Un enseignant lié à cet utilisateur
     * 
     * SORTIE ATTENDUE : experience_years ≈ 5 (calculé depuis experience_start_date)
     * 
     * POURQUOI : Les années d'expérience doivent être calculées dynamiquement depuis la date
     *            de début d'expérience pour rester à jour automatiquement.
     */
    #[Test]
    public function it_calculates_experience_years_from_user_start_date(): void
    {
        // L'utilisateur a experience_start_date = il y a 5 ans
        // L'accesseur doit calculer automatiquement
        $this->assertGreaterThanOrEqual(4, $this->teacher->experience_years);
        $this->assertLessThanOrEqual(6, $this->teacher->experience_years);
    }

    /**
     * Test : Accesseur experience_years sans experience_start_date
     * 
     * BUT : Vérifier que experience_years utilise la valeur stockée si pas de date de début
     * 
     * ENTRÉE : 
     * - Un utilisateur sans experience_start_date
     * - Un enseignant avec experience_years = 3 stocké
     * 
     * SORTIE ATTENDUE : experience_years = 3 (valeur stockée)
     * 
     * POURQUOI : Si l'utilisateur n'a pas de date de début d'expérience, la valeur stockée
     *            dans le profil enseignant doit être utilisée.
     */
    #[Test]
    public function it_uses_stored_experience_years_when_no_start_date(): void
    {
        $user = User::create([
            'name' => 'Teacher No Date',
            'email' => 'teacher-no-date@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            // Pas de experience_start_date
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'experience_years' => 3,
            'is_available' => true,
        ]);

        $this->assertEquals(3, $teacher->experience_years);
    }

    /**
     * Test : Relation avec Discipline
     * 
     * BUT : S'assurer que la relation disciplines() fonctionne correctement
     * 
     * ENTRÉE : Un enseignant avec des disciplines attachées
     * 
     * SORTIE ATTENDUE : teacher->disciplines doit retourner une collection de Discipline
     * 
     * POURQUOI : Un enseignant peut enseigner dans plusieurs disciplines. Cette relation permet
     *            de définir les disciplines que l'enseignant peut enseigner.
     */
    #[Test]
    public function it_has_disciplines_relationship(): void
    {
        $discipline1 = Discipline::create([
            'name' => 'Dressage',
            'slug' => 'dressage',
            'is_active' => true,
        ]);

        $discipline2 = Discipline::create([
            'name' => 'Obstacle',
            'slug' => 'obstacle',
            'is_active' => true,
        ]);

        $this->teacher->disciplines()->attach($discipline1->id, [
            'level' => 'expert',
            'is_primary' => true,
        ]);

        $this->teacher->disciplines()->attach($discipline2->id, [
            'level' => 'avance',
            'is_primary' => false,
        ]);

        $disciplines = $this->teacher->disciplines;

        $this->assertCount(2, $disciplines);
        $this->assertTrue($disciplines->contains($discipline1));
        $this->assertTrue($disciplines->contains($discipline2));
    }

    /**
     * Test : Méthode primaryDiscipline
     * 
     * BUT : Vérifier que primaryDiscipline() retourne la discipline principale
     * 
     * ENTRÉE : Un enseignant avec plusieurs disciplines, dont une marquée comme principale
     * 
     * SORTIE ATTENDUE : primaryDiscipline() retourne la discipline avec is_primary = true
     * 
     * POURQUOI : La discipline principale permet d'identifier la spécialité principale de
     *            l'enseignant pour l'affichage et les recherches.
     */
    #[Test]
    public function it_can_get_primary_discipline(): void
    {
        $primaryDiscipline = Discipline::create([
            'name' => 'Dressage',
            'slug' => 'dressage',
            'is_active' => true,
        ]);

        $secondaryDiscipline = Discipline::create([
            'name' => 'Obstacle',
            'slug' => 'obstacle',
            'is_active' => true,
        ]);

        $this->teacher->disciplines()->attach($primaryDiscipline->id, [
            'level' => 'expert',
            'is_primary' => true,
        ]);

        $this->teacher->disciplines()->attach($secondaryDiscipline->id, [
            'level' => 'avance',
            'is_primary' => false,
        ]);

        $primary = $this->teacher->primaryDiscipline();

        $this->assertNotNull($primary);
        $this->assertEquals($primaryDiscipline->id, $primary->id);
    }

    /**
     * Test : Méthode getContractForClub
     * 
     * BUT : Vérifier que getContractForClub() retourne le contrat actif pour un club
     * 
     * ENTRÉE : 
     * - Un enseignant avec un contrat actif pour un club
     * - Appel de getContractForClub() avec l'ID du club
     * 
     * SORTIE ATTENDUE : Le contrat actif est retourné
     * 
     * POURQUOI : Un enseignant peut avoir plusieurs contrats (un par club). Cette méthode permet
     *            de récupérer facilement le contrat actif pour un club spécifique.
     */
    #[Test]
    public function it_can_get_contract_for_club(): void
    {
        // Note: Ce test nécessite que TeacherContract existe
        $hasTable = \Illuminate\Support\Facades\Schema::hasTable('teacher_contracts');
        
        if ($hasTable) {
            $contract = \App\Models\TeacherContract::create([
                'teacher_id' => $this->teacher->id,
                'club_id' => $this->club->id,
                'is_active' => true,
            ]);

            $foundContract = $this->teacher->getContractForClub($this->club->id);

            $this->assertNotNull($foundContract);
            $this->assertEquals($contract->id, $foundContract->id);
        } else {
            $this->markTestSkipped('La table teacher_contracts n\'existe pas');
        }
    }

    /**
     * Test : Filtrage par disponibilité
     * 
     * BUT : Vérifier qu'on peut filtrer les enseignants par disponibilité
     * 
     * ENTRÉE : 
     * - Un enseignant disponible
     * - Un enseignant indisponible
     * 
     * SORTIE ATTENDUE : Seuls les enseignants avec is_available = true sont retournés
     * 
     * POURQUOI : Il faut pouvoir filtrer les enseignants disponibles pour n'afficher que ceux
     *            qui peuvent donner des cours.
     */
    #[Test]
    public function it_can_be_filtered_by_availability(): void
    {
        $availableTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Available Teacher',
                'email' => 'available@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'is_available' => true,
        ]);

        $unavailableTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Unavailable Teacher',
                'email' => 'unavailable@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'is_available' => false,
        ]);
        
        $availableTeachers = Teacher::where('is_available', true)->get();
        
        $this->assertTrue($availableTeachers->contains($availableTeacher));
        $this->assertFalse($availableTeachers->contains($unavailableTeacher));
    }

    /**
     * Test : Filtrage par spécialisation
     * 
     * BUT : Vérifier qu'on peut filtrer les enseignants par spécialisation
     * 
     * ENTRÉE : 
     * - Un enseignant avec specialties contenant 'dressage'
     * - Un enseignant avec specialties ne contenant pas 'dressage'
     * 
     * SORTIE ATTENDUE : Seuls les enseignants avec la spécialisation spécifiée sont retournés
     * 
     * POURQUOI : Il faut pouvoir rechercher des enseignants par spécialisation pour trouver
     *            les enseignants compétents dans une discipline donnée.
     */
    #[Test]
    public function it_can_be_filtered_by_specialization(): void
    {
        $dressageTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Dressage Teacher',
                'email' => 'dressage@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'specialties' => ['dressage', 'obstacle'],
            'is_available' => true,
        ]);
        
        $obstacleTeacher = Teacher::create([
            'user_id' => User::create([
                'name' => 'Obstacle Teacher',
                'email' => 'obstacle@test.com',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ])->id,
            'specialties' => ['obstacle', 'cross'],
            'is_available' => true,
        ]);
        
        // Rechercher les enseignants de dressage
        $dressageTeachers = Teacher::whereJsonContains('specialties', 'dressage')->get();
        
        $this->assertTrue($dressageTeachers->contains($dressageTeacher));
        $this->assertFalse($dressageTeachers->contains($obstacleTeacher));
    }

    /**
     * Test : Soft deletes
     * 
     * BUT : Vérifier que Teacher utilise SoftDeletes
     * 
     * ENTRÉE : Un enseignant existant
     * 
     * SORTIE ATTENDUE : 
     * - Après delete(), l'enseignant n'apparaît plus dans les requêtes normales
     * - L'enseignant existe toujours dans la base de données avec deleted_at rempli
     * 
     * POURQUOI : Le soft delete permet de conserver les données historiques tout en masquant
     *            les enseignants supprimés des requêtes normales.
     */
    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $teacherId = $this->teacher->id;
        
        $this->teacher->delete();

        // Vérifier que l'enseignant n'apparaît plus dans les requêtes normales
        $this->assertNull(Teacher::find($teacherId));
        
        // Vérifier que l'enseignant existe toujours avec trashed
        $this->assertNotNull(Teacher::withTrashed()->find($teacherId));
    }

    /**
     * Test : Création avec tous les attributs
     * 
     * BUT : Vérifier qu'un enseignant peut être créé avec tous ses attributs
     * 
     * ENTRÉE : Données complètes d'un enseignant
     * 
     * SORTIE ATTENDUE : L'enseignant est créé avec toutes les valeurs correctes
     * 
     * POURQUOI : Un enseignant doit pouvoir être créé avec toutes ses informations pour être complet.
     */
    #[Test]
    public function it_can_be_created_with_all_attributes(): void
    {
        $user = User::create([
            'name' => 'Complete Teacher',
            'email' => 'complete@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $data = [
            'user_id' => $user->id,
            'specialties' => ['dressage', 'obstacle', 'cross'],
            'experience_years' => 10,
            'certifications' => ['BFEE', 'BPJEPS', 'DEJEPS'],
            'hourly_rate' => 60.00,
            'bio' => 'Enseignant très expérimenté',
            'is_available' => true,
            'max_travel_distance' => 100,
            'preferred_locations' => ['Paris', 'Lyon', 'Marseille'],
            'rating' => 4.8,
            'total_lessons' => 500,
        ];
        
        $teacher = Teacher::create($data);
        
        $this->assertEquals($data['user_id'], $teacher->user_id);
        $this->assertEquals($data['specialties'], $teacher->specialties);
        $this->assertEquals($data['experience_years'], $teacher->experience_years);
        $this->assertEquals($data['certifications'], $teacher->certifications);
        $this->assertEquals($data['hourly_rate'], $teacher->hourly_rate);
        $this->assertEquals($data['bio'], $teacher->bio);
        $this->assertEquals($data['is_available'], $teacher->is_available);
        $this->assertEquals($data['max_travel_distance'], $teacher->max_travel_distance);
        $this->assertEquals($data['preferred_locations'], $teacher->preferred_locations);
        $this->assertEquals($data['rating'], $teacher->rating);
        $this->assertEquals($data['total_lessons'], $teacher->total_lessons);
    }
}
