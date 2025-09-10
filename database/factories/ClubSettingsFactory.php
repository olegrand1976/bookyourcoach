<?php

namespace Database\Factories;

use App\Models\ClubSettings;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubSettings>
 */
class ClubSettingsFactory extends Factory
{
    protected $model = ClubSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $features = [
            'lesson_booking' => [
                'name' => 'Réservation de Cours',
                'category' => 'booking',
                'description' => 'Permet aux étudiants de réserver des cours en ligne',
                'icon' => 'calendar',
            ],
            'payment_online' => [
                'name' => 'Paiement en Ligne',
                'category' => 'payment',
                'description' => 'Système de paiement intégré',
                'icon' => 'credit-card',
            ],
            'instructor_management' => [
                'name' => 'Gestion des Instructeurs',
                'category' => 'management',
                'description' => 'Gestion des profils et disponibilités des instructeurs',
                'icon' => 'users',
            ],
            'student_progress' => [
                'name' => 'Suivi des Progrès',
                'category' => 'analytics',
                'description' => 'Suivi des progrès des étudiants',
                'icon' => 'chart-line',
            ],
            'equipment_booking' => [
                'name' => 'Réservation d\'Équipement',
                'category' => 'booking',
                'description' => 'Réservation d\'équipement équestre',
                'icon' => 'tools',
            ],
            'competition_management' => [
                'name' => 'Gestion des Compétitions',
                'category' => 'events',
                'description' => 'Organisation et gestion des compétitions',
                'icon' => 'trophy',
            ],
            'newsletter' => [
                'name' => 'Newsletter',
                'category' => 'communication',
                'description' => 'Envoi de newsletters aux membres',
                'icon' => 'envelope',
            ],
            'social_media' => [
                'name' => 'Réseaux Sociaux',
                'category' => 'communication',
                'description' => 'Intégration avec les réseaux sociaux',
                'icon' => 'share',
            ],
        ];

        $featureKey = $this->faker->randomElement(array_keys($features));
        $feature = $features[$featureKey];

        return [
            'club_id' => Club::factory(),
            'feature_key' => $featureKey,
            'feature_name' => $feature['name'],
            'feature_category' => $feature['category'],
            'is_enabled' => $this->faker->boolean(70), // 70% enabled
            'configuration' => $this->generateConfiguration($featureKey),
            'description' => $feature['description'],
            'icon' => $feature['icon'],
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Generate configuration based on feature key.
     */
    private function generateConfiguration(string $featureKey): array
    {
        $configurations = [
            'lesson_booking' => [
                'advance_booking_days' => $this->faker->numberBetween(1, 30),
                'cancellation_hours' => $this->faker->numberBetween(2, 24),
                'max_lessons_per_day' => $this->faker->numberBetween(1, 5),
                'auto_confirm' => $this->faker->boolean(),
            ],
            'payment_online' => [
                'stripe_enabled' => $this->faker->boolean(),
                'paypal_enabled' => $this->faker->boolean(),
                'bank_transfer_enabled' => $this->faker->boolean(),
                'currency' => 'EUR',
                'tax_rate' => $this->faker->randomFloat(2, 0, 20),
            ],
            'instructor_management' => [
                'auto_assign' => $this->faker->boolean(),
                'rating_system' => $this->faker->boolean(),
                'availability_buffer' => $this->faker->numberBetween(0, 60),
                'max_students_per_instructor' => $this->faker->numberBetween(5, 20),
            ],
            'student_progress' => [
                'track_attendance' => $this->faker->boolean(),
                'skill_assessment' => $this->faker->boolean(),
                'certificate_generation' => $this->faker->boolean(),
                'progress_reports' => $this->faker->boolean(),
            ],
            'equipment_booking' => [
                'advance_booking_days' => $this->faker->numberBetween(1, 7),
                'max_booking_duration' => $this->faker->numberBetween(1, 24),
                'deposit_required' => $this->faker->boolean(),
                'auto_return_reminder' => $this->faker->boolean(),
            ],
            'competition_management' => [
                'registration_deadline_days' => $this->faker->numberBetween(7, 30),
                'entry_fee_collection' => $this->faker->boolean(),
                'results_publication' => $this->faker->boolean(),
                'photo_gallery' => $this->faker->boolean(),
            ],
            'newsletter' => [
                'frequency' => $this->faker->randomElement(['weekly', 'monthly', 'quarterly']),
                'auto_send' => $this->faker->boolean(),
                'template_customization' => $this->faker->boolean(),
                'subscriber_segments' => $this->faker->boolean(),
            ],
            'social_media' => [
                'facebook_integration' => $this->faker->boolean(),
                'instagram_integration' => $this->faker->boolean(),
                'twitter_integration' => $this->faker->boolean(),
                'auto_post' => $this->faker->boolean(),
            ],
        ];

        return $configurations[$featureKey] ?? [];
    }

    /**
     * Indicate that the feature is enabled.
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    /**
     * Indicate that the feature is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a booking-related feature.
     */
    public function booking(): static
    {
        return $this->state(fn (array $attributes) => [
            'feature_category' => 'booking',
            'feature_key' => $this->faker->randomElement(['lesson_booking', 'equipment_booking']),
        ]);
    }

    /**
     * Create a payment-related feature.
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'feature_category' => 'payment',
            'feature_key' => 'payment_online',
        ]);
    }

    /**
     * Create a management-related feature.
     */
    public function management(): static
    {
        return $this->state(fn (array $attributes) => [
            'feature_category' => 'management',
            'feature_key' => $this->faker->randomElement(['instructor_management', 'student_progress']),
        ]);
    }
}
