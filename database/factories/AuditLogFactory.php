<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
            'model_type' => $this->faker->randomElement(['User', 'Club', 'Lesson', 'Payment']),
            'model_id' => $this->faker->numberBetween(1, 100),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the audit log is for a user action.
     */
    public function userAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'User',
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
        ]);
    }

    /**
     * Indicate that the audit log is for a club action.
     */
    public function clubAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'Club',
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
        ]);
    }

    /**
     * Indicate that the audit log has old and new values.
     */
    public function withChanges(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => ['name' => 'Old Name', 'email' => 'old@example.com'],
            'new_values' => ['name' => 'New Name', 'email' => 'new@example.com'],
        ]);
    }
}
