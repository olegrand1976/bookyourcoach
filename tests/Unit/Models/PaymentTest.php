<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated()
    {
        $payment = new Payment();

        $this->assertInstanceOf(Payment::class, $payment);
    }

    #[Test]
    public function it_has_correct_table_name()
    {
        $payment = new Payment();

        $this->assertEquals('payments', $payment->getTable());
    }

    #[Test]
    public function it_uses_timestamps()
    {
        $payment = new Payment();

        $this->assertTrue($payment->timestamps);
    }
}
