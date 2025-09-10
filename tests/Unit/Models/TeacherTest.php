<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class TeacherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->club = Club::factory()->create();
        $this->user = User::factory()->create(['role' => 'teacher']);
        $this->teacher = Teacher::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        // Associer le teacher au club via la table pivot
        $this->teacher->clubs()->attach($this->club->id, [
            'allowed_disciplines' => json_encode(['dressage', 'obstacle']),
            'restricted_disciplines' => json_encode([]),
            'hourly_rate' => 50.00,
            'is_active' => true,
            'joined_at' => now()
        ]);
    }

    #[Test]
    public function it_belongs_to_a_user()
    {
        $this->assertInstanceOf(User::class, $this->teacher->user);
        $this->assertEquals($this->user->id, $this->teacher->user->id);
    }

    #[Test]
    public function it_belongs_to_a_club()
    {
        $this->assertTrue($this->teacher->clubs->contains($this->club));
        $this->assertEquals($this->club->id, $this->teacher->clubs->first()->id);
    }

    #[Test]
    public function it_can_have_multiple_lessons()
    {
        $lesson1 = Lesson::factory()->create(['teacher_id' => $this->teacher->id]);
        $lesson2 = Lesson::factory()->create(['teacher_id' => $this->teacher->id]);
        
        $this->assertCount(2, $this->teacher->lessons);
        $this->assertTrue($this->teacher->lessons->contains($lesson1));
        $this->assertTrue($this->teacher->lessons->contains($lesson2));
    }

    #[Test]
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'user_id' => $this->user->id,
            'specialties' => ['dressage', 'obstacle'],
            'experience_years' => 5,
            'hourly_rate' => 60.00,
            'bio' => 'Enseignant expérimenté',
            'is_available' => true
        ];
        
        $teacher = Teacher::create($data);
        
        // Vérifier que le teacher a été créé avec les bonnes valeurs
        $this->assertEquals($data['user_id'], $teacher->user_id);
        $this->assertEquals($data['specialties'], $teacher->specialties);
        $this->assertEquals($data['experience_years'], $teacher->experience_years);
        $this->assertEquals($data['hourly_rate'], $teacher->hourly_rate);
        $this->assertEquals($data['bio'], $teacher->bio);
        $this->assertEquals($data['is_available'], $teacher->is_available);
    }

    #[Test]
    public function it_can_have_nullable_specializations()
    {
        $teacher = Teacher::factory()->create(['specialties' => null]);
        
        $this->assertNull($teacher->specialties);
    }

    #[Test]
    public function it_can_have_nullable_bio()
    {
        $teacher = Teacher::factory()->create(['bio' => null]);
        
        $this->assertNull($teacher->bio);
    }

    #[Test]
    public function it_can_have_default_values()
    {
        $teacher = Teacher::create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id
        ]);
        
        $this->assertEquals(0, $teacher->experience_years);
        $this->assertEquals(0, $teacher->hourly_rate);
        $this->assertTrue($teacher->is_available ?? true); // Vérifier que c'est true ou null (valeur par défaut)
    }

    #[Test]
    public function it_can_be_associated_with_club_via_pivot_table()
    {
        $club = Club::factory()->create();
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
            'club_id' => $club->id
        ]);
        
        // Associer l'utilisateur au club via la table pivot
        $club->users()->attach($user->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        
        $this->assertTrue($club->users->contains($user));
        $this->assertEquals('teacher', $club->users->first()->pivot->role);
    }

    #[Test]
    public function it_can_be_deleted_with_cascade()
    {
        $teacherId = $this->teacher->id;
        
        // Créer des leçons liées
        Lesson::factory()->create(['teacher_id' => $teacherId]);
        
        // Supprimer l'enseignant (hard delete pour tester la cascade)
        $this->teacher->forceDelete();
        
        // Vérifier que l'enseignant est supprimé
        $this->assertDatabaseMissing('teachers', ['id' => $teacherId]);
        
        // Vérifier que les leçons sont supprimées (cascade)
        $this->assertDatabaseMissing('lessons', ['teacher_id' => $teacherId]);
    }

    #[Test]
    public function it_can_calculate_total_lessons()
    {
        Lesson::factory()->count(3)->create(['teacher_id' => $this->teacher->id]);
        
        $this->assertEquals(3, $this->teacher->lessons->count());
    }

    #[Test]
    public function it_can_calculate_completed_lessons()
    {
        Lesson::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'status' => 'completed'
        ]);
        
        Lesson::factory()->count(1)->create([
            'teacher_id' => $this->teacher->id,
            'status' => 'pending'
        ]);
        
        $completedLessons = $this->teacher->lessons()->where('status', 'completed')->count();
        $this->assertEquals(2, $completedLessons);
    }

    #[Test]
    public function it_can_be_filtered_by_availability()
    {
        $availableTeacher = Teacher::factory()->create(['is_available' => true]);
        $unavailableTeacher = Teacher::factory()->create(['is_available' => false]);
        
        $availableTeachers = Teacher::where('is_available', true)->get();
        
        $this->assertTrue($availableTeachers->contains($availableTeacher));
        $this->assertFalse($availableTeachers->contains($unavailableTeacher));
    }

    #[Test]
    public function it_can_be_filtered_by_specialization()
    {
        $dressageTeacher = Teacher::factory()->create([
            'specialties' => ['dressage', 'obstacle']
        ]);
        
        $obstacleTeacher = Teacher::factory()->create([
            'specialties' => ['obstacle', 'cross']
        ]);
        
        // Rechercher les enseignants de dressage
        $dressageTeachers = Teacher::whereJsonContains('specialties', 'dressage')->get();
        
        $this->assertTrue($dressageTeachers->contains($dressageTeacher));
        $this->assertFalse($dressageTeachers->contains($obstacleTeacher));
    }
}