<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Garde-fous des connexions, ajoutés après l'incident du 2026-09-23 : l'identifiant
 * d'un compte club réel était publié dans un dépôt public, sans limitation de
 * tentatives, avec des jetons qui n'expiraient jamais.
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $password = 'MotDePasseSolide2026'): User
    {
        return User::factory()->create([
            'email' => 'gerant@club.test',
            'password' => Hash::make($password),
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function la_connexion_est_bloquee_apres_plusieurs_echecs(): void
    {
        $this->makeUser();
        $max = (int) config('bookyourcoach.auth.login_max_attempts', 5);

        for ($i = 0; $i < $max; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'gerant@club.test',
                'password' => 'mauvais-mot-de-passe',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'gerant@club.test',
            'password' => 'mauvais-mot-de-passe',
        ])->assertStatus(429);
    }

    #[Test]
    public function le_blocage_ne_verrouille_pas_les_autres_comptes(): void
    {
        $this->makeUser();
        User::factory()->create([
            'email' => 'autre@club.test',
            'password' => Hash::make('MotDePasseSolide2026'),
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);

        $max = (int) config('bookyourcoach.auth.login_max_attempts', 5);
        for ($i = 0; $i <= $max; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'gerant@club.test',
                'password' => 'mauvais-mot-de-passe',
            ]);
        }

        // Le compteur est indexé sur le couple e-mail + IP : harceler un compte
        // ne doit pas condamner la connexion d'un autre.
        $this->postJson('/api/auth/login', [
            'email' => 'autre@club.test',
            'password' => 'MotDePasseSolide2026',
        ])->assertStatus(200);
    }

    #[Test]
    public function un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->postJson('/api/auth/register', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    #[Test]
    public function changer_de_mot_de_passe_revoque_les_autres_jetons(): void
    {
        $user = $this->makeUser();

        $ancienJeton = $user->createToken('telephone')->plainTextToken;
        $jetonCourant = $user->createToken('navigateur')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$jetonCourant)
            ->putJson('/api/auth/change-password', [
                'current_password' => 'MotDePasseSolide2026',
                'password' => 'NouveauSecret2026x',
                'password_confirmation' => 'NouveauSecret2026x',
            ])->assertStatus(200);

        // L'application est partagée entre deux requêtes d'un même test : sans cet oubli,
        // le garde Sanctum réutilise l'utilisateur déjà résolu et la requête suivante
        // passerait quel que soit le jeton présenté.
        $this->app['auth']->forgetGuards();

        // Le jeton resté sur un autre appareil ne doit plus ouvrir de session.
        $this->withHeader('Authorization', 'Bearer '.$ancienJeton)
            ->getJson('/api/auth/user')->assertStatus(401);

        $this->app['auth']->forgetGuards();

        // Celui qui vient de changer son mot de passe n'est pas déconnecté.
        $this->withHeader('Authorization', 'Bearer '.$jetonCourant)
            ->getJson('/api/auth/user')->assertStatus(200);
    }

    #[Test]
    public function un_jeton_dormant_n_est_plus_accepte(): void
    {
        $user = $this->makeUser();
        $jeton = $user->createToken('vieux-telephone')->plainTextToken;
        $idleDays = (int) config('bookyourcoach.auth.token_idle_days', 30);

        DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->update([
                'last_used_at' => now()->subDays($idleDays + 1),
                'created_at' => now()->subDays($idleDays + 1),
            ]);

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/auth/user')->assertStatus(401);
    }

    #[Test]
    public function un_jeton_utilise_recemment_reste_valide(): void
    {
        $user = $this->makeUser();
        $jeton = $user->createToken('telephone')->plainTextToken;

        DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->update(['last_used_at' => now()->subDays(2), 'created_at' => now()->subYear()]);

        // Créé il y a un an mais utilisé avant-hier : l'expiration est glissante.
        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/auth/user')->assertStatus(200);
    }

    #[Test]
    public function la_route_de_debug_utilisateur_n_existe_plus(): void
    {
        $user = $this->makeUser();
        $jeton = $user->createToken('navigateur')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/auth/debug-user')->assertStatus(404);
    }

    #[Test]
    public function la_purge_supprime_les_jetons_dormants(): void
    {
        $user = $this->makeUser();
        $user->createToken('actif');
        $user->createToken('dormant');

        $idleDays = (int) config('bookyourcoach.auth.token_idle_days', 30);
        DB::table('personal_access_tokens')
            ->where('name', 'dormant')
            ->update(['last_used_at' => now()->subDays($idleDays + 5)]);

        $this->artisan('auth:prune-idle-tokens', ['--dry-run' => true])->assertSuccessful();
        $this->assertEquals(2, DB::table('personal_access_tokens')->count());

        $this->artisan('auth:prune-idle-tokens')->assertSuccessful();
        $this->assertEquals(1, DB::table('personal_access_tokens')->count());
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'actif']);
    }

    #[Test]
    public function l_audit_signale_un_mot_de_passe_divulgue_sans_rien_modifier(): void
    {
        $faible = User::factory()->create([
            'email' => 'faible@club.test',
            'password' => Hash::make('password123'),
        ]);
        $faible->createToken('telephone');
        $this->makeUser();

        // Sans option : lecture seule, mais code de sortie en échec pour être visible en CI.
        $this->artisan('auth:audit-weak-passwords')->assertFailed();

        $this->assertEquals(1, DB::table('personal_access_tokens')->where('tokenable_id', $faible->id)->count());
    }

    #[Test]
    public function l_audit_revoque_les_jetons_des_comptes_vulnerables(): void
    {
        $faible = User::factory()->create([
            'email' => 'faible@club.test',
            'password' => Hash::make('password'),
        ]);
        $faible->createToken('telephone');

        $solide = $this->makeUser();
        $solide->createToken('navigateur');

        $this->artisan('auth:audit-weak-passwords', ['--revoke' => true])->assertFailed();

        $this->assertEquals(0, DB::table('personal_access_tokens')->where('tokenable_id', $faible->id)->count());
        $this->assertEquals(1, DB::table('personal_access_tokens')->where('tokenable_id', $solide->id)->count());
    }

    #[Test]
    public function revoke_tokens_deconnecte_un_compte_par_son_email(): void
    {
        $user = $this->makeUser();
        $user->createToken('telephone');
        $user->createToken('navigateur');

        $this->artisan('auth:revoke-tokens', ['user' => 'gerant@club.test'])->assertSuccessful();

        $this->assertEquals(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
    }
}
