<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $invoice = new Invoice();
        
        $this->assertInstanceOf(Invoice::class, $invoice);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $invoice = new Invoice();
        
        $this->assertEquals('invoices', $invoice->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $invoice = new Invoice();
        
        $this->assertTrue($invoice->timestamps);
    }
}
