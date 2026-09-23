<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorService $service;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TwoFactorService::class);
        $this->google2fa = app(Google2FA::class);
    }

    #[Test]
    public function only_club_and_admin_require_two_factor(): void
    {
        $this->assertTrue(User::factory()->make(['role' => User::ROLE_CLUB])->requiresTwoFactor());
        $this->assertTrue(User::factory()->make(['role' => User::ROLE_ADMIN])->requiresTwoFactor());
        $this->assertFalse(User::factory()->make(['role' => User::ROLE_TEACHER])->requiresTwoFactor());
        $this->assertFalse(User::factory()->make(['role' => User::ROLE_STUDENT])->requiresTwoFactor());
    }

    #[Test]
    public function emergency_switch_disables_the_requirement(): void
    {
        config(['bookyourcoach.auth.two_factor_enforced' => false]);

        $this->assertFalse(User::factory()->make(['role' => User::ROLE_ADMIN])->requiresTwoFactor());
    }

    #[Test]
    public function enrolment_activates_the_secret_only_after_a_valid_code(): void
    {
        $user = User::factory()->withoutTwoFactor()->create(['role' => User::ROLE_CLUB]);

        $enrolment = $this->service->startEnrolment($user);

        $this->assertStringContainsString('<svg', $enrolment['qr_svg']);
        $this->assertStringStartsWith('otpauth://totp/', $enrolment['otpauth_url']);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled(), 'Le secret en attente ne doit pas être actif');

        $this->assertNull($this->service->confirmEnrolment($user, '000000'));

        $codes = $this->service->confirmEnrolment($user, $this->google2fa->getCurrentOtp($enrolment['secret']));

        $this->assertCount(TwoFactorService::RECOVERY_CODES_COUNT, $codes);
        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertSame($enrolment['secret'], $user->two_factor_secret);
        $this->assertFalse($this->service->hasPendingEnrolment($user));
    }

    #[Test]
    public function secret_is_encrypted_at_rest_and_hidden_from_json(): void
    {
        $secret = $this->google2fa->generateSecretKey(32);
        $user = User::factory()->withTwoFactor($secret)->create(['role' => User::ROLE_CLUB]);

        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
    }

    #[Test]
    public function a_code_cannot_be_replayed(): void
    {
        $secret = $this->google2fa->generateSecretKey(32);
        $user = User::factory()->withTwoFactor($secret)->create(['role' => User::ROLE_CLUB]);
        $code = $this->google2fa->getCurrentOtp($secret);

        $this->assertTrue($this->service->verifyCode($user, $code));
        $this->assertFalse($this->service->verifyCode($user, $code));
    }

    #[Test]
    public function malformed_codes_are_rejected(): void
    {
        $user = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);

        $this->assertFalse($this->service->verifyCode($user, 'abcdef'));
        $this->assertFalse($this->service->verifyCode($user, '12345'));
        $this->assertFalse($this->service->verifyCode(User::factory()->create(), '123456'));
    }

    #[Test]
    public function recovery_codes_are_single_use_and_tolerant_to_formatting(): void
    {
        $user = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $codes = $this->service->regenerateRecoveryCodes($user);

        $this->assertTrue($this->service->useRecoveryCode($user, strtoupper(str_replace('-', ' ', $codes[0]))));
        $this->assertFalse($this->service->useRecoveryCode($user->fresh(), $codes[0]));
        $this->assertSame(TwoFactorService::RECOVERY_CODES_COUNT - 1, $this->service->remainingRecoveryCodes($user->fresh()));
    }

    #[Test]
    public function challenge_is_bound_to_its_mode_and_dies_after_too_many_failures(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLUB]);
        $token = $this->service->createChallenge($user, TwoFactorService::MODE_CHALLENGE);

        $this->assertTrue($user->is($this->service->challengeUser($token, TwoFactorService::MODE_CHALLENGE)));
        $this->assertNull($this->service->challengeUser($token, TwoFactorService::MODE_SETUP));
        $this->assertNull($this->service->challengeUser('inconnu', TwoFactorService::MODE_CHALLENGE));

        for ($i = 1; $i < TwoFactorService::MAX_CHALLENGE_FAILURES; $i++) {
            $this->assertSame(TwoFactorService::MAX_CHALLENGE_FAILURES - $i, $this->service->recordChallengeFailure($token));
        }
        $this->assertSame(0, $this->service->recordChallengeFailure($token));
        $this->assertNull($this->service->challengeUser($token, TwoFactorService::MODE_CHALLENGE));
    }

    #[Test]
    public function challenge_expires(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLUB]);
        $token = $this->service->createChallenge($user, TwoFactorService::MODE_CHALLENGE);

        $this->travel(11)->minutes();

        $this->assertNull($this->service->challengeUser($token, TwoFactorService::MODE_CHALLENGE));
    }

    #[Test]
    public function trusted_device_is_recognised_until_it_expires(): void
    {
        $user = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $other = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $token = $this->service->issueTrustedDevice($user, Request::create('/', 'POST'));

        $this->assertDatabaseMissing('trusted_devices', ['token_hash' => $token]);
        $this->assertTrue($this->service->isTrustedDevice($user, $token));
        $this->assertFalse($this->service->isTrustedDevice($other, $token), 'Un jeton ne vaut que pour son compte');
        $this->assertFalse($this->service->isTrustedDevice($user, null));

        $this->travel(31)->days();

        $this->assertFalse($this->service->isTrustedDevice($user, $token));
    }

    #[Test]
    public function reset_clears_second_factor_devices_and_sessions(): void
    {
        $user = User::factory()->withTwoFactor()->create(['role' => User::ROLE_CLUB]);
        $user->createToken('auth_token');
        $this->service->issueTrustedDevice($user, Request::create('/', 'POST'));

        $this->service->reset($user);

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertSame(0, $user->trustedDevices()->count());
        $this->assertSame(0, $user->tokens()->count());
    }
}
