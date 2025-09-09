<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->user = User::factory()->create([
            'email' => 'club@bookyourcoach.com',
            'role' => 'club'
        ]);
        
        $this->club = Club::factory()->create();
        $this->user->clubs()->attach($this->club->id, [
            'role' => 'admin',
            'is_admin' => true,
            'joined_at' => now()
        ]);
    }

    /** @test */
    public function it_can_create_a_student_via_api()
    {
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'level' => 'debutant',
            'goals' => 'Apprendre le dressage',
            'medical_info' => 'Aucune allergie'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'student' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role'
                    ],
                    'studentProfile' => [
                        'id',
                        'user_id',
                        'level',
                        'goals',
                        'medical_info'
                    ]
                ]);
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'role' => 'student'
        ]);
        
        $this->assertDatabaseHas('students', [
            'level' => 'debutant',
            'goals' => 'Apprendre le dressage'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/club/students-test', []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'existing@example.com']);
        
        $studentData = [
            'name' => 'Test Student',
            'email' => 'existing@example.com'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_create_student_with_optional_fields()
    {
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'level' => 'intermediaire',
            'goals' => 'Perfectionner le dressage',
            'medical_info' => 'Allergie aux chevaux'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('students', [
            'level' => 'intermediaire',
            'goals' => 'Perfectionner le dressage',
            'medical_info' => 'Allergie aux chevaux'
        ]);
    }

    /** @test */
    public function it_can_create_student_with_nullable_fields()
    {
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'level' => '',
            'goals' => '',
            'medical_info' => ''
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('students', [
            'level' => null,
            'goals' => null,
            'medical_info' => null
        ]);
    }

    /** @test */
    public function it_associates_student_with_club()
    {
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(201);
        
        // Vérifier que l'étudiant est associé au club
        $student = Student::where('club_id', $this->club->id)->first();
        $this->assertNotNull($student);
        
        // Vérifier que l'utilisateur est associé au club via la table pivot
        $this->assertTrue($this->club->users->contains($student->user));
    }

    /** @test */
    public function it_can_upload_medical_documents()
    {
        Storage::fake('public');
        
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'club_id' => $this->club->id
        ]);
        
        $file = UploadedFile::fake()->create('medical_cert.pdf', 100);
        
        $documentData = [
            'documents' => [
                [
                    'file' => $file,
                    'document_type' => 'certificat_medical',
                    'expiry_date' => '2025-12-31',
                    'renewal_frequency' => 'yearly',
                    'notes' => 'Certificat médical valide'
                ]
            ]
        ];
        
        $response = $this->postJson("/api/club/students/{$student->id}/medical-documents", $documentData);
        
        $response->assertStatus(201);
        
        // Vérifier que le fichier est stocké
        Storage::disk('public')->assertExists('medical_documents/' . $file->hashName());
        
        // Vérifier que le document est enregistré en base
        $this->assertDatabaseHas('student_medical_documents', [
            'student_id' => $student->id,
            'document_type' => 'certificat_medical',
            'renewal_frequency' => 'yearly'
        ]);
    }

    /** @test */
    public function it_can_list_students()
    {
        // Créer quelques étudiants
        $student1 = Student::factory()->create(['club_id' => $this->club->id]);
        $student2 = Student::factory()->create(['club_id' => $this->club->id]);
        
        $response = $this->getJson('/api/club/students-list');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'students' => [
                        '*' => [
                            'student_id',
                            'user_id',
                            'name',
                            'email'
                        ]
                    ]
                ]);
        
        $this->assertCount(2, $response->json('students'));
    }

    /** @test */
    public function it_returns_error_when_user_not_found()
    {
        // Supprimer l'utilisateur club
        $this->user->delete();
        
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'User not found']);
    }

    /** @test */
    public function it_returns_error_when_club_not_found()
    {
        // Supprimer le club
        $this->club->delete();
        
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Club not found']);
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Mock une erreur de base de données
        $this->mock(\Illuminate\Database\DatabaseManager::class, function ($mock) {
            $mock->shouldReceive('connection')->andThrow(new \Exception('Database error'));
        });
        
        $studentData = [
            'name' => 'Test Student',
            'email' => 'test@example.com'
        ];
        
        $response = $this->postJson('/api/club/students-test', $studentData);
        
        $response->assertStatus(500)
                ->assertJsonStructure([
                    'error',
                    'message',
                    'trace'
                ]);
    }
}
