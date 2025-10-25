<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facilityTypes = [
            'indoor_arena' => 'Manège Couvert',
            'outdoor_arena' => 'Carrière Extérieure',
            'round_pen' => 'Rond de Longe',
            'jumping_arena' => 'Carrière de Saut',
            'dressage_arena' => 'Carrière de Dressage',
            'trail_course' => 'Parcours de Cross',
            'stabling' => 'Écuries',
            'tack_room' => 'Sellerie',
            'office' => 'Bureau',
            'reception' => 'Accueil',
        ];

        $type = $this->faker->randomElement(array_keys($facilityTypes));
        $name = $facilityTypes[$type];

        return [
            'activity_type_id' => ActivityType::factory(),
            'name' => $name . ' ' . $this->faker->numberBetween(1, 5),
            'type' => $type,
            'capacity' => $this->generateCapacity($type),
            'dimensions' => $this->generateDimensions($type),
            'equipment' => $this->generateEquipment($type),
            'description' => $this->generateDescription($type),
            'is_active' => $this->faker->boolean(90), // 90% active
        ];
    }

    /**
     * Generate capacity based on facility type.
     */
    private function generateCapacity(string $type): int
    {
        $capacities = [
            'indoor_arena' => $this->faker->numberBetween(8, 20),
            'outdoor_arena' => $this->faker->numberBetween(10, 25),
            'round_pen' => 1,
            'jumping_arena' => $this->faker->numberBetween(6, 15),
            'dressage_arena' => $this->faker->numberBetween(4, 12),
            'trail_course' => $this->faker->numberBetween(5, 20),
            'stabling' => $this->faker->numberBetween(10, 50),
            'tack_room' => $this->faker->numberBetween(5, 20),
            'office' => $this->faker->numberBetween(2, 8),
            'reception' => $this->faker->numberBetween(3, 10),
        ];

        return $capacities[$type] ?? 1;
    }

    /**
     * Generate dimensions based on facility type.
     */
    private function generateDimensions(string $type): array
    {
        $dimensions = [
            'indoor_arena' => [
                'length' => $this->faker->numberBetween(20, 60),
                'width' => $this->faker->numberBetween(20, 40),
                'height' => $this->faker->numberBetween(4, 8),
            ],
            'outdoor_arena' => [
                'length' => $this->faker->numberBetween(30, 80),
                'width' => $this->faker->numberBetween(20, 50),
            ],
            'round_pen' => [
                'diameter' => $this->faker->numberBetween(15, 25),
            ],
            'jumping_arena' => [
                'length' => $this->faker->numberBetween(40, 80),
                'width' => $this->faker->numberBetween(20, 40),
            ],
            'dressage_arena' => [
                'length' => 60,
                'width' => 20,
            ],
            'trail_course' => [
                'length' => $this->faker->numberBetween(500, 2000),
                'obstacles' => $this->faker->numberBetween(10, 30),
            ],
            'stabling' => [
                'stalls' => $this->faker->numberBetween(10, 50),
                'aisle_width' => $this->faker->numberBetween(3, 5),
            ],
            'tack_room' => [
                'length' => $this->faker->numberBetween(5, 15),
                'width' => $this->faker->numberBetween(3, 8),
            ],
            'office' => [
                'length' => $this->faker->numberBetween(3, 8),
                'width' => $this->faker->numberBetween(3, 6),
            ],
            'reception' => [
                'length' => $this->faker->numberBetween(5, 12),
                'width' => $this->faker->numberBetween(4, 8),
            ],
        ];

        return $dimensions[$type] ?? [];
    }

    /**
     * Generate equipment based on facility type.
     */
    private function generateEquipment(string $type): array
    {
        $equipment = [
            'indoor_arena' => [
                'mirrors', 'sound_system', 'lighting', 'ventilation', 'safety_barriers'
            ],
            'outdoor_arena' => [
                'drainage_system', 'lighting', 'safety_barriers', 'watering_system'
            ],
            'round_pen' => [
                'gate', 'safety_barriers', 'footing_material'
            ],
            'jumping_arena' => [
                'jumps', 'standards', 'poles', 'cups', 'safety_cups'
            ],
            'dressage_arena' => [
                'letters', 'center_line', 'short_sides', 'long_sides'
            ],
            'trail_course' => [
                'natural_obstacles', 'man_made_obstacles', 'water_features', 'bridges'
            ],
            'stabling' => [
                'automatic_waterers', 'feed_bins', 'bedding', 'ventilation', 'lighting'
            ],
            'tack_room' => [
                'saddle_racks', 'bridle_hooks', 'shelving', 'lockers', 'cleaning_supplies'
            ],
            'office' => [
                'desk', 'computer', 'phone', 'filing_cabinet', 'printer'
            ],
            'reception' => [
                'desk', 'computer', 'phone', 'seating', 'display_materials'
            ],
        ];

        $availableEquipment = $equipment[$type] ?? [];
        return $this->faker->randomElements($availableEquipment, $this->faker->numberBetween(1, count($availableEquipment)));
    }

    /**
     * Generate description based on facility type.
     */
    private function generateDescription(string $type): string
    {
        $descriptions = [
            'indoor_arena' => 'Manège couvert équipé pour tous types de cours et d\'entraînements.',
            'outdoor_arena' => 'Carrière extérieure avec un excellent drainage et un éclairage pour les cours du soir.',
            'round_pen' => 'Rond de longe parfait pour le travail à pied et le débourrage.',
            'jumping_arena' => 'Carrière spécialement aménagée pour le saut d\'obstacles avec obstacles variés.',
            'dressage_arena' => 'Carrière de dressage aux dimensions officielles avec lettres et marquages.',
            'trail_course' => 'Parcours de cross avec obstacles naturels et artificiels variés.',
            'stabling' => 'Écuries modernes avec boxes spacieux et équipements de confort.',
            'tack_room' => 'Sellerie bien organisée avec rangements pour tout l\'équipement.',
            'office' => 'Bureau administratif pour la gestion du club.',
            'reception' => 'Espace d\'accueil pour les visiteurs et les membres.',
        ];

        return $descriptions[$type] ?? 'Installation équipée pour les activités équestres.';
    }

    /**
     * Indicate that the facility is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the facility is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an indoor arena.
     */
    public function indoorArena(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'indoor_arena',
            'name' => 'Manège Couvert Principal',
            'capacity' => $this->faker->numberBetween(8, 20),
        ]);
    }

    /**
     * Create an outdoor arena.
     */
    public function outdoorArena(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'outdoor_arena',
            'name' => 'Carrière Extérieure',
            'capacity' => $this->faker->numberBetween(10, 25),
        ]);
    }

    /**
     * Create a jumping arena.
     */
    public function jumpingArena(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'jumping_arena',
            'name' => 'Carrière de Saut',
            'capacity' => $this->faker->numberBetween(6, 15),
        ]);
    }
}
