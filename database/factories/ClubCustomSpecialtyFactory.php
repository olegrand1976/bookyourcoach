<?php

namespace Database\Factories;

use App\Models\ClubCustomSpecialty;
use App\Models\Club;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubCustomSpecialty>
 */
class ClubCustomSpecialtyFactory extends Factory
{
    protected $model = ClubCustomSpecialty::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specialtyNames = [
            'Cours de Dressage Avancé',
            'Saut d\'Obstacles Technique',
            'Équitation Western',
            'Voltige Équestre',
            'Attelage Traditionnel',
            'Équitation de Travail',
            'Cross Country',
            'Équitation de Loisir',
            'Préparation Compétition',
            'Équitation Thérapeutique'
        ];

        return [
            'club_id' => Club::factory(),
            'activity_type_id' => ActivityType::factory(),
            'name' => $this->faker->randomElement($specialtyNames),
            'description' => $this->faker->paragraph(),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'base_price' => $this->faker->randomFloat(2, 15, 80),
            'skill_levels' => $this->faker->randomElements(
                ['débutant', 'intermédiaire', 'avancé', 'expert'],
                $this->faker->numberBetween(1, 4)
            ),
            'min_participants' => $this->faker->numberBetween(1, 3),
            'max_participants' => $this->faker->numberBetween(4, 12),
            'equipment_required' => $this->faker->randomElements(
                ['casque', 'bottes', 'gants', 'bombe', 'cravache', 'éperons', 'selle', 'filet'],
                $this->faker->numberBetween(2, 5)
            ),
            'is_active' => $this->faker->boolean(85), // 85% active
        ];
    }

    /**
     * Indicate that the specialty is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the specialty is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an individual lesson specialty.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_participants' => 1,
            'max_participants' => 1,
            'base_price' => $this->faker->randomFloat(2, 40, 80),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60]),
        ]);
    }

    /**
     * Create a group lesson specialty.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_participants' => $this->faker->numberBetween(2, 4),
            'max_participants' => $this->faker->numberBetween(6, 12),
            'base_price' => $this->faker->randomFloat(2, 15, 35),
            'duration_minutes' => $this->faker->randomElement([60, 90]),
        ]);
    }

    /**
     * Create a beginner-level specialty.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_levels' => ['débutant'],
            'name' => 'Cours Débutant - ' . $this->faker->words(2, true),
            'base_price' => $this->faker->randomFloat(2, 15, 25),
        ]);
    }

    /**
     * Create an advanced specialty.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_levels' => ['avancé', 'expert'],
            'name' => 'Cours Avancé - ' . $this->faker->words(2, true),
            'base_price' => $this->faker->randomFloat(2, 40, 80),
            'min_participants' => 1,
            'max_participants' => $this->faker->numberBetween(2, 6),
        ]);
    }
}
