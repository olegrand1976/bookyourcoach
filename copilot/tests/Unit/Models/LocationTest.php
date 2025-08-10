<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $location = new Location();
        
        $this->assertInstanceOf(Location::class, $location);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $location = new Location();
        
        $this->assertEquals('locations', $location->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $location = new Location();
        
        $this->assertTrue($location->timestamps);
    }
}
