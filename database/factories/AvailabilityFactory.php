<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Availability::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => \App\Models\Teacher::factory(),
            'location_id' => \App\Models\Location::factory(),
            'start_time' => $this->faker->dateTimeBetween('now', '+1 week'),
            'end_time' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'is_available' => $this->faker->boolean(80),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the availability is active.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    /**
     * Indicate that the availability is not available.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Create availability for a specific day.
     */
    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    /**
     * Create availability for weekdays.
     */
    public function weekdays(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $this->faker->numberBetween(1, 5), // Monday to Friday
        ]);
    }

    /**
     * Create availability for weekends.
     */
    public function weekends(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $this->faker->randomElement([0, 6]), // Sunday or Saturday
        ]);
    }
}