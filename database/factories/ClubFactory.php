<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club>
 */
class ClubFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Club::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Equestrian Club',
            'description' => $this->faker->paragraph(3),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'website' => $this->faker->optional()->url(),
            'facilities' => $this->faker->optional()->paragraph(2),
            'disciplines' => $this->faker->optional()->words(3, true),
            'max_students' => $this->faker->numberBetween(20, 200),
            'subscription_price' => $this->faker->randomFloat(2, 50, 500),
            'is_active' => $this->faker->boolean(80), // 80% chance d'être actif
            'terms_and_conditions' => $this->faker->optional()->paragraph(5),
        ];
    }

    /**
     * Indicate that the club is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the club is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the club has a specific number of max students.
     */
    public function withMaxStudents(int $maxStudents): static
    {
        return $this->state(fn (array $attributes) => [
            'max_students' => $maxStudents,
        ]);
    }

    /**
     * Indicate that the club has a specific subscription price.
     */
    public function withSubscriptionPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_price' => $price,
        ]);
    }
}
