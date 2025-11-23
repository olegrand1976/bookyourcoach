<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Club;
use App\Models\SubscriptionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Vérifier si club_id existe dans la table
        $hasClubIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'club_id');
        
        $definition = [];
        
        // Ajouter club_id seulement si la colonne existe
        if ($hasClubIdColumn) {
            $definition['club_id'] = Club::factory();
        }
        
        // Ajouter subscription_template_id si disponible (optionnel)
        if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id')) {
            // Ne pas créer de template par défaut pour éviter les dépendances circulaires
            // Le test peut créer un template si nécessaire
            $definition['subscription_template_id'] = null;
        }
        
        // Ajouter subscription_number si la colonne existe
        if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_number')) {
            // Le numéro sera généré automatiquement par le modèle
            $definition['subscription_number'] = null;
        }
        
        // Ajouter validity_months si la colonne existe
        if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'validity_months')) {
            $definition['validity_months'] = $this->faker->numberBetween(1, 12);
        }
        
        return $definition;
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            // Vérifier si is_active existe dans la table
            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'is_active')) {
                return ['is_active' => true];
            }
            return [];
        });
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            // Vérifier si is_active existe dans la table
            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'is_active')) {
                return ['is_active' => false];
            }
            return [];
        });
    }

    /**
     * Set a specific club for the subscription.
     */
    public function forClub(Club $club): static
    {
        return $this->state(function (array $attributes) use ($club) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'club_id')) {
                return ['club_id' => $club->id];
            }
            return [];
        });
    }

    /**
     * Set a specific subscription template.
     */
    public function withTemplate(SubscriptionTemplate $template): static
    {
        return $this->state(function (array $attributes) use ($template) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'subscription_template_id')) {
                return ['subscription_template_id' => $template->id];
            }
            return [];
        });
    }
}

