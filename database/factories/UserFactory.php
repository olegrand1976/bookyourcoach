<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Un club ou un admin fabriqué est enrôlé en 2FA par défaut : c'est l'état normal
     * de ces comptes, sans lequel le middleware « 2fa » les refuse. Les tests du
     * parcours d'enrôlement partent de withoutTwoFactor().
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if (in_array($user->role, [User::ROLE_CLUB, User::ROLE_ADMIN], true) && $user->two_factor_confirmed_at === null) {
                $user->forceFill([
                    'two_factor_secret' => app(Google2FA::class)->generateSecretKey(32),
                    'two_factor_recovery_codes' => [],
                    'two_factor_confirmed_at' => now(),
                ]);
            }
        });
    }

    /**
     * Compte dont la double authentification est déjà configurée.
     */
    public function withTwoFactor(?string $secret = null): static
    {
        return $this->state(fn(array $attributes) => [
            'two_factor_secret' => $secret ?? app(Google2FA::class)->generateSecretKey(32),
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Compte club ou admin qui n'a pas encore configuré sa double authentification.
     */
    public function withoutTwoFactor(): static
    {
        // Enregistré après celui de configure(), ce rappel l'emporte.
        return $this->afterMaking(function (User $user) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]);
        });
    }
}
