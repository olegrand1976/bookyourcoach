<?php

namespace Tests\Unit\Models;

use App\Models\Lesson;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $lesson = new Lesson();
        
        $this->assertInstanceOf(Lesson::class, $lesson);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $lesson = new Lesson();
        
        $this->assertEquals('lessons', $lesson->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $lesson = new Lesson();
        
        $this->assertTrue($lesson->timestamps);
    }
}
