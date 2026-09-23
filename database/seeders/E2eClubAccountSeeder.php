<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Compte club dédié aux tests end-to-end.
 *
 * Il existe pour que les tests n'aient plus besoin d'un compte réel : l'identifiant
 * d'un gérant de production était codé en dur dans les tests d'un dépôt public.
 *
 * Ce compte est volontairement sans privilège d'administration (is_admin = false) :
 * un test n'a pas à disposer de plus de droits que ce qu'il vérifie.
 *
 * Réservé au développement — le seeder refuse de s'exécuter ailleurs.
 */
class E2eClubAccountSeeder extends Seeder
{
    private const DEFAULT_EMAIL = 'e2e-club@example.test';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            $this->command?->error('Ce seeder est réservé aux environnements de développement.');

            return;
        }

        $email = env('E2E_CLUB_EMAIL', self::DEFAULT_EMAIL);
        $password = env('E2E_CLUB_PASSWORD');

        if (! $password) {
            $this->command?->error(
                'E2E_CLUB_PASSWORD doit être défini (voir frontend/.env.test.example). '
                .'Aucun mot de passe par défaut n’est fourni volontairement.'
            );

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Club E2E',
                'first_name' => 'Club',
                'last_name' => 'E2E',
                'password' => Hash::make($password),
                'role' => User::ROLE_CLUB,
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $club = Club::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Club E2E',
                'description' => 'Club de test end-to-end — ne pas utiliser en production.',
                'city' => 'Test',
                'country' => 'Belgique',
                'max_students' => 50,
                'is_active' => true,
            ]
        );

        DB::table('club_user')->updateOrInsert(
            ['club_id' => $club->id, 'user_id' => $user->id],
            [
                'role' => 'owner',
                'is_admin' => false,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command?->info("Compte club e2e prêt : {$email} (club #{$club->id}).");
    }
}
