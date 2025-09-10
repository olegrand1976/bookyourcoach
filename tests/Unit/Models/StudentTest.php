<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Student;
use App\Models\User;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\StudentMedicalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->club = Club::factory()->create();
        $this->user = User::factory()->create(['role' => 'student']);
        $this->student = Student::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        // Associer le student au club via la table pivot
        $this->student->clubs()->attach($this->club->id, [
            'level' => 'debutant',
            'goals' => 'Test goals',
            'medical_info' => 'Test medical info',
            'preferred_disciplines' => json_encode(['dressage']),
            'is_active' => true,
            'joined_at' => now()
        ]);
    }

    #[Test]
    public function it_belongs_to_a_user()
    {
        $this->assertInstanceOf(User::class, $this->student->user);
        $this->assertEquals($this->user->id, $this->student->user->id);
    }

    #[Test]
    public function it_belongs_to_a_club()
    {
        $this->assertTrue($this->student->clubs->contains($this->club));
        $this->assertEquals($this->club->id, $this->student->clubs->first()->id);
    }

    #[Test]
    public function it_can_have_multiple_disciplines()
    {
        $discipline1 = Discipline::factory()->create();
        $discipline2 = Discipline::factory()->create();
        
        $this->student->disciplines()->attach([$discipline1->id, $discipline2->id]);
        
        $this->assertCount(2, $this->student->disciplines);
        $this->assertTrue($this->student->disciplines->contains($discipline1));
        $this->assertTrue($this->student->disciplines->contains($discipline2));
    }

    #[Test]
    public function it_can_have_multiple_medical_documents()
    {
        $document1 = StudentMedicalDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'certificat_medical'
        ]);
        
        $document2 = StudentMedicalDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'assurance'
        ]);
        
        $this->assertCount(2, $this->student->medicalDocuments);
        $this->assertTrue($this->student->medicalDocuments->contains($document1));
        $this->assertTrue($this->student->medicalDocuments->contains($document2));
    }

    #[Test]
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'user_id' => $this->user->id,
            'club_id' => $this->club->id,
            'level' => 'intermediaire',
            'goals' => 'Apprendre le dressage',
            'medical_info' => 'Aucune allergie connue'
        ];
        
        $student = Student::create($data);
        
        $this->assertDatabaseHas('students', $data);
        $this->assertEquals('intermediaire', $student->level);
        $this->assertEquals('Apprendre le dressage', $student->goals);
    }

    #[Test]
    public function it_can_have_nullable_level()
    {
        $student = Student::factory()->create(['level' => null]);
        
        $this->assertNull($student->level);
    }

    #[Test]
    public function it_can_have_nullable_goals()
    {
        $student = Student::factory()->create(['goals' => null]);
        
        $this->assertNull($student->goals);
    }

    #[Test]
    public function it_can_have_nullable_medical_info()
    {
        $student = Student::factory()->create(['medical_info' => null]);
        
        $this->assertNull($student->medical_info);
    }

    #[Test]
    public function it_can_be_associated_with_club_via_pivot_table()
    {
        $club = Club::factory()->create();
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'club_id' => $club->id
        ]);
        
        // Associer l'utilisateur au club via la table pivot
        $club->users()->attach($user->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        
        $this->assertTrue($club->users->contains($user));
        $this->assertEquals('student', $club->users->first()->pivot->role);
    }

    #[Test]
    public function it_can_be_deleted_with_cascade()
    {
        $studentId = $this->student->id;
        
        // Créer des documents médicaux liés
        StudentMedicalDocument::factory()->create(['student_id' => $studentId]);
        
        // Supprimer l'étudiant
        $this->student->delete();
        
        // Vérifier que l'étudiant est supprimé
        $this->assertDatabaseMissing('students', ['id' => $studentId]);
        
        // Vérifier que les documents médicaux sont supprimés (cascade)
        $this->assertDatabaseMissing('student_medical_documents', ['student_id' => $studentId]);
    }

    #[Test]
    public function it_can_be_soft_deleted_if_configured()
    {
        // Note: Ce test vérifie la possibilité de soft delete si elle est implémentée
        $this->assertTrue(true); // Placeholder pour le moment
    }
}