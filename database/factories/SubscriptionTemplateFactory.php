<?php

namespace Database\Factories;

use App\Models\SubscriptionTemplate;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionTemplateFactory extends Factory
{
    protected $model = SubscriptionTemplate::class;

    public function definition()
    {
        return [
            'club_id' => Club::factory(),
            'model_number' => 'ABON-' . $this->faker->unique()->numberBetween(1000, 9999),
            'total_lessons' => $this->faker->randomElement([5, 10, 20, 30]),
            'free_lessons' => $this->faker->randomElement([0, 1, 2]),
            'price' => $this->faker->randomFloat(2, 100, 500),
            'validity_months' => $this->faker->randomElement([3, 6, 12]),
            'is_active' => true,
        ];
    }

    /**
     * Template pour un pack de 10 cours
     */
    public function pack10()
    {
        return $this->state(function (array $attributes) {
            return [
                'total_lessons' => 10,
                'free_lessons' => 0,
                'price' => 200.00,
                'validity_months' => 6,
            ];
        });
    }

    /**
     * Template pour un pack de 20 cours avec bonus
     */
    public function pack20()
    {
        return $this->state(function (array $attributes) {
            return [
                'total_lessons' => 20,
                'free_lessons' => 2,
                'price' => 380.00,
                'validity_months' => 12,
            ];
        });
    }

    /**
     * Template inactif
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}

