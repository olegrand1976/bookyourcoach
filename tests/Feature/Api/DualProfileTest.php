<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Compte double club + enseignant : bascule par en-tête, 2FA dans les deux rôles.
 */
class DualProfileTest extends TestCase
{
    private function teacherAccount(bool $twoFactor = true): User
    {
        $factory = User::factory();
        if ($twoFactor) {
            $factory = $factory->withTwoFactor();
        }
        $user = $factory->create(['role' => User::ROLE_TEACHER]);
        Teacher::create(['user_id' => $user->id, 'hourly_rate' => 0, 'is_available' => true]);

        return $user;
    }

    private function makeManager(User $user, Club $club, string $role = 'manager'): void
    {
        DB::table('club_user')->insert([
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_admin' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function dualAccount(bool $twoFactor = true): array
    {
        $user = $this->teacherAccount($twoFactor);
        $club = Club::factory()->create();
        $this->makeManager($user, $club);

        return [$user, $club];
    }

    private function clubRoute(): string
    {
        return '/api/club/closure-days?date_from=2026-01-01&date_to=2026-01-31';
    }

    #[Test]
    public function a_dual_account_lists_both_roles_and_keeps_its_primary_role_by_default(): void
    {
        [$user] = $this->dualAccount();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonPath('user.available_roles', ['club', 'teacher']);
    }

    #[Test]
    public function the_header_switches_a_dual_account_to_club_without_saving_it(): void
    {
        [$user, $club] = $this->dualAccount();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Active-Role' => 'club'])->getJson('/api/auth/user')
            ->assertJsonPath('user.role', 'club');

        $this->withHeaders(['X-Active-Role' => 'club'])->getJson($this->clubRoute())->assertOk();
        $this->withHeaders(['X-Active-Role' => 'club'])->getJson('/api/teacher/profile')->assertForbidden();

        $this->assertSame('teacher', DB::table('users')->where('id', $user->id)->value('role'));
    }

    #[Test]
    public function without_the_header_a_dual_account_stays_out_of_club_routes(): void
    {
        [$user] = $this->dualAccount();
        Sanctum::actingAs($user);

        $this->getJson($this->clubRoute())->assertForbidden();
    }

    #[Test]
    public function the_header_grants_nothing_to_a_single_role_account(): void
    {
        $user = $this->teacherAccount();
        Sanctum::actingAs($user);

        $this->withHeaders(['X-Active-Role' => 'club'])->getJson('/api/auth/user')
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonPath('user.available_roles', ['teacher']);
        $this->withHeaders(['X-Active-Role' => 'club'])->getJson($this->clubRoute())->assertForbidden();
        $this->withHeaders(['X-Active-Role' => 'admin'])->getJson('/api/admin/users')->assertForbidden();
    }

    #[Test]
    public function a_plain_club_membership_does_not_make_a_teacher_dual(): void
    {
        // Un enseignant rattaché au club via club_user (rôle « teacher ») n'est pas gérant.
        $user = $this->teacherAccount(twoFactor: false);
        $this->makeManager($user, Club::factory()->create(), 'teacher');

        $this->assertFalse($user->hasDualProfile());
        $this->assertFalse($user->requiresTwoFactor());
    }

    #[Test]
    public function a_dual_account_without_two_factor_is_blocked_in_both_roles(): void
    {
        [$user] = $this->dualAccount(twoFactor: false);
        Sanctum::actingAs($user);

        $this->getJson('/api/teacher/profile')
            ->assertForbidden()
            ->assertJsonPath('code', 'two_factor_setup_required');
        $this->withHeaders(['X-Active-Role' => 'club'])->getJson($this->clubRoute())
            ->assertForbidden()
            ->assertJsonPath('code', 'two_factor_setup_required');
    }

    #[Test]
    public function a_dual_account_must_enrol_two_factor_at_login(): void
    {
        [$user] = $this->dualAccount(twoFactor: false);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.two_factor_setup_required', true)
            ->assertJsonMissingPath('access_token');
    }

    #[Test]
    public function the_first_club_of_a_dual_account_is_the_one_it_manages(): void
    {
        $user = $this->teacherAccount();
        $teachesThere = Club::factory()->create();
        $managed = Club::factory()->create();
        $this->makeManager($user, $teachesThere, 'teacher');
        $this->makeManager($user, $managed);

        $this->assertSame($managed->id, $user->getFirstClub()->id);
    }

    #[Test]
    public function the_command_simulates_by_default_then_grants_club_management(): void
    {
        $user = $this->teacherAccount(twoFactor: false);
        $club = Club::factory()->create();
        $user->createToken('auth_token');

        $this->artisan('user:grant-dual-profile', ['email' => $user->email, '--club' => $club->id])
            ->expectsOutputToContain('[SIMULATION]')
            ->assertSuccessful();
        $this->assertFalse(DB::table('club_user')->where('user_id', $user->id)->exists());

        $this->artisan('user:grant-dual-profile', ['email' => $user->email, '--club' => $club->id, '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('club_user', ['club_id' => $club->id, 'user_id' => $user->id, 'role' => 'manager']);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertTrue($user->fresh()->requiresTwoFactor());
        $this->assertSame('teacher', $user->fresh()->role);

        // Idempotente.
        $this->artisan('user:grant-dual-profile', ['email' => $user->email, '--club' => $club->id, '--apply' => true, '--force' => true])
            ->expectsOutputToContain('rien à faire')
            ->assertSuccessful();
        $this->assertSame(1, DB::table('club_user')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function the_command_gives_a_club_account_a_teacher_profile(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLUB]);
        $club = Club::factory()->create();
        $this->makeManager($user, $club, 'owner');

        $this->artisan('user:grant-dual-profile', ['email' => $user->email, '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $teacher = $user->fresh()->teacher;
        $this->assertNotNull($teacher);
        $this->assertTrue($teacher->clubs()->where('clubs.id', $club->id)->exists());
        $this->assertSame(['club', 'teacher'], $user->fresh()->availableRoles());
    }

    #[Test]
    public function the_command_requires_a_club_for_a_teacher_account(): void
    {
        $user = $this->teacherAccount();

        $this->artisan('user:grant-dual-profile', ['email' => $user->email])
            ->expectsOutputToContain('--club est obligatoire')
            ->assertFailed();
    }
}
