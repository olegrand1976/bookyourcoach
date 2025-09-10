<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productNames = [
            'Selle de Dressage',
            'Bombe de Sécurité',
            'Bottes d\'Équitation',
            'Gants Cavalier',
            'Cravache',
            'Éperons',
            'Filet Simple',
            'Filet Double',
            'Sangle de Selle',
            'Étriers',
            'Couverture de Cheval',
            'Brosse de Pansage',
            'Étrille',
            'Peigne de Queue',
            'Shampoing Équestre',
        ];

        return [
            'club_id' => \App\Models\Club::factory(),
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->randomElement($productNames),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 200),
            'cost_price' => $this->faker->randomFloat(2, 3, 150),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'min_stock' => $this->faker->numberBetween(1, 10),
            'sku' => 'PROD-' . $this->faker->unique()->numerify('########'),
            'barcode' => $this->faker->optional(0.7)->ean13(),
            'images' => $this->faker->optional(0.5)->randomElements(['image1.jpg', 'image2.jpg'], $this->faker->numberBetween(1, 2)),
            'is_active' => $this->faker->boolean(90), // 90% active
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Create an equipment product.
     */
    public function equipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => ProductCategory::factory()->equipment()->create()->id,
            'name' => $this->faker->randomElement(['Selle', 'Bombe', 'Bottes', 'Gants']),
            'price' => $this->faker->randomFloat(2, 20, 200),
        ]);
    }

    /**
     * Create a clothing product.
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => ProductCategory::factory()->clothing()->create()->id,
            'name' => $this->faker->randomElement(['Pantalon', 'Veste', 'Chemise', 'Chaussettes']),
            'price' => $this->faker->randomFloat(2, 15, 80),
        ]);
    }

    /**
     * Create a feed product.
     */
    public function feed(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => ProductCategory::factory()->feed()->create()->id,
            'name' => $this->faker->randomElement(['Granulés', 'Foin', 'Avoine', 'Complément']),
            'price' => $this->faker->randomFloat(2, 5, 50),
        ]);
    }
}