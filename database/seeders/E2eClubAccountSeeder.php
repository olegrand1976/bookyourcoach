<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\TrustedDevice;
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
        // La 2FA est obligatoire pour un club : le compte e2e est enrôlé avec un secret
        // connu des tests, et un appareil de confiance leur évite de saisir un code à
        // chaque connexion (deux codes dans la même période de 30 s seraient refusés).
        $totpSecret = env('E2E_CLUB_TOTP_SECRET');
        $deviceToken = env('E2E_CLUB_DEVICE_TOKEN');

        if (! $password) {
            $this->command?->error(
                'E2E_CLUB_PASSWORD doit être défini (voir frontend/.env.test.example). '
                .'Aucun mot de passe par défaut n’est fourni volontairement.'
            );

            return;
        }

        if (! $totpSecret || ! preg_match('/^[A-Z2-7]{16,}$/', $totpSecret) || ! $deviceToken || strlen($deviceToken) < 32) {
            $this->command?->error(
                'E2E_CLUB_TOTP_SECRET (base32, 16 caractères min.) et E2E_CLUB_DEVICE_TOKEN (32 caractères min.) '
                .'doivent être définis (voir frontend/.env.test.example).'
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

        $user->forceFill([
            'two_factor_secret' => $totpSecret,
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ])->save();

        TrustedDevice::updateOrCreate(
            ['token_hash' => hash('sha256', $deviceToken)],
            [
                'user_id' => $user->id,
                'user_agent' => 'Playwright (e2e)',
                'expires_at' => now()->addYear(),
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
