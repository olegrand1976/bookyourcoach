<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'replacement_request',
            'replacement_accepted',
            'replacement_rejected',
            'replacement_cancelled',
            'club_replacement_accepted'
        ];

        $type = $this->faker->randomElement($types);
        
        // Générer un titre et un message selon le type
        $titles = [
            'replacement_request' => 'Demande de remplacement reçue',
            'replacement_accepted' => 'Remplacement accepté',
            'replacement_rejected' => 'Remplacement refusé',
            'replacement_cancelled' => 'Remplacement annulé',
            'club_replacement_accepted' => 'Remplacement accepté par le club'
        ];

        $messages = [
            'replacement_request' => 'Vous avez reçu une demande de remplacement pour un cours.',
            'replacement_accepted' => 'Votre demande de remplacement a été acceptée.',
            'replacement_rejected' => 'Votre demande de remplacement a été refusée.',
            'replacement_cancelled' => 'La demande de remplacement a été annulée.',
            'club_replacement_accepted' => 'Un remplacement a été accepté pour un de vos cours.'
        ];

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $titles[$type] ?? 'Notification',
            'message' => $messages[$type] ?? $this->faker->sentence(),
            'data' => [
                'lesson_id' => $this->faker->numberBetween(1, 100),
                'teacher_id' => $this->faker->numberBetween(1, 50),
            ],
            'read' => $this->faker->boolean(30), // 30% de chance d'être lu
            'read_at' => function (array $attributes) {
                return $attributes['read'] ? $this->faker->dateTimeBetween('-1 week', 'now') : null;
            },
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Set a specific notification type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}

