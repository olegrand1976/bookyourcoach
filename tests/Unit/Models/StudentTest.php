<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use App\Models\Discipline;
use App\Models\SubscriptionInstance;
use App\Models\Location;
use App\Models\Teacher;
use App\Models\CourseType;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour le modèle Student
 * 
 * Ce fichier teste les fonctionnalités principales d'un élève :
 * - Les relations avec d'autres modèles (User, Club, Lesson, SubscriptionInstance, Discipline)
 * - Les attributs JSON (emergency_contacts, preferred_disciplines, etc.)
 * - Les accesseurs calculés (age, total_lessons, total_spent)
 * - Les relations many-to-many (allLessons, subscriptionInstances)
 * 
 * Note : Student représente un élève qui peut être inscrit dans plusieurs clubs
 *        et suivre des cours avec différents enseignants.
 */
class StudentTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private User $user;
    private Student $student;

    /**
     * Configuration initiale pour tous les tests
     * 
     * Crée un environnement de test complet avec :
     * - Un club
     * - Un utilisateur élève
     * - Un élève lié à l'utilisateur et au club
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

        // Créer un utilisateur pour l'élève
        $this->user = User::create([
            'name' => 'Élève Test',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        // Créer un élève
        $this->student = Student::create([
            'user_id' => $this->user->id,
            'date_of_birth' => Carbon::now()->subYears(25),
            'level' => 'intermediaire',
            'goals' => 'Apprendre le dressage',
            'medical_info' => 'Aucune allergie connue',
            'emergency_contacts' => [
                ['name' => 'Parent Test', 'phone' => '0123456789'],
            ],
            'preferred_disciplines' => ['dressage', 'obstacle'],
            'preferred_levels' => ['debutant', 'intermediaire'],
            'preferred_formats' => ['individuel', 'groupe'],
            'notifications_enabled' => true,
        ]);

        // Associer l'élève au club
        $this->student->clubs()->attach($this->club->id, [
            'level' => 'intermediaire',
            'goals' => 'Apprendre le dressage',
            'medical_info' => 'Aucune allergie connue',
            'preferred_disciplines' => json_encode(['dressage']),
            'is_active' => true,
            'joined_at' => now()
        ]);
    }

    /**
     * Test : Vérification de l'instanciation du modèle
     * 
     * BUT : S'assurer que le modèle Student peut être instancié correctement
     * 
     * ENTRÉE : Une nouvelle instance vide de Student
     * 
     * SORTIE ATTENDUE : L'instance doit être du type Student
     * 
     * POURQUOI : Test de base pour vérifier que le modèle fonctionne correctement
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $student = new Student();

        $this->assertInstanceOf(Student::class, $student);
    }

    /**
     * Test : Relation avec User
     * 
     * BUT : S'assurer que la relation user() fonctionne correctement
     * 
     * ENTRÉE : Un élève lié à un utilisateur (créé dans setUp)
     * 
     * SORTIE ATTENDUE : student->user doit retourner une instance de User
     * 
     * POURQUOI : Un élève appartient à un utilisateur. Cette relation est essentielle
     *            pour accéder aux informations de l'utilisateur (nom, email, etc.).
     */
    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $this->assertInstanceOf(User::class, $this->student->user);
        $this->assertEquals($this->user->id, $this->student->user->id);
    }

    /**
     * Test : Relation avec Club
     * 
     * BUT : S'assurer que la relation clubs() fonctionne correctement
     * 
     * ENTRÉE : Un élève lié à un club (créé dans setUp)
     * 
     * SORTIE ATTENDUE : student->clubs doit retourner une collection de Club avec les pivots
     * 
     * POURQUOI : Un élève peut être inscrit dans plusieurs clubs. Cette relation permet
     *            de gérer les inscriptions avec les informations spécifiques (niveau,
     *            objectifs, informations médicales, etc.).
     */
    #[Test]
    public function it_belongs_to_clubs(): void
    {
        $this->assertTrue($this->student->clubs->contains($this->club));
        $this->assertEquals($this->club->id, $this->student->clubs->first()->id);
        $this->assertEquals('intermediaire', $this->student->clubs->first()->pivot->level);
    }

    /**
     * Test : Relation lessons (hasMany)
     * 
     * BUT : S'assurer que la relation lessons() fonctionne correctement (relation legacy)
     * 
     * ENTRÉE : Un élève avec des cours créés (via student_id)
     * 
     * SORTIE ATTENDUE : student->lessons doit retourner une collection de Lesson
     * 
     * POURQUOI : Cette relation legacy permet la compatibilité avec l'ancien système où
     *            un cours avait un seul élève principal.
     */
    #[Test]
    public function it_has_lessons_relationship(): void
    {
        $location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test',
                    'email' => 'teacher@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
            ])->id,
            'student_id' => $this->student->id,
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

        $lessons = $this->student->lessons;

        $this->assertTrue($lessons->contains($lesson));
    }

    /**
     * Test : Relation allLessons (many-to-many)
     * 
     * BUT : S'assurer que la relation allLessons() fonctionne correctement
     * 
     * ENTRÉE : Un élève avec des cours attachés via la table pivot lesson_student
     * 
     * SORTIE ATTENDUE : student->allLessons doit retourner une collection de Lesson avec les pivots
     * 
     * POURQUOI : Cette relation many-to-many permet à un cours d'avoir plusieurs élèves,
     *            ce qui est nécessaire pour les cours de groupe.
     */
    #[Test]
    public function it_has_all_lessons_relationship(): void
    {
        $location = Location::create([
            'name' => 'Lieu Test',
            'address' => '123 Rue Test',
            'city' => 'Test City',
            'postal_code' => '75001',
            'country' => 'France',
        ]);

        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test',
                    'email' => 'teacher@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
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

        $this->student->allLessons()->attach($lesson->id, [
            'status' => 'confirmed',
            'price' => 50.00,
        ]);

        $allLessons = $this->student->allLessons;

        $this->assertTrue($allLessons->contains($lesson));
        $this->assertEquals(50.00, $allLessons->first()->pivot->price);
    }

    /**
     * Test : Relation avec Discipline
     * 
     * BUT : S'assurer que la relation disciplines() fonctionne correctement
     * 
     * ENTRÉE : Un élève avec des disciplines attachées
     * 
     * SORTIE ATTENDUE : student->disciplines doit retourner une collection de Discipline
     * 
     * POURQUOI : Un élève peut suivre des cours dans plusieurs disciplines. Cette relation permet
     *            de définir les disciplines que l'élève pratique.
     */
    #[Test]
    public function it_can_have_multiple_disciplines(): void
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
        
        $this->student->disciplines()->attach([$discipline1->id, $discipline2->id]);
        
        $this->assertCount(2, $this->student->disciplines);
        $this->assertTrue($this->student->disciplines->contains($discipline1));
        $this->assertTrue($this->student->disciplines->contains($discipline2));
    }

    /**
     * Test : Relation avec SubscriptionInstance
     * 
     * BUT : S'assurer que la relation subscriptionInstances() fonctionne correctement
     * 
     * ENTRÉE : Un élève avec des instances d'abonnement attachées
     * 
     * SORTIE ATTENDUE : student->subscriptionInstances doit retourner une collection de SubscriptionInstance
     * 
     * POURQUOI : Un élève peut avoir plusieurs abonnements (actifs ou expirés). Cette relation permet
     *            de lister tous les abonnements de l'élève.
     */
    #[Test]
    public function it_has_subscription_instances_relationship(): void
    {
        $subscription = Subscription::create([
            'club_id' => $this->club->id,
            'name' => 'Abonnement Test',
            'total_lessons' => 10,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMonths(3),
            'status' => 'active',
        ]);

        $this->student->subscriptionInstances()->attach($instance->id);

        $instances = $this->student->subscriptionInstances;

        $this->assertTrue($instances->contains($instance));
    }

    /**
     * Test : Casts - date_of_birth en date
     * 
     * BUT : Vérifier que date_of_birth est casté en date Carbon
     * 
     * ENTRÉE : Un élève avec date_of_birth = "2000-01-01"
     * 
     * SORTIE ATTENDUE : date_of_birth est une instance Carbon
     * 
     * POURQUOI : La date de naissance doit être une instance Carbon pour faciliter les calculs
     *            d'âge et les comparaisons de dates.
     */
    #[Test]
    public function it_casts_date_of_birth_as_date(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->student->date_of_birth);
    }

    /**
     * Test : Casts - emergency_contacts en array
     * 
     * BUT : Vérifier que emergency_contacts est casté en array
     * 
     * ENTRÉE : Un élève avec emergency_contacts = [{'name': 'Parent', 'phone': '0123456789'}]
     * 
     * SORTIE ATTENDUE : emergency_contacts est un array PHP
     * 
     * POURQUOI : Les contacts d'urgence sont stockés en JSON dans la base de données mais doivent
     *            être accessibles comme un array PHP pour faciliter leur manipulation.
     */
    #[Test]
    public function it_casts_emergency_contacts_as_array(): void
    {
        $this->assertIsArray($this->student->emergency_contacts);
        $this->assertArrayHasKey(0, $this->student->emergency_contacts);
        $this->assertEquals('Parent Test', $this->student->emergency_contacts[0]['name']);
    }

    /**
     * Test : Accesseur age
     * 
     * BUT : Vérifier que age calcule correctement l'âge depuis date_of_birth
     * 
     * ENTRÉE : Un élève avec date_of_birth = il y a 25 ans
     * 
     * SORTIE ATTENDUE : age = 25
     * 
     * POURQUOI : L'âge doit être calculé dynamiquement depuis la date de naissance pour rester
     *            à jour automatiquement. C'est utilisé pour l'affichage et les vérifications
     *            d'âge minimum/maximum.
     */
    #[Test]
    public function it_calculates_age_from_date_of_birth(): void
    {
        $this->assertEquals(25, $this->student->age);
    }

    /**
     * Test : Accesseur age sans date_of_birth
     * 
     * BUT : Vérifier que age retourne null si date_of_birth n'est pas défini
     * 
     * ENTRÉE : Un élève sans date_of_birth
     * 
     * SORTIE ATTENDUE : age = null
     * 
     * POURQUOI : Si la date de naissance n'est pas renseignée, l'âge ne peut pas être calculé.
     *            L'accesseur doit retourner null dans ce cas.
     */
    #[Test]
    public function it_returns_null_age_when_no_date_of_birth(): void
    {
        $student = Student::create([
            'user_id' => User::create([
                'name' => 'Student No DOB',
                'email' => 'student-nodob@test.com',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])->id,
        ]);

        $this->assertNull($student->age);
    }

    /**
     * Test : Accesseur total_lessons
     * 
     * BUT : Vérifier que total_lessons compte correctement tous les cours via allLessons
     * 
     * ENTRÉE : Un élève avec plusieurs cours attachés via allLessons
     * 
     * SORTIE ATTENDUE : total_lessons = nombre de cours attachés
     * 
     * POURQUOI : Le nombre total de cours permet de suivre l'activité de l'élève et d'afficher
     *            des statistiques. Il doit être calculé depuis allLessons pour inclure tous
     *            les cours (individuels et de groupe).
     */
    #[Test]
    public function it_calculates_total_lessons(): void
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
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test',
                    'email' => 'teacher@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
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
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test 2',
                    'email' => 'teacher2@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
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

        $this->student->allLessons()->attach($lesson1->id, ['status' => 'confirmed', 'price' => 50.00]);
        $this->student->allLessons()->attach($lesson2->id, ['status' => 'confirmed', 'price' => 50.00]);

        $this->assertEquals(2, $this->student->total_lessons);
    }

    /**
     * Test : Accesseur total_spent
     * 
     * BUT : Vérifier que total_spent calcule correctement le total dépensé depuis les cours
     * 
     * ENTRÉE : Un élève avec plusieurs cours ayant des prix différents
     * 
     * SORTIE ATTENDUE : total_spent = somme des prix des cours
     * 
     * POURQUOI : Le total dépensé permet de suivre les revenus générés par un élève et d'afficher
     *            des statistiques financières. Il doit être calculé depuis les prix des cours
     *            (via pivot ou directement).
     */
    #[Test]
    public function it_calculates_total_spent(): void
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
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test',
                    'email' => 'teacher@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
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
            'teacher_id' => Teacher::create([
                'user_id' => User::create([
                    'name' => 'Teacher Test 2',
                    'email' => 'teacher2@test.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ])->id,
                'is_available' => true,
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
            'price' => 75.00,
        ]);

        $this->student->allLessons()->attach($lesson1->id, ['status' => 'confirmed', 'price' => 50.00]);
        $this->student->allLessons()->attach($lesson2->id, ['status' => 'confirmed', 'price' => 75.00]);

        // Recharger la relation pour avoir les pivots
        $this->student->load('allLessons');

        $this->assertEquals(125.00, $this->student->total_spent);
    }

    /**
     * Test : Création avec tous les attributs
     * 
     * BUT : Vérifier qu'un élève peut être créé avec tous ses attributs
     * 
     * ENTRÉE : Données complètes d'un élève
     * 
     * SORTIE ATTENDUE : L'élève est créé avec toutes les valeurs correctes
     * 
     * POURQUOI : Un élève doit pouvoir être créé avec toutes ses informations pour être complet.
     */
    #[Test]
    public function it_can_be_created_with_all_attributes(): void
    {
        $user = User::create([
            'name' => 'Complete Student',
            'email' => 'complete-student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $data = [
            'user_id' => $user->id,
            'date_of_birth' => Carbon::now()->subYears(20),
            'level' => 'avance',
            'goals' => 'Devenir professionnel',
            'medical_info' => 'Allergie aux chevaux',
            'emergency_contacts' => [
                ['name' => 'Parent 1', 'phone' => '0123456789'],
                ['name' => 'Parent 2', 'phone' => '0987654321'],
            ],
            'preferred_disciplines' => ['dressage', 'obstacle', 'cross'],
            'preferred_levels' => ['intermediaire', 'avance'],
            'preferred_formats' => ['individuel'],
            'notifications_enabled' => true,
        ];
        
        $student = Student::create($data);
        
        $this->assertEquals($data['user_id'], $student->user_id);
        $this->assertEquals($data['date_of_birth']->format('Y-m-d'), $student->date_of_birth->format('Y-m-d'));
        $this->assertEquals($data['level'], $student->level);
        $this->assertEquals($data['goals'], $student->goals);
        $this->assertEquals($data['medical_info'], $student->medical_info);
        $this->assertEquals($data['emergency_contacts'], $student->emergency_contacts);
        $this->assertEquals($data['preferred_disciplines'], $student->preferred_disciplines);
        $this->assertEquals($data['preferred_levels'], $student->preferred_levels);
        $this->assertEquals($data['preferred_formats'], $student->preferred_formats);
        $this->assertEquals($data['notifications_enabled'], $student->notifications_enabled);
    }
}
