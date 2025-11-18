<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Tests unitaires pour le modèle Lesson
 * 
 * Ce fichier teste les fonctionnalités du modèle Lesson, notamment :
 * - Les nouveaux champs pour les commissions (est_legacy, date_paiement, montant)
 * - Les relations avec les autres modèles
 * - Le casting des types de données
 * - La validation des données
 */
class LessonTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Teacher $teacher;
    private Student $student;
    private CourseType $courseType;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Créer un utilisateur enseignant
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'first_name' => 'Teacher',
            'last_name' => 'User',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'hourly_rate' => 50.00,
            'is_available' => true,
        ]);

        // Créer un utilisateur élève
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
    }

    /**
     * Test : Création d'un cours avec les champs de commission
     * 
     * BUT : Vérifier que les champs est_legacy, date_paiement et montant peuvent être définis
     * 
     * ENTRÉE : Un cours avec est_legacy=false (DCL), date_paiement et montant
     * SORTIE ATTENDUE : Le cours est créé avec tous les champs correctement sauvegardés
     * 
     * POURQUOI : Ces champs sont nécessaires pour le calcul des commissions dans les rapports de paie
     */
    #[Test]
    public function test_can_create_lesson_with_commission_fields(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'status' => 'confirmed',
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'montant' => 50.00,
        ]);

        $this->assertFalse($lesson->est_legacy);
        $this->assertEquals('2025-11-15', $lesson->date_paiement->format('Y-m-d'));
        $this->assertEquals(50.00, $lesson->montant);
    }

    /**
     * Test : Création d'un cours NDCL
     * 
     * BUT : Vérifier qu'un cours peut être marqué comme NDCL (Non Déclaré)
     * 
     * ENTRÉE : Un cours avec est_legacy=true
     * SORTIE ATTENDUE : Le cours est créé avec est_legacy=true
     * 
     * POURQUOI : Permet de distinguer les cours DCL et NDCL pour les commissions
     */
    #[Test]
    public function test_can_create_lesson_with_ndcl_flag(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'status' => 'confirmed',
            'est_legacy' => true, // NDCL
        ]);

        $this->assertTrue($lesson->est_legacy);
    }

    /**
     * Test : Casting des types de données
     * 
     * BUT : Vérifier que est_legacy est casté en boolean, date_paiement en date, montant en decimal
     * 
     * ENTRÉE : Un cours avec les champs définis
     * SORTIE ATTENDUE : Les types sont correctement castés
     * 
     * POURQUOI : Assure la cohérence des types de données dans l'application
     */
    #[Test]
    public function test_casts_commission_fields_correctly(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'status' => 'confirmed',
            'est_legacy' => 1, // Entier au lieu de boolean
            'date_paiement' => '2025-11-15',
            'montant' => '75.50', // String au lieu de float
        ]);

        $this->assertIsBool($lesson->est_legacy);
        $this->assertTrue($lesson->est_legacy);
        $this->assertInstanceOf(Carbon::class, $lesson->date_paiement);
        // Le cast 'decimal:2' retourne une string en SQLite, vérifier que c'est numérique
        $this->assertIsNumeric($lesson->montant);
        $this->assertEquals('75.50', (string)$lesson->montant);
    }

    /**
     * Test : Champs de commission optionnels
     * 
     * BUT : Vérifier que les champs de commission peuvent être null
     * 
     * ENTRÉE : Un cours sans les champs de commission
     * SORTIE ATTENDUE : Le cours est créé avec les champs null
     * 
     * POURQUOI : Les champs de commission sont optionnels pour permettre la création de cours sans ces informations
     */
    #[Test]
    public function test_commission_fields_are_optional(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'status' => 'confirmed',
        ]);

        $this->assertNull($lesson->est_legacy);
        $this->assertNull($lesson->date_paiement);
        $this->assertNull($lesson->montant);
    }

    /**
     * Test : Montant peut différer du prix
     * 
     * BUT : Vérifier que montant peut être différent de price
     * 
     * ENTRÉE : Un cours avec price=50.00 et montant=45.00
     * SORTIE ATTENDUE : Les deux valeurs sont sauvegardées indépendamment
     * 
     * POURQUOI : Le montant réellement payé peut différer du prix du cours (remises, négociations, etc.)
     */
    #[Test]
    public function test_montant_can_differ_from_price(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'montant' => 45.00, // Montant réellement payé différent du prix
            'status' => 'confirmed',
        ]);

        $this->assertEquals(50.00, $lesson->price);
        $this->assertEquals(45.00, $lesson->montant);
    }

    /**
     * Test : Relation avec subscriptionInstances
     * 
     * BUT : Vérifier qu'un cours peut être lié à des abonnements via subscriptionInstances
     * 
     * ENTRÉE : Un cours avec une relation subscriptionInstances
     * SORTIE ATTENDUE : La relation fonctionne correctement
     * 
     * POURQUOI : Un cours peut être consommé depuis un abonnement, mais aussi être un cours individuel (non lié)
     */
    #[Test]
    public function test_has_subscription_instances_relationship(): void
    {
        $lesson = Lesson::create([
            'club_id' => $this->club->id,
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'price' => 50.00,
            'status' => 'confirmed',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $lesson->subscriptionInstances());
    }
}
