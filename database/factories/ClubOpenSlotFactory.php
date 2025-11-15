<?php

namespace Database\Factories;

use App\Models\ClubOpenSlot;
use App\Models\Club;
use App\Models\Discipline;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClubOpenSlotFactory extends Factory
{
    protected $model = ClubOpenSlot::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 18);
        $endHour = $startHour + $this->faker->numberBetween(1, 3);
        
        return [
            'club_id' => Club::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'discipline_id' => Discipline::factory(),
            'max_slots' => $this->faker->numberBetween(1, 10), // Nombre de plages simultanées
            'max_capacity' => $this->faker->numberBetween(1, 10), // Nombre d'élèves par enseignant
            'duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'price' => $this->faker->randomFloat(2, 20, 80),
        ];
    }
}

