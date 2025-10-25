<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certification>
 */
class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['official', 'federation', 'continuing_education', 'specialized'];
        $authorities = ['FFE', 'Fédération Française d\'Équitation', 'Ministère des Sports', 'Organisme Privé', 'Formation Interne'];

        return [
            'name' => $this->faker->words(3, true) . ' Certification',
            'issuing_authority' => $this->faker->randomElement($authorities),
            'category' => $this->faker->randomElement($categories),
            'activity_type_id' => ActivityType::factory(),
            'validity_years' => $this->faker->randomElement([1, 2, 3, 5, null]), // null = permanent
            'requirements' => [
                'minimum_age' => $this->faker->numberBetween(16, 25),
                'experience_hours' => $this->faker->numberBetween(50, 500),
                'practical_test' => $this->faker->boolean(),
                'written_exam' => $this->faker->boolean(),
            ],
            'description' => $this->faker->paragraph(),
            'icon' => $this->faker->randomElement(['certificate', 'shield', 'star', 'medal', 'badge']),
            'is_active' => $this->faker->boolean(90), // 90% active
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the certification is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the certification is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the certification is permanent (no expiry).
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'validity_years' => null,
        ]);
    }

    /**
     * Indicate that the certification expires in a specific number of years.
     */
    public function expiresIn(int $years): static
    {
        return $this->state(fn (array $attributes) => [
            'validity_years' => $years,
        ]);
    }

    /**
     * Create a safety certification.
     */
    public function safety(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'official',
            'name' => 'Certification Sécurité Équestre',
            'issuing_authority' => 'FFE',
            'validity_years' => 3,
        ]);
    }

    /**
     * Create a teaching certification.
     */
    public function teaching(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'federation',
            'name' => 'Certification Enseignement Équestre',
            'issuing_authority' => 'Fédération Française d\'Équitation',
            'validity_years' => 5,
        ]);
    }
}
