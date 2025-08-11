<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $payment = new Payment();

        $this->assertInstanceOf(Payment::class, $payment);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $payment = new Payment();

        $this->assertEquals('payments', $payment->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $payment = new Payment();

        $this->assertTrue($payment->timestamps);
    }
}
