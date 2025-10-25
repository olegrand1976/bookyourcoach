<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashRegister>
 */
class CashRegisterFactory extends Factory
{
    protected $model = CashRegister::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => $this->faker->words(2, true),
            'location' => $this->faker->optional(0.7)->address(),
            'is_active' => $this->faker->boolean(80),
            'current_balance' => $this->faker->randomFloat(2, 0, 2000),
            'last_closing_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
