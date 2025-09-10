<?php

namespace Database\Factories;

use App\Models\TeacherSkill;
use App\Models\Teacher;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherSkill>
 */
class TeacherSkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TeacherSkill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'skill_id' => Skill::factory(),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'expert', 'master']),
            'experience_years' => $this->faker->numberBetween(0, 20),
            'acquired_date' => $this->faker->optional(0.7)->dateTimeBetween('-5 years', 'now'),
            'last_practiced' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'evidence' => $this->faker->optional(0.2)->randomElements(['video', 'certificate', 'testimony'], $this->faker->numberBetween(1, 2)),
            'is_verified' => $this->faker->boolean(70),
            'verified_by' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray()),
            'verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-2 years', 'now'),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Indicate that the skill is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_by' => User::factory(),
            'verified_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the skill is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Indicate that the skill is at expert level.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'expert',
            'experience_years' => $this->faker->numberBetween(10, 20),
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the skill is at beginner level.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'beginner',
            'experience_years' => $this->faker->numberBetween(0, 2),
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate that the skill is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the skill is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}