<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['technical', 'pedagogical', 'management', 'communication', 'technology'];
        $skillNames = [
            'technical' => ['Équilibre', 'Position', 'Aides', 'Contrôle des rênes', 'Assiette'],
            'pedagogical' => ['Enseignement', 'Pédagogie', 'Communication pédagogique', 'Gestion de groupe'],
            'management' => ['Gestion administrative', 'Planification', 'Organisation', 'Leadership'],
            'communication' => ['Communication avec le cheval', 'Écoute', 'Empathie', 'Patience'],
            'technology' => ['Utilisation des outils numériques', 'Gestion des données', 'Communication digitale'],
        ];

        $category = $this->faker->randomElement($categories);
        $name = $this->faker->randomElement($skillNames[$category]);

        return [
            'name' => $name,
            'category' => $category,
            'activity_type_id' => ActivityType::factory(),
            'description' => $this->generateDescription($category, $name),
            'icon' => $this->generateIcon($category),
            'levels' => $this->generateLevels($category),
            'requirements' => $this->generateRequirements($category),
            'is_active' => $this->faker->boolean(90), // 90% active
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Generate description based on category and name.
     */
    private function generateDescription(string $category, string $name): string
    {
        $descriptions = [
            'technical' => "Compétence technique fondamentale en équitation : {$name}. Essentielle pour le développement du cavalier.",
            'pedagogical' => "Compétence pédagogique importante : {$name}. Nécessaire pour l'enseignement efficace.",
            'management' => "Compétence de gestion : {$name}. Utile pour l'organisation et la direction.",
            'communication' => "Compétence de communication : {$name}. Base de la relation cavalier-cheval.",
            'technology' => "Compétence technologique : {$name}. Essentielle dans le monde moderne.",
        ];

        return $descriptions[$category] ?? "Compétence équestre : {$name}.";
    }

    /**
     * Generate icon based on category.
     */
    private function generateIcon(string $category): string
    {
        $icons = [
            'technical' => '🎯',
            'pedagogical' => '👨‍🏫',
            'management' => '📊',
            'communication' => '💬',
            'technology' => '💻',
        ];

        return $icons[$category] ?? '⭐';
    }

    /**
     * Generate levels based on category.
     */
    private function generateLevels(string $category): array
    {
        $baseLevels = ['beginner', 'intermediate', 'advanced', 'expert', 'master'];
        
        // Some skills might have fewer levels
        if ($category === 'safety') {
            return ['beginner', 'intermediate', 'advanced'];
        }
        
        return $baseLevels;
    }

    /**
     * Generate requirements based on category.
     */
    private function generateRequirements(string $category): array
    {
        $requirements = [
            'technical' => [
                'minimum_age' => $this->faker->numberBetween(6, 12),
                'experience_hours' => $this->faker->numberBetween(20, 200),
                'prerequisite_skills' => $this->faker->randomElements(['Équilibre', 'Position'], $this->faker->numberBetween(0, 2)),
            ],
            'physical' => [
                'minimum_age' => $this->faker->numberBetween(8, 16),
                'fitness_level' => $this->faker->randomElement(['basic', 'intermediate', 'advanced']),
                'training_hours' => $this->faker->numberBetween(10, 100),
            ],
            'mental' => [
                'minimum_age' => $this->faker->numberBetween(10, 18),
                'maturity_level' => $this->faker->randomElement(['basic', 'intermediate', 'advanced']),
                'experience_hours' => $this->faker->numberBetween(50, 300),
            ],
            'communication' => [
                'minimum_age' => $this->faker->numberBetween(8, 14),
                'empathy_level' => $this->faker->randomElement(['basic', 'intermediate', 'advanced']),
                'experience_hours' => $this->faker->numberBetween(30, 250),
            ],
            'safety' => [
                'minimum_age' => $this->faker->numberBetween(6, 10),
                'certification_required' => $this->faker->boolean(),
                'training_hours' => $this->faker->numberBetween(5, 50),
            ],
        ];

        return $requirements[$category] ?? [];
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

    /**
     * Create a technical skill.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'technical',
            'name' => $this->faker->randomElement(['Équilibre', 'Position', 'Aides', 'Contrôle des rênes']),
            'icon' => '🎯',
        ]);
    }

    /**
     * Create a safety skill.
     */
    public function safety(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'technical',
            'name' => $this->faker->randomElement(['Sécurité', 'Prévention', 'Gestion des risques']),
            'icon' => '🛡️',
        ]);
    }

    /**
     * Create a communication skill.
     */
    public function communication(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'communication',
            'name' => $this->faker->randomElement(['Communication avec le cheval', 'Écoute', 'Empathie']),
            'icon' => '💬',
        ]);
    }
}
