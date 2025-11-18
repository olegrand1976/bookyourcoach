<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

/**
 * Tests d'intégration pour les champs de commission dans LessonController
 * 
 * Ce fichier teste que les champs est_legacy, date_paiement et montant
 * sont correctement validés et sauvegardés lors de la création de cours.
 */
class LessonControllerCommissionFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $clubUser;
    private Club $club;
    private Teacher $teacher;
    private Student $student;
    private CourseType $courseType;
    private Location $location;

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

        // Créer un enseignant
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

        $this->teacher->clubs()->attach($this->club->id);

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
     * Test : Création d'un cours avec les champs de commission DCL
     * 
     * BUT : Vérifier qu'un cours peut être créé avec est_legacy=false (DCL)
     * 
     * ENTRÉE : Requête POST avec est_legacy=false, date_paiement et montant
     * SORTIE ATTENDUE : Le cours est créé avec tous les champs correctement sauvegardés
     * 
     * POURQUOI : Permet de créer des cours avec classification DCL pour les commissions
     */
    #[Test]
    public function test_can_create_lesson_with_dcl_fields(): void
    {
        Sanctum::actingAs($this->clubUser);

        $startTime = Carbon::now()->addDay();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
            'est_legacy' => false, // DCL
            'date_paiement' => '2025-11-15',
            'montant' => 50.00,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'est_legacy',
                'date_paiement',
                'montant',
            ],
        ]);

        $lesson = Lesson::find($response->json('data.id'));
        $this->assertFalse($lesson->est_legacy);
        $this->assertEquals('2025-11-15', $lesson->date_paiement->format('Y-m-d'));
        $this->assertEquals(50.00, $lesson->montant);
    }

    /**
     * Test : Création d'un cours avec les champs de commission NDCL
     * 
     * BUT : Vérifier qu'un cours peut être créé avec est_legacy=true (NDCL)
     * 
     * ENTRÉE : Requête POST avec est_legacy=true
     * SORTIE ATTENDUE : Le cours est créé avec est_legacy=true
     * 
     * POURQUOI : Permet de créer des cours avec classification NDCL pour les commissions
     */
    #[Test]
    public function test_can_create_lesson_with_ndcl_fields(): void
    {
        Sanctum::actingAs($this->clubUser);

        $startTime = Carbon::now()->addDay();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
            'est_legacy' => true, // NDCL
            'date_paiement' => '2025-11-15',
            'montant' => 50.00,
        ]);

        $response->assertStatus(201);

        $lesson = Lesson::find($response->json('data.id'));
        $this->assertTrue($lesson->est_legacy);
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

        $startTime = Carbon::now()->addDay();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
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

        $startTime = Carbon::now()->addDay();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
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

        $startTime = Carbon::now()->addDay();

        // Test avec montant invalide
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
            'montant' => 'invalid', // Doit être un nombre
        ]);

        $response->assertStatus(422);

        // Test avec montant négatif
        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
            'montant' => -10, // Doit être positif
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test : Champs de commission optionnels
     * 
     * BUT : Vérifier qu'un cours peut être créé sans les champs de commission
     * 
     * ENTRÉE : Requête POST sans est_legacy, date_paiement ni montant
     * SORTIE ATTENDUE : Le cours est créé avec succès
     * 
     * POURQUOI : Les champs de commission sont optionnels
     */
    #[Test]
    public function test_commission_fields_are_optional(): void
    {
        Sanctum::actingAs($this->clubUser);

        $startTime = Carbon::now()->addDay();

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => $startTime->toISOString(),
            'duration' => 60,
            'price' => 50.00,
            // Pas de champs de commission
        ]);

        $response->assertStatus(201);

        $lesson = Lesson::find($response->json('data.id'));
        $this->assertNotNull($lesson);
    }
}

