<?php

namespace Tests\Unit\Models;

use App\Models\Availability;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated()
    {
        $availability = new Availability();

        $this->assertInstanceOf(Availability::class, $availability);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        $availability = new Availability();

        $this->assertEquals('availabilities', $availability->getTable());
    }

    #[Test]
    public function it_uses_timestamps()
    {
        $availability = new Availability();

        $this->assertTrue($availability->timestamps);
    }
}
