<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(['role' => User::ROLE_TEACHER]),
            'specialties' => fake()->randomElements(['dressage', 'obstacle', 'cross', 'voltige', 'attelage'], fake()->numberBetween(1, 3)),
            'experience_years' => fake()->numberBetween(1, 30),
            'certifications' => fake()->randomElements(['FFE Galop 7', 'BPJEPS', 'CQP ASA', 'DE JEPS', 'Instructeur FFE'], fake()->numberBetween(1, 3)),
            'hourly_rate' => fake()->randomFloat(2, 30, 100),
            'bio' => fake()->optional()->paragraph(),
            'is_available' => fake()->boolean(80), // 80% chance d'être disponible
            'max_travel_distance' => fake()->numberBetween(10, 100),
            'preferred_locations' => fake()->randomElements(['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier'], fake()->numberBetween(1, 4)),
            'stripe_account_id' => fake()->optional()->regexify('acct_[A-Za-z0-9]{16}'),
            'rating' => fake()->randomFloat(2, 3.0, 5.0),
            'total_lessons' => fake()->numberBetween(0, 1000),
        ];
    }
}
