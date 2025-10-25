<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Club;
use App\Models\CashRegister;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class TransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_created_with_required_fields()
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();
        $cashRegister = CashRegister::factory()->create(['club_id' => $club->id]);

        $transactionData = [
            'club_id' => $club->id,
            'cash_register_id' => $cashRegister->id,
            'user_id' => $user->id,
            'type' => 'sale',
            'amount' => 50.00,
            'payment_method' => 'card',
            'description' => 'Paiement cours d\'équitation',
            'reference' => 'REF-001',
            'processed_at' => now(),
        ];

        $transaction = Transaction::create($transactionData);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($transactionData['amount'], $transaction->amount);
        $this->assertEquals($transactionData['type'], $transaction->type);
        $this->assertEquals($transactionData['payment_method'], $transaction->payment_method);
    }

    #[Test]
    public function it_has_user_relationship()
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $transaction->user());
        $this->assertInstanceOf(User::class, $transaction->user);
    }

    #[Test]
    public function it_has_club_relationship()
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $transaction->club());
        $this->assertInstanceOf(Club::class, $transaction->club);
    }

    #[Test]
    public function it_has_cash_register_relationship()
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $transaction->cashRegister());
        $this->assertInstanceOf(CashRegister::class, $transaction->cashRegister);
    }

    #[Test]
    public function it_casts_amount_as_decimal()
    {
        $transaction = Transaction::factory()->create(['amount' => 50.50]);

        $this->assertIsString($transaction->amount); // Laravel retourne les decimals comme string
        $this->assertEquals('50.50', $transaction->amount);
    }

    #[Test]
    public function it_casts_metadata_as_array()
    {
        $metadata = [
            'stripe_payment_intent' => 'pi_1234567890',
            'payment_method' => 'card',
            'billing_address' => [
                'city' => 'Paris',
                'country' => 'FR'
            ]
        ];

        $transaction = Transaction::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals($metadata, $transaction->metadata);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'club_id',
            'cash_register_id',
            'user_id',
            'type',
            'amount',
            'payment_method',
            'description',
            'reference',
            'metadata',
            'processed_at'
        ];

        $transaction = new Transaction();
        $this->assertEquals($fillable, $transaction->getFillable());
    }

    #[Test]
    public function it_can_have_different_types()
    {
        $types = ['sale', 'refund', 'expense', 'deposit'];

        foreach ($types as $type) {
            $transaction = Transaction::factory()->create(['type' => $type]);
            $this->assertEquals($type, $transaction->type);
        }
    }

    #[Test]
    public function it_can_have_different_payment_methods()
    {
        $methods = ['cash', 'card', 'transfer', 'check', 'multiple'];

        foreach ($methods as $method) {
            $transaction = Transaction::factory()->create(['payment_method' => $method]);
            $this->assertEquals($method, $transaction->payment_method);
        }
    }

    #[Test]
    public function it_can_be_ordered_by_created_at()
    {
        $transaction1 = Transaction::factory()->create();
        sleep(1); // S'assurer qu'il y a une différence de temps
        $transaction2 = Transaction::factory()->create();

        $transactions = Transaction::orderBy('created_at', 'desc')->get();

        $this->assertEquals($transaction2->id, $transactions->first()->id);
        $this->assertEquals($transaction1->id, $transactions->last()->id);
    }

    #[Test]
    public function it_can_have_processed_at_timestamp()
    {
        $processedAt = now()->addHours(2);
        $transaction = Transaction::factory()->create(['processed_at' => $processedAt]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->processed_at);
        $this->assertEquals($processedAt->format('Y-m-d H:i:s'), $transaction->processed_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_have_items_relationship()
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $transaction->items());
    }

    #[Test]
    public function it_can_calculate_total_from_items()
    {
        $transaction = Transaction::factory()->create(['amount' => 100.00]);
        
        // Simuler des items (même si la factory n'existe pas encore)
        $this->assertIsNumeric($transaction->calculateTotal());
    }

    #[Test]
    public function it_can_generate_receipt()
    {
        $transaction = Transaction::factory()->create();

        $receipt = $transaction->generateReceipt();

        $this->assertIsArray($receipt);
        $this->assertArrayHasKey('transaction_id', $receipt);
        $this->assertArrayHasKey('date', $receipt);
        $this->assertArrayHasKey('total', $receipt);
        $this->assertArrayHasKey('payment_method', $receipt);
    }

    #[Test]
    public function it_can_create_refund()
    {
        $originalTransaction = Transaction::factory()->create(['amount' => 50.00]);
        
        // Simuler l'authentification pour le refund
        $this->actingAs($originalTransaction->user);
        
        $refund = $originalTransaction->refund();

        $this->assertInstanceOf(Transaction::class, $refund);
        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(-50.00, $refund->amount);
        $this->assertEquals($originalTransaction->club_id, $refund->club_id);
    }
}