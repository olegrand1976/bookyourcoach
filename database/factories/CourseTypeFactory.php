<?php

namespace Database\Factories;

use App\Models\CourseType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseTypeFactory extends Factory
{
    protected $model = CourseType::class;

    public function definition()
    {
        $courseTypes = [
            'Dressage' => [
                'description' => 'Cours de dressage pour améliorer la technique et la précision',
                'duration_minutes' => 60,
                'price' => 45.00
            ],
            'Obstacle' => [
                'description' => 'Cours de saut d\'obstacles pour développer la technique de saut',
                'duration_minutes' => 60,
                'price' => 50.00
            ],
            'Cross' => [
                'description' => 'Cours de cross-country en terrain varié',
                'duration_minutes' => 75,
                'price' => 55.00
            ],
            'Préparation Compétition' => [
                'description' => 'Préparation spécialisée pour les compétitions équestres',
                'duration_minutes' => 90,
                'price' => 70.00
            ],
            'Cours Débutant' => [
                'description' => 'Cours d\'initiation pour les cavaliers débutants',
                'duration_minutes' => 45,
                'price' => 35.00
            ],
            'Perfectionnement' => [
                'description' => 'Cours de perfectionnement pour cavaliers confirmés',
                'duration_minutes' => 60,
                'price' => 60.00
            ],
        ];

        $courseType = $this->faker->randomElement(array_keys($courseTypes));
        $details = $courseTypes[$courseType];

        return [
            'name' => $courseType,
            'description' => $details['description'],
            'duration_minutes' => $details['duration_minutes'],
            'price' => $details['price'],
        ];
    }

    /**
     * Course type with custom pricing
     */
    public function withPrice($price)
    {
        return $this->state(function (array $attributes) use ($price) {
            return [
                'price' => $price,
            ];
        });
    }

    /**
     * Course type with custom duration
     */
    public function withDuration($duration)
    {
        return $this->state(function (array $attributes) use ($duration) {
            return [
                'duration_minutes' => $duration,
            ];
        });
    }

    /**
     * Specialized course type
     */
    public function specialized()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Cours Spécialisé',
                'description' => 'Cours spécialisé avec accompagnement personnalisé',
                'duration_minutes' => 120,
                'price' => 100.00,
            ];
        });
    }

    /**
     * Basic course type for beginners
     */
    public function beginner()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Cours Débutant',
                'description' => 'Cours d\'initiation pour les nouveaux cavaliers',
                'duration_minutes' => 30,
                'price' => 25.00,
            ];
        });
    }
}
