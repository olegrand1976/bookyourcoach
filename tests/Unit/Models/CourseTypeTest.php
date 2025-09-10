<?php

namespace Tests\Unit\Models;

use App\Models\CourseType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class CourseTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated()
    {
        $courseType = new CourseType();

        $this->assertInstanceOf(CourseType::class, $courseType);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        $courseType = new CourseType();

        $this->assertEquals('course_types', $courseType->getTable());
    }

    #[Test]
    public function it_uses_timestamps()
    {
        $courseType = new CourseType();

        $this->assertTrue($courseType->timestamps);
    }
}
