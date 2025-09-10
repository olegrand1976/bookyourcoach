<?php

namespace Tests\Unit\Models;

use App\Models\TransactionItem;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_transaction_item()
    {
        $transaction = Transaction::factory()->create();
        $product = Product::factory()->create();

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'item_name' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 15.50,
            'total_price' => 31.00,
            'discount' => 2.00
        ]);

        $this->assertInstanceOf(TransactionItem::class, $transactionItem);
        $this->assertEquals($transaction->id, $transactionItem->transaction_id);
        $this->assertEquals($product->id, $transactionItem->product_id);
        $this->assertEquals('Test Item', $transactionItem->item_name);
        $this->assertEquals(2, $transactionItem->quantity);
        $this->assertEquals(15.50, $transactionItem->unit_price);
        $this->assertEquals(31.00, $transactionItem->total_price);
        $this->assertEquals(2.00, $transactionItem->discount);
    }

    public function test_belongs_to_transaction()
    {
        $transaction = Transaction::factory()->create();
        $transactionItem = TransactionItem::factory()->create([
            'transaction_id' => $transaction->id
        ]);

        $this->assertInstanceOf(Transaction::class, $transactionItem->transaction);
        $this->assertEquals($transaction->id, $transactionItem->transaction->id);
    }

    public function test_belongs_to_product()
    {
        $product = Product::factory()->create();
        $transactionItem = TransactionItem::factory()->create([
            'product_id' => $product->id
        ]);

        $this->assertInstanceOf(Product::class, $transactionItem->product);
        $this->assertEquals($product->id, $transactionItem->product->id);
    }

    public function test_calculate_total()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'quantity' => 3,
            'unit_price' => 10.00,
            'total_price' => 30.00,
            'discount' => 5.00
        ]);

        $calculatedTotal = $transactionItem->calculateTotal();

        // (3 * 10.00) - 5.00 = 25.00
        $this->assertEquals(25.00, $calculatedTotal);
    }

    public function test_calculate_total_without_discount()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 15.00,
            'total_price' => 30.00,
            'discount' => 0.00
        ]);

        $calculatedTotal = $transactionItem->calculateTotal();

        // (2 * 15.00) - 0.00 = 30.00
        $this->assertEquals(30.00, $calculatedTotal);
    }

    public function test_quantity_casting()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'quantity' => 5
        ]);

        $this->assertIsInt($transactionItem->quantity);
        $this->assertEquals(5, $transactionItem->quantity);
    }

    public function test_unit_price_casting()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'unit_price' => 12.75
        ]);

        $this->assertIsFloat($transactionItem->unit_price);
        $this->assertEquals(12.75, $transactionItem->unit_price);
    }

    public function test_total_price_casting()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'total_price' => 25.50
        ]);

        $this->assertIsFloat($transactionItem->total_price);
        $this->assertEquals(25.50, $transactionItem->total_price);
    }

    public function test_discount_casting()
    {
        $transactionItem = TransactionItem::create([
            'transaction_id' => Transaction::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'item_name' => 'Test Item',
            'discount' => 3.25
        ]);

        $this->assertIsFloat($transactionItem->discount);
        $this->assertEquals(3.25, $transactionItem->discount);
    }

    public function test_fillable_attributes()
    {
        $transactionItem = new TransactionItem();
        $fillable = $transactionItem->getFillable();

        $expectedFillable = [
            'transaction_id',
            'product_id',
            'item_name',
            'quantity',
            'unit_price',
            'total_price',
            'discount'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $transactionItem = new TransactionItem();
        $casts = $transactionItem->getCasts();

        $this->assertArrayHasKey('quantity', $casts);
        $this->assertArrayHasKey('unit_price', $casts);
        $this->assertArrayHasKey('total_price', $casts);
        $this->assertArrayHasKey('discount', $casts);
    }
}
