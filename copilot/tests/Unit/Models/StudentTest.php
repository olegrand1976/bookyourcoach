<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $student = new Student();
        
        $this->assertInstanceOf(Student::class, $student);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $student = new Student();
        
        $this->assertEquals('students', $student->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $student = new Student();
        
        $this->assertTrue($student->timestamps);
    }
}
