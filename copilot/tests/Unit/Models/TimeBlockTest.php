<?php

namespace Tests\Unit\Models;

use App\Models\TimeBlock;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeBlockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $timeBlock = new TimeBlock();
        
        $this->assertInstanceOf(TimeBlock::class, $timeBlock);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $timeBlock = new TimeBlock();
        
        $this->assertEquals('time_blocks', $timeBlock->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $timeBlock = new TimeBlock();
        
        $this->assertTrue($timeBlock->timestamps);
    }
}
