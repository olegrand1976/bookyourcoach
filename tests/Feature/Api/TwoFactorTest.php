<?php

namespace Tests\Feature\Api;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Double authentification obligatoire des comptes club et admin.
 */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasseSolide2026';

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->google2fa = app(Google2FA::class);
    }

    private function account(string $role, ?string $secret = null): User
    {
        $factory = User::factory();
        if ($secret) {
            $factory = $factory->withTwoFactor($secret);
        }

        return $factory->create([
            'email' => $role.'@club.test',
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function login(User $user, array $extra = []): TestResponse
    {
        return $this->postJson('/api/auth/login', array_merge([
            'email' => $user->email,
            'password' => self::PASSWORD,
        ], $extra));
    }

    #[Test]
    public function teacher_and_student_still_log_in_in_one_step(): void
    {
        foreach ([User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $this->login($this->account($role))
                ->assertOk()
                ->assertJsonStructure(['access_token', 'user']);
        }
    }

    #[Test]
    public function club_without_two_factor_must_enrol_before_getting_a_token(): void
    {
        $user = $this->account(User::ROLE_CLUB);

        $login = $this->login($user)
            ->assertOk()
            ->assertJsonPath('data.two_factor_setup_required', true)
            ->assertJsonMissingPath('access_token');
        $challenge = $login->json('data.challenge_token');
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, LoginAttempt::where('successful', true)->count(), 'Le mot de passe seul n\'est pas une connexion');

        $setup = $this->postJson('/api/auth/two-factor/setup', ['challenge_token' => $challenge])
            ->assertOk()
            ->assertJsonStructure(['data' => ['qr_svg', 'secret', 'otpauth_url']]);

        $this->postJson('/api/auth/two-factor/setup/confirm', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ])->assertStatus(422)->assertJsonPath('data.remaining_attempts', TwoFactorService::MAX_CHALLENGE_FAILURES - 1);

        $this->postJson('/api/auth/two-factor/setup/confirm', [
            'challenge_token' => $challenge,
            'code' => $this->google2fa->getCurrentOtp($setup->json('data.secret')),
        ])->assertOk()
            ->assertJsonCount(TwoFactorService::RECOVERY_CODES_COUNT, 'data.recovery_codes')
            ->assertJsonStructure(['data' => ['access_token', 'user']])
            ->assertJsonMissingPath('data.user.two_factor_secret');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame(1, LoginAttempt::where('user_id', $user->id)->where('successful', true)->count());

        // Le challenge a servi : il ne rouvre rien.
        $this->postJson('/api/auth/two-factor/setup', ['challenge_token' => $challenge])->assertStatus(401);
    }

    #[Test]
    public function admin_with_two_factor_logs_in_with_a_code_that_cannot_be_replayed(): void
    {
        $secret = $this->google2fa->generateSecretKey(32);
        $user = $this->account(User::ROLE_ADMIN, $secret);

        $challenge = $this->login($user)
            ->assertOk()
            ->assertJsonPath('data.two_factor_required', true)
            ->json('data.challenge_token');

        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'code' => '000000'])
            ->assertStatus(422);
        $this->assertSame(1, LoginAttempt::where('failure_reason', LoginAttempt::REASON_INVALID_TWO_FACTOR)->count());

        $code = $this->google2fa->getCurrentOtp($secret);
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'code' => $code])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'user']])
            ->assertJsonMissingPath('data.device_token');

        // Même code sur un nouveau challenge : refusé.
        $second = $this->login($user)->json('data.challenge_token');
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $second, 'code' => $code])
            ->assertStatus(422);
    }

    #[Test]
    public function a_setup_challenge_cannot_be_used_to_skip_the_code(): void
    {
        $user = $this->account(User::ROLE_CLUB, $this->google2fa->generateSecretKey(32));
        $challenge = $this->login($user)->json('data.challenge_token');

        // Un compte déjà enrôlé ne peut pas se ré-enrôler avec le seul mot de passe.
        $this->postJson('/api/auth/two-factor/setup', ['challenge_token' => $challenge])->assertStatus(401);
    }

    #[Test]
    public function recovery_code_is_single_use(): void
    {
        $user = $this->account(User::ROLE_CLUB, $this->google2fa->generateSecretKey(32));
        $codes = app(TwoFactorService::class)->regenerateRecoveryCodes($user);

        $challenge = $this->login($user)->json('data.challenge_token');
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'recovery_code' => $codes[0]])
            ->assertOk()
            ->assertJsonPath('data.remaining_recovery_codes', TwoFactorService::RECOVERY_CODES_COUNT - 1);

        $challenge = $this->login($user)->json('data.challenge_token');
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'recovery_code' => $codes[0]])
            ->assertStatus(422);
    }

    #[Test]
    public function trusted_device_skips_the_code_on_next_login(): void
    {
        $secret = $this->google2fa->generateSecretKey(32);
        $user = $this->account(User::ROLE_CLUB, $secret);

        $challenge = $this->login($user)->json('data.challenge_token');
        $deviceToken = $this->postJson('/api/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => $this->google2fa->getCurrentOtp($secret),
            'remember_device' => true,
        ])->assertOk()->json('data.device_token');
        $this->assertNotEmpty($deviceToken);

        $this->login($user, ['device_token' => $deviceToken])
            ->assertOk()
            ->assertJsonStructure(['access_token']);

        // Jeton inconnu : retour au code.
        $this->login($user, ['device_token' => str_repeat('x', 64)])
            ->assertJsonPath('data.two_factor_required', true);

        $user->trustedDevices()->delete();
        $this->login($user, ['device_token' => $deviceToken])
            ->assertJsonPath('data.two_factor_required', true);
    }

    #[Test]
    public function challenge_dies_after_too_many_wrong_codes(): void
    {
        $user = $this->account(User::ROLE_CLUB, $this->google2fa->generateSecretKey(32));
        $challenge = $this->login($user)->json('data.challenge_token');

        for ($i = 1; $i < TwoFactorService::MAX_CHALLENGE_FAILURES; $i++) {
            $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'code' => '000000'])
                ->assertStatus(422);
        }
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $challenge, 'code' => '000000'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'two_factor_challenge_invalid');

        $this->postJson('/api/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => $this->google2fa->getCurrentOtp($user->two_factor_secret),
        ])->assertStatus(401);
    }

    #[Test]
    public function expired_challenge_is_refused(): void
    {
        $user = $this->account(User::ROLE_CLUB, $this->google2fa->generateSecretKey(32));
        $challenge = $this->login($user)->json('data.challenge_token');

        $this->travel(11)->minutes();

        $this->postJson('/api/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => $this->google2fa->getCurrentOtp($user->two_factor_secret),
        ])->assertStatus(401);
    }

    #[Test]
    public function verification_is_rate_limited(): void
    {
        $this->account(User::ROLE_CLUB, $this->google2fa->generateSecretKey(32));
        $bogus = str_repeat('a', 64);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $bogus, 'code' => '000000'])
                ->assertStatus(401);
        }
        $this->postJson('/api/auth/two-factor/challenge', ['challenge_token' => $bogus, 'code' => '000000'])
            ->assertStatus(429);
    }

    #[Test]
    public function club_registration_does_not_issue_a_token(): void
    {
        $this->postJson('/api/auth/register', [
            'first_name' => 'Gérant',
            'last_name' => 'Club',
            'email' => 'nouveau@club.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'role' => 'club',
            'club_name' => 'Club de test',
        ])->assertCreated()
            ->assertJsonPath('data.two_factor_setup_required', true)
            ->assertJsonMissingPath('access_token');

        $this->assertSame(0, User::where('email', 'nouveau@club.test')->firstOrFail()->tokens()->count());
    }

    #[Test]
    public function emergency_switch_restores_password_only_login(): void
    {
        config(['bookyourcoach.auth.two_factor_enforced' => false]);

        $this->login($this->account(User::ROLE_CLUB))
            ->assertOk()
            ->assertJsonStructure(['access_token']);
    }
}
