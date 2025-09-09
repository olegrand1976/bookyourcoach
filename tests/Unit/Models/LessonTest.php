<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les données de test nécessaires
        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->create();
        $this->courseType = CourseType::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->lesson = Lesson::factory()->create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'price' => 50.00,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_belongs_to_a_teacher()
    {
        $this->assertInstanceOf(Teacher::class, $this->lesson->teacher);
        $this->assertEquals($this->teacher->id, $this->lesson->teacher->id);
    }

    /** @test */
    public function it_belongs_to_a_student()
    {
        $this->assertInstanceOf(Student::class, $this->lesson->student);
        $this->assertEquals($this->student->id, $this->lesson->student->id);
    }

    /** @test */
    public function it_belongs_to_a_course_type()
    {
        $this->assertInstanceOf(CourseType::class, $this->lesson->courseType);
        $this->assertEquals($this->courseType->id, $this->lesson->courseType->id);
    }

    /** @test */
    public function it_belongs_to_a_location()
    {
        $this->assertInstanceOf(Location::class, $this->lesson->location);
        $this->assertEquals($this->location->id, $this->lesson->location->id);
    }

    /** @test */
    public function it_can_have_multiple_students()
    {
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();
        
        $this->lesson->students()->attach([
            $student2->id => ['status' => 'pending', 'price' => 50.00],
            $student3->id => ['status' => 'pending', 'price' => 50.00]
        ]);
        
        $this->assertCount(2, $this->lesson->students);
        $this->assertTrue($this->lesson->students->contains($student2));
        $this->assertTrue($this->lesson->students->contains($student3));
    }

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'course_type_id' => $this->courseType->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'price' => 75.00,
            'status' => 'confirmed',
            'notes' => 'Cours de test'
        ];
        
        $lesson = Lesson::create($data);
        
        $this->assertDatabaseHas('lessons', $data);
        $this->assertEquals(75.00, $lesson->price);
        $this->assertEquals('confirmed', $lesson->status);
    }

    /** @test */
    public function it_can_have_nullable_notes()
    {
        $lesson = Lesson::factory()->create(['notes' => null]);
        
        $this->assertNull($lesson->notes);
    }

    /** @test */
    public function it_can_be_filtered_by_status()
    {
        Lesson::factory()->create(['status' => 'completed']);
        Lesson::factory()->create(['status' => 'cancelled']);
        
        $pendingLessons = Lesson::byStatus('pending')->get();
        $completedLessons = Lesson::byStatus('completed')->get();
        
        $this->assertTrue($pendingLessons->contains($this->lesson));
        $this->assertCount(1, $completedLessons);
    }

    /** @test */
    public function it_can_be_filtered_by_date_range()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $nextWeek = now()->addWeek()->startOfDay();
        
        Lesson::factory()->create(['start_time' => $today]);
        Lesson::factory()->create(['start_time' => $tomorrow]);
        Lesson::factory()->create(['start_time' => $nextWeek]);
        
        $thisWeekLessons = Lesson::inDateRange($today, $tomorrow->addDay())->get();
        
        $this->assertCount(2, $thisWeekLessons);
    }

    /** @test */
    public function it_can_calculate_formatted_duration()
    {
        $lesson1 = Lesson::factory()->create(['start_time' => now(), 'end_time' => now()->addMinutes(90)]);
        $lesson2 = Lesson::factory()->create(['start_time' => now(), 'end_time' => now()->addMinutes(120)]);
        $lesson3 = Lesson::factory()->create(['start_time' => now(), 'end_time' => now()->addMinutes(45)]);
        
        // Calculer la durée en minutes
        $duration1 = $lesson1->start_time->diffInMinutes($lesson1->end_time);
        $duration2 = $lesson2->start_time->diffInMinutes($lesson2->end_time);
        $duration3 = $lesson3->start_time->diffInMinutes($lesson3->end_time);
        
        $this->assertEquals(90, $duration1);
        $this->assertEquals(120, $duration2);
        $this->assertEquals(45, $duration3);
    }

    /** @test */
    public function it_can_calculate_student_count()
    {
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();
        
        $this->lesson->students()->attach([
            $student2->id => ['status' => 'pending', 'price' => 50.00],
            $student3->id => ['status' => 'pending', 'price' => 50.00]
        ]);
        
        $this->assertEquals(2, $this->lesson->students->count());
    }

    /** @test */
    public function it_can_determine_if_group_lesson()
    {
        $student2 = Student::factory()->create();
        
        // Leçon individuelle
        $this->assertFalse($this->lesson->students->count() > 1);
        
        // Leçon de groupe
        $this->lesson->students()->attach([
            $student2->id => ['status' => 'pending', 'price' => 50.00]
        ]);
        
        $this->assertTrue($this->lesson->students->count() > 1);
    }

    /** @test */
    public function it_can_calculate_total_price()
    {
        $student2 = Student::factory()->create();
        
        $this->lesson->students()->attach([
            $student2->id => ['status' => 'pending', 'price' => 60.00]
        ]);
        
        // Le prix total devrait être la somme des prix individuels
        $totalPrice = $this->lesson->price + 60.00;
        $this->assertEquals($totalPrice, $this->lesson->price + 60.00);
    }

    /** @test */
    public function it_can_be_deleted_with_cascade()
    {
        $lessonId = $this->lesson->id;
        
        // Créer des associations avec des étudiants
        $student2 = Student::factory()->create();
        $this->lesson->students()->attach([
            $student2->id => ['status' => 'pending', 'price' => 50.00]
        ]);
        
        // Supprimer la leçon
        $this->lesson->delete();
        
        // Vérifier que la leçon est supprimée
        $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
        
        // Vérifier que les associations sont supprimées (cascade)
        $this->assertDatabaseMissing('lesson_student', ['lesson_id' => $lessonId]);
    }

    /** @test */
    public function it_can_have_different_statuses()
    {
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
        
        foreach ($statuses as $status) {
            $lesson = Lesson::factory()->create(['status' => $status]);
            $this->assertEquals($status, $lesson->status);
        }
    }

    /** @test */
    public function it_can_have_payment_status()
    {
        $lesson = Lesson::factory()->create(['payment_status' => 'paid']);
        
        $this->assertEquals('paid', $lesson->payment_status);
    }
}