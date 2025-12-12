<?php

namespace Database\Factories;

use App\Models\LessonReplacement;
use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LessonReplacement>
 */
class LessonReplacementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LessonReplacement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'accepted', 'rejected', 'cancelled'];
        $status = $this->faker->randomElement($statuses);

        $reasons = [
            'Maladie',
            'Congé',
            'Urgence familiale',
            'Formation',
            'Autre raison personnelle'
        ];

        return [
            'lesson_id' => Lesson::factory(),
            'original_teacher_id' => Teacher::factory(),
            'replacement_teacher_id' => Teacher::factory(),
            'status' => $status,
            'reason' => $this->faker->randomElement($reasons),
            'notes' => $this->faker->optional()->sentence(),
            'requested_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'responded_at' => function (array $attributes) use ($status) {
                // Si le statut est accepté ou refusé, il y a une date de réponse
                return in_array($status, ['accepted', 'rejected']) 
                    ? $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now')
                    : null;
            },
        ];
    }

    /**
     * Indicate that the replacement is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
        ]);
    }

    /**
     * Indicate that the replacement is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the replacement is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'responded_at' => $this->faker->dateTimeBetween($attributes['requested_at'] ?? '-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the replacement is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'responded_at' => null,
        ]);
    }
}

