<?php

namespace Database\Factories;

use App\Models\Discipline;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisciplineFactory extends Factory
{
    protected $model = Discipline::class;

    public function definition()
    {
        $disciplines = [
            'Dressage',
            'Obstacle',
            'Cross',
            'Complet',
            'Voltige',
            'Attelage',
            'Endurance',
            'Western',
            'Polo',
            'Trekking'
        ];

        return [
            'name' => $this->faker->randomElement($disciplines),
            'description' => $this->faker->sentence(),
            'activity_type_id' => ActivityType::factory(),
            'is_active' => $this->faker->boolean(90)
        ];
    }

    /**
     * Discipline équestre
     */
    public function equestrian()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Dressage', 'Obstacle', 'Cross', 'Complet']),
                'description' => 'Discipline équestre traditionnelle',
                'activity_type_id' => 1 // Équitation
            ];
        });
    }

    /**
     * Discipline aquatique
     */
    public function aquatic()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Natation', 'Aquagym', 'Aquabike', 'Aquafitness']),
                'description' => 'Discipline aquatique',
                'activity_type_id' => 2 // Natation
            ];
        });
    }

    /**
     * Discipline de fitness
     */
    public function fitness()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Musculation', 'Cardio', 'Yoga', 'Pilates']),
                'description' => 'Discipline de fitness',
                'activity_type_id' => 3 // Salle de sport
            ];
        });
    }

    /**
     * Discipline de coaching
     */
    public function coaching()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Coaching personnel', 'Préparation physique', 'Rééducation']),
                'description' => 'Discipline de coaching sportif',
                'activity_type_id' => 4 // Coaching sportif
            ];
        });
    }
}
