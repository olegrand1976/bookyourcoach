<?php

namespace Tests\Unit\Models;

use App\Models\Availability;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $availability = new Availability();
        
        $this->assertInstanceOf(Availability::class, $availability);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $availability = new Availability();
        
        $this->assertEquals('availabilities', $availability->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $availability = new Availability();
        
        $this->assertTrue($availability->timestamps);
    }
}
