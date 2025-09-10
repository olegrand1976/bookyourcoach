<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => \App\Models\Club::factory(),
            'cash_register_id' => \App\Models\CashRegister::factory(),
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['sale', 'refund', 'expense', 'deposit']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'transfer', 'check', 'multiple']),
            'description' => $this->faker->optional(0.3)->paragraph(),
            'reference' => $this->faker->optional(0.5)->uuid(),
            'metadata' => $this->faker->optional(0.2)->randomElements(['key1' => 'value1', 'key2' => 'value2']),
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}