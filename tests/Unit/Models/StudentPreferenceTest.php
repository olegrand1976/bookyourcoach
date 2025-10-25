<?php

namespace Tests\Unit\Models;

use App\Models\StudentPreference;
use App\Models\Student;
use App\Models\Discipline;
use App\Models\CourseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class StudentPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_student_preference()
    {
        $student = Student::factory()->create();
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create();

        $preference = StudentPreference::create([
            'student_id' => $student->id,
            'discipline_id' => $discipline->id,
            'course_type_id' => $courseType->id,
            'is_preferred' => true,
            'priority_level' => 1
        ]);

        $this->assertInstanceOf(StudentPreference::class, $preference);
        $this->assertEquals($student->id, $preference->student_id);
        $this->assertEquals($discipline->id, $preference->discipline_id);
        $this->assertEquals($courseType->id, $preference->course_type_id);
        $this->assertTrue($preference->is_preferred);
        $this->assertEquals(1, $preference->priority_level);
    }

    #[Test]
    public function test_belongs_to_student()
    {
        $student = Student::factory()->create();
        $preference = StudentPreference::factory()->create([
            'student_id' => $student->id
        ]);

        $this->assertInstanceOf(Student::class, $preference->student);
        $this->assertEquals($student->id, $preference->student->id);
    }

    #[Test]
    public function test_belongs_to_discipline()
    {
        $discipline = Discipline::factory()->create();
        $preference = StudentPreference::factory()->create([
            'discipline_id' => $discipline->id
        ]);

        $this->assertInstanceOf(Discipline::class, $preference->discipline);
        $this->assertEquals($discipline->id, $preference->discipline->id);
    }

    #[Test]
    public function test_belongs_to_course_type()
    {
        $courseType = CourseType::factory()->create();
        $preference = StudentPreference::factory()->create([
            'course_type_id' => $courseType->id
        ]);

        $this->assertInstanceOf(CourseType::class, $preference->courseType);
        $this->assertEquals($courseType->id, $preference->courseType->id);
    }

    #[Test]
    public function test_is_preferred_casting()
    {
        $preference = StudentPreference::create([
            'student_id' => Student::factory()->create()->id,
            'discipline_id' => Discipline::factory()->create()->id,
            'course_type_id' => CourseType::factory()->create()->id,
            'is_preferred' => true
        ]);

        $this->assertIsBool($preference->is_preferred);
        $this->assertTrue($preference->is_preferred);
    }

    #[Test]
    public function test_priority_level_casting()
    {
        $preference = StudentPreference::create([
            'student_id' => Student::factory()->create()->id,
            'discipline_id' => Discipline::factory()->create()->id,
            'course_type_id' => CourseType::factory()->create()->id,
            'priority_level' => 5
        ]);

        $this->assertIsInt($preference->priority_level);
        $this->assertEquals(5, $preference->priority_level);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $preference = new StudentPreference();
        $fillable = $preference->getFillable();

        $expectedFillable = [
            'student_id',
            'discipline_id',
            'course_type_id',
            'is_preferred',
            'priority_level'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $preference = new StudentPreference();
        $casts = $preference->getCasts();

        $this->assertArrayHasKey('is_preferred', $casts);
        $this->assertArrayHasKey('priority_level', $casts);
    }
}
