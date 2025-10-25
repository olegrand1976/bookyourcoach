<?php

namespace Database\Factories;

use App\Models\StudentPreference;
use App\Models\Student;
use App\Models\Discipline;
use App\Models\CourseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentPreference>
 */
class StudentPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'discipline_id' => Discipline::factory(),
            'course_type_id' => CourseType::factory(),
            'is_preferred' => $this->faker->boolean(80),
            'priority_level' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Indicate that this is a preferred discipline/course type.
     */
    public function preferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => true,
            'priority_level' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that this is not a preferred discipline/course type.
     */
    public function notPreferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => false,
            'priority_level' => $this->faker->numberBetween(3, 5),
        ]);
    }

    /**
     * Indicate high priority preference.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => true,
            'priority_level' => 1,
        ]);
    }

    /**
     * Indicate low priority preference.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => false,
            'priority_level' => 5,
        ]);
    }

    /**
     * Indicate that the preference is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the preference is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}