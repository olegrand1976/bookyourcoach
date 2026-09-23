<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Réglages 2FA de son compte et réinitialisation par un administrateur.
 */
class TwoFactorAccountTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasseSolide2026';

    private Google2FA $google2fa;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->google2fa = app(Google2FA::class);
        $this->secret = $this->google2fa->generateSecretKey(32);
    }

    private function club(): User
    {
        $user = User::factory()->withTwoFactor($this->secret)->create([
            'role' => User::ROLE_CLUB,
            'password' => Hash::make(self::PASSWORD),
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    #[Test]
    public function status_lists_active_trusted_devices_without_their_token(): void
    {
        $user = $this->club();
        app(TwoFactorService::class)->issueTrustedDevice($user, Request::create('/', 'POST'));

        $this->getJson('/api/auth/two-factor')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.required', true)
            ->assertJsonCount(1, 'data.trusted_devices')
            ->assertJsonMissingPath('data.trusted_devices.0.token_hash');

        $this->getJson('/api/auth/user')->assertJsonPath('user.two_factor_enabled', true);
    }

    #[Test]
    public function regenerating_recovery_codes_requires_password_and_code(): void
    {
        $this->club();

        $this->postJson('/api/auth/two-factor/recovery-codes', ['password' => 'faux', 'code' => '000000'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/auth/two-factor/recovery-codes', ['password' => self::PASSWORD, 'code' => '000000'])
            ->assertStatus(422);

        $this->postJson('/api/auth/two-factor/recovery-codes', [
            'password' => self::PASSWORD,
            'code' => $this->google2fa->getCurrentOtp($this->secret),
        ])->assertOk()->assertJsonCount(TwoFactorService::RECOVERY_CODES_COUNT, 'data.recovery_codes');
    }

    #[Test]
    public function changing_phone_keeps_the_old_secret_until_confirmed(): void
    {
        $user = $this->club();
        app(TwoFactorService::class)->issueTrustedDevice($user, Request::create('/', 'POST'));

        $newSecret = $this->postJson('/api/auth/two-factor/reconfigure', [
            'password' => self::PASSWORD,
            'code' => $this->google2fa->getCurrentOtp($this->secret),
        ])->assertOk()->json('data.secret');

        $this->assertSame($this->secret, $user->fresh()->two_factor_secret, 'L\'ancien secret reste actif');

        $this->postJson('/api/auth/two-factor/reconfigure/confirm', ['code' => '000000'])->assertStatus(422);

        // Même période de 30 s que le code de l'ancien secret : il ne doit pas être
        // pris pour un rejeu.
        $this->postJson('/api/auth/two-factor/reconfigure/confirm', [
            'code' => $this->google2fa->getCurrentOtp($newSecret),
        ])->assertOk()->assertJsonCount(TwoFactorService::RECOVERY_CODES_COUNT, 'data.recovery_codes');

        $user->refresh();
        $this->assertSame($newSecret, $user->two_factor_secret);
        $this->assertSame(0, $user->trustedDevices()->count(), 'L\'ancien téléphone peut être perdu : ses appareils tombent');
    }

    #[Test]
    public function a_user_can_only_revoke_their_own_devices(): void
    {
        $service = app(TwoFactorService::class);
        $other = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $service->issueTrustedDevice($other, Request::create('/', 'POST'));
        $othersDevice = $other->trustedDevices()->first();

        $user = $this->club();
        $service->issueTrustedDevice($user, Request::create('/', 'POST'));
        $service->issueTrustedDevice($user, Request::create('/', 'POST'));
        $mine = $user->trustedDevices()->first();

        $this->deleteJson('/api/auth/two-factor/trusted-devices/'.$othersDevice->id)->assertNotFound();
        $this->deleteJson('/api/auth/two-factor/trusted-devices/'.$mine->id)->assertOk();
        $this->assertSame(1, $user->trustedDevices()->count());

        $this->deleteJson('/api/auth/two-factor/trusted-devices')->assertOk()->assertJsonPath('data.revoked', 1);
        $this->assertSame(0, $user->trustedDevices()->count());
        $this->assertSame(1, $other->trustedDevices()->count());
    }

    #[Test]
    public function admin_can_reset_another_account(): void
    {
        $target = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $target->createToken('auth_token');
        $admin = $this->actingAsAdmin();

        $this->postJson('/api/admin/users/'.$target->id.'/two-factor/reset')
            ->assertOk()
            ->assertJsonPath('data.two_factor_enabled', false);

        $target->refresh();
        $this->assertFalse($target->hasTwoFactorEnabled());
        $this->assertSame(0, $target->tokens()->count());

        $this->postJson('/api/admin/users/'.$admin->id.'/two-factor/reset')->assertStatus(422);
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $this->postJson('/api/admin/users/999999/two-factor/reset')->assertNotFound();
    }

    #[Test]
    public function only_admins_can_reset(): void
    {
        $target = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $this->club();

        $this->postJson('/api/admin/users/'.$target->id.'/two-factor/reset')->assertForbidden();
        $this->assertTrue($target->fresh()->hasTwoFactorEnabled());
    }
}
