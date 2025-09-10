<?php

namespace Database\Factories;

use App\Models\TransactionItem;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 5, 100);
        $discount = $this->faker->randomFloat(2, 0, 20);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'item_name' => $this->faker->words(2, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'discount' => $discount,
        ];
    }

    /**
     * Indicate that the item has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => 0,
        ]);
    }

    /**
     * Indicate that the item has a specific discount percentage.
     */
    public function withDiscount(float $discount): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => $discount,
        ]);
    }
}
