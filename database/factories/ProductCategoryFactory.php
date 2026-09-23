<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'equipment' => [
                'name' => 'Équipement Équestre',
                'icon' => '🛡️',
                'color' => '#8B4513',
            ],
            'clothing' => [
                'name' => 'Vêtements',
                'icon' => '👕',
                'color' => '#4169E1',
            ],
            'accessories' => [
                'name' => 'Accessoires',
                'icon' => '🎒',
                'color' => '#FF6347',
            ],
            'feed' => [
                'name' => 'Alimentation',
                'icon' => '🌾',
                'color' => '#32CD32',
            ],
            'health' => [
                'name' => 'Santé & Soins',
                'icon' => '💊',
                'color' => '#FF1493',
            ],
            'books' => [
                'name' => 'Livres & Médias',
                'icon' => '📚',
                'color' => '#9932CC',
            ],
            'gifts' => [
                'name' => 'Cadeaux',
                'icon' => '🎁',
                'color' => '#FFD700',
            ],
        ];

        $categoryKey = $this->faker->randomElement(array_keys($categories));
        $category = $categories[$categoryKey];

        return [
            'name' => $category['name'],
            // Le slug est unique en base : deux catégories générées dans le même
            // test tiraient la même clé une fois sur sept et cassaient l'insertion.
            'slug' => $categoryKey.'-'.$this->faker->unique()->numberBetween(1, 999999),
            'description' => $this->generateDescription($categoryKey),
            'icon' => $category['icon'],
            'color' => $category['color'],
            'is_active' => $this->faker->boolean(90), // 90% active
        ];
    }

    /**
     * Generate description based on category.
     */
    private function generateDescription(string $category): string
    {
        $descriptions = [
            'equipment' => 'Tout l\'équipement nécessaire pour la pratique de l\'équitation : selles, brides, étriers, etc.',
            'clothing' => 'Vêtements et chaussures spécialisés pour l\'équitation et les activités équestres.',
            'accessories' => 'Accessoires pratiques pour les cavaliers et les chevaux.',
            'feed' => 'Aliments et compléments nutritionnels pour chevaux de tous âges et niveaux.',
            'health' => 'Produits de soins, médicaments et accessoires pour la santé des chevaux.',
            'books' => 'Livres, DVD et supports pédagogiques sur l\'équitation et les soins aux chevaux.',
            'gifts' => 'Idées cadeaux pour les passionnés d\'équitation.',
        ];

        return $descriptions[$category] ?? 'Catégorie de produits équestres.';
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an equipment category.
     */
    public function equipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Équipement Équestre',
            'slug' => 'equipment',
            'icon' => '🛡️',
            'color' => '#8B4513',
        ]);
    }

    /**
     * Create a clothing category.
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Vêtements',
            'slug' => 'clothing',
            'icon' => '👕',
            'color' => '#4169E1',
        ]);
    }

    /**
     * Create a feed category.
     */
    public function feed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Alimentation',
            'slug' => 'feed',
            'icon' => '🌾',
            'color' => '#32CD32',
        ]);
    }
}
