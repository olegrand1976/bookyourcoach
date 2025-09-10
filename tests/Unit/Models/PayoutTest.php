<?php

namespace Tests\Unit\Models;

use App\Models\Payout;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class PayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated()
    {
        $payout = new Payout();

        $this->assertInstanceOf(Payout::class, $payout);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        $payout = new Payout();

        $this->assertEquals('payouts', $payout->getTable());
    }

    #[Test]
    public function it_uses_timestamps()
    {
        $payout = new Payout();

        $this->assertTrue($payout->timestamps);
    }
}
