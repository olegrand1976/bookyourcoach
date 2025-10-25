<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class LocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated()
    {
        $location = new Location();

        $this->assertInstanceOf(Location::class, $location);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        $location = new Location();

        $this->assertEquals('locations', $location->getTable());
    }

    #[Test]
    public function it_uses_timestamps()
    {
        $location = new Location();

        $this->assertTrue($location->timestamps);
    }
}
