<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Club;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'user_id' => $this->user->id,
            'club_id' => $this->club->id
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $this->assertInstanceOf(User::class, $this->teacher->user);
        $this->assertEquals($this->user->id, $this->teacher->user->id);
    }

    /** @test */
    public function it_belongs_to_a_club()
    {
        $this->assertInstanceOf(Club::class, $this->teacher->club);
        $this->assertEquals($this->club->id, $this->teacher->club->id);
    }

    /** @test */
    public function it_can_have_multiple_lessons()
    {
        $lesson1 = Lesson::factory()->create(['teacher_id' => $this->teacher->id]);
        $lesson2 = Lesson::factory()->create(['teacher_id' => $this->teacher->id]);
        
        $this->assertCount(2, $this->teacher->lessons);
        $this->assertTrue($this->teacher->lessons->contains($lesson1));
        $this->assertTrue($this->teacher->lessons->contains($lesson2));
    }

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'user_id' => $this->user->id,
            'club_id' => $this->club->id,
            'specializations' => ['dressage', 'obstacle'],
            'experience_years' => 5,
            'hourly_rate' => 60.00,
            'bio' => 'Enseignant expérimenté',
            'is_available' => true
        ];
        
        $teacher = Teacher::create($data);
        
        $this->assertDatabaseHas('teachers', $data);
        $this->assertEquals(['dressage', 'obstacle'], $teacher->specializations);
        $this->assertEquals(5, $teacher->experience_years);
        $this->assertEquals(60.00, $teacher->hourly_rate);
    }

    /** @test */
    public function it_can_have_nullable_specializations()
    {
        $teacher = Teacher::factory()->create(['specializations' => null]);
        
        $this->assertNull($teacher->specializations);
    }

    /** @test */
    public function it_can_have_nullable_bio()
    {
        $teacher = Teacher::factory()->create(['bio' => null]);
        
        $this->assertNull($teacher->bio);
    }

    /** @test */
    public function it_can_have_default_values()
    {
        $teacher = Teacher::factory()->create([
            'experience_years' => null,
            'hourly_rate' => null,
            'is_available' => null
        ]);
        
        $this->assertNull($teacher->experience_years);
        $this->assertNull($teacher->hourly_rate);
        $this->assertNull($teacher->is_available);
    }

    /** @test */
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

    /** @test */
    public function it_can_be_deleted_with_cascade()
    {
        $teacherId = $this->teacher->id;
        
        // Créer des leçons liées
        Lesson::factory()->create(['teacher_id' => $teacherId]);
        
        // Supprimer l'enseignant
        $this->teacher->delete();
        
        // Vérifier que l'enseignant est supprimé
        $this->assertDatabaseMissing('teachers', ['id' => $teacherId]);
        
        // Vérifier que les leçons sont supprimées (cascade)
        $this->assertDatabaseMissing('lessons', ['teacher_id' => $teacherId]);
    }

    /** @test */
    public function it_can_calculate_total_lessons()
    {
        Lesson::factory()->count(3)->create(['teacher_id' => $this->teacher->id]);
        
        $this->assertEquals(3, $this->teacher->lessons->count());
    }

    /** @test */
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

    /** @test */
    public function it_can_be_filtered_by_availability()
    {
        $availableTeacher = Teacher::factory()->create(['is_available' => true]);
        $unavailableTeacher = Teacher::factory()->create(['is_available' => false]);
        
        $availableTeachers = Teacher::where('is_available', true)->get();
        
        $this->assertTrue($availableTeachers->contains($availableTeacher));
        $this->assertFalse($availableTeachers->contains($unavailableTeacher));
    }

    /** @test */
    public function it_can_be_filtered_by_specialization()
    {
        $dressageTeacher = Teacher::factory()->create([
            'specializations' => ['dressage', 'obstacle']
        ]);
        
        $obstacleTeacher = Teacher::factory()->create([
            'specializations' => ['obstacle', 'cross']
        ]);
        
        // Rechercher les enseignants de dressage
        $dressageTeachers = Teacher::whereJsonContains('specializations', 'dressage')->get();
        
        $this->assertTrue($dressageTeachers->contains($dressageTeacher));
        $this->assertFalse($dressageTeachers->contains($obstacleTeacher));
    }
}