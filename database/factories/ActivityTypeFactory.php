<?php

namespace Database\Factories;

use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    public function definition()
    {
        $activityTypes = [
            'Équitation',
            'Natation',
            'Salle de sport',
            'Coaching sportif',
            'Tennis',
            'Golf',
            'Escalade',
            'Danse',
            'Arts martiaux',
            'Cyclisme'
        ];

        return [
            'name' => $this->faker->randomElement($activityTypes),
            'description' => $this->faker->sentence(),
            'slug' => $this->faker->slug(),
            'is_active' => $this->faker->boolean(90)
        ];
    }

    /**
     * Type d'activité équestre
     */
    public function equestrian()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Équitation',
                'description' => 'Pratique de l\'équitation et des sports équestres',
                'slug' => 'equitation'
            ];
        });
    }

    /**
     * Type d'activité aquatique
     */
    public function aquatic()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Natation',
                'description' => 'Activités aquatiques et natation',
                'slug' => 'natation'
            ];
        });
    }

    /**
     * Type d'activité fitness
     */
    public function fitness()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Salle de sport',
                'description' => 'Activités de fitness et musculation',
                'slug' => 'salle-de-sport'
            ];
        });
    }

    /**
     * Type d'activité coaching
     */
    public function coaching()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Coaching sportif',
                'description' => 'Coaching personnel et préparation physique',
                'slug' => 'coaching-sportif'
            ];
        });
    }
}
