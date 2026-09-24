<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Donne à un compte le second profil d'un compte double club + enseignant.
 *
 * Le rôle enregistré (users.role) ne change pas : on ajoute la relation qui manque.
 * Les jetons du compte sont révoqués : la prochaine connexion impose l'enrôlement 2FA.
 */
class DualProfileService
{
    /**
     * Ce que l'opération ferait, sans rien écrire.
     *
     * @return array{direction: string, changes: list<string>}
     */
    public function plan(User $user, ?Club $club): array
    {
        return match ($user->primaryRole()) {
            User::ROLE_TEACHER => $this->planClubManagement($user, $club),
            User::ROLE_CLUB => $this->planTeacherProfile($user),
            default => throw new \InvalidArgumentException('Seuls les comptes enseignant ou club peuvent devenir doubles.'),
        };
    }

    /**
     * @return array{direction: string, changes: list<string>}
     */
    public function apply(User $user, ?Club $club): array
    {
        $plan = $this->plan($user, $club);

        DB::transaction(function () use ($user, $club) {
            if ($user->primaryRole() === User::ROLE_TEACHER) {
                $this->grantClubManagement($user, $club);
            } else {
                $this->grantTeacherProfile($user);
            }

            $user->tokens()->delete();
        });

        return $plan;
    }

    /**
     * @return array{direction: string, changes: list<string>}
     */
    private function planClubManagement(User $user, ?Club $club): array
    {
        if (! $club) {
            throw new \InvalidArgumentException('Un compte enseignant devient gérant d’un club précis : --club est obligatoire.');
        }

        $existing = DB::table('club_user')->where('club_id', $club->id)->where('user_id', $user->id)->first();
        $changes = [];

        if ($existing && in_array($existing->role, User::CLUB_MANAGER_ROLES, true)) {
            $changes[] = "déjà gérant du club #{$club->id} ({$existing->role}) : rien à faire";
        } elseif ($existing) {
            $changes[] = "lien club_user #{$club->id} existant : rôle « {$existing->role} » → « manager », is_admin → oui";
        } else {
            $changes[] = "lien club_user #{$club->id} créé : rôle « manager », is_admin oui";
        }

        $isTeacherThere = $user->teacher?->clubs()->where('clubs.id', $club->id)->exists() ?? false;
        if (! $isTeacherThere) {
            $changes[] = "attention : ce compte n’enseigne pas dans le club #{$club->id}";
        }

        return ['direction' => 'enseignant → + gérant du club '.$club->name, 'changes' => $changes];
    }

    /**
     * @return array{direction: string, changes: list<string>}
     */
    private function planTeacherProfile(User $user): array
    {
        $club = $user->managedClubs()->first();
        if (! $club) {
            throw new \InvalidArgumentException('Ce compte club ne gère aucun club : impossible de le rattacher comme enseignant.');
        }

        $changes = $user->teacher()->exists()
            ? ['fiche enseignant déjà présente : rien à faire']
            : ["fiche enseignant créée, rattachée au club #{$club->id}"];

        return ['direction' => 'club → + enseignant dans '.$club->name, 'changes' => $changes];
    }

    private function grantClubManagement(User $user, Club $club): void
    {
        $existing = DB::table('club_user')->where('club_id', $club->id)->where('user_id', $user->id)->first();

        if ($existing && in_array($existing->role, User::CLUB_MANAGER_ROLES, true)) {
            return;
        }

        if ($existing) {
            DB::table('club_user')->where('id', $existing->id)->update([
                'role' => 'manager',
                'is_admin' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grantTeacherProfile(User $user): void
    {
        if ($user->teacher()->exists()) {
            return;
        }

        $club = $user->managedClubs()->first();

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'club_id' => $club->id,
            'hourly_rate' => 0,
            'experience_years' => 0,
            'is_available' => true,
            'specialties' => [],
            'certifications' => [],
            'preferred_locations' => [],
        ]);

        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);
    }
}
