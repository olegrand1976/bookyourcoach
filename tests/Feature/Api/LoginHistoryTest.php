<?php

namespace Tests\Feature\Api;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\ClientIpResolver;
use App\Services\LoginAttemptRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Traçabilité des connexions. Le 2026-09-23, reconstituer un incident a exigé les
 * journaux de l'hébergeur : l'application enregistrait une adresse IP inutilisable,
 * celle du relais interne, identique pour tout le monde.
 */
class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(string $email = 'gerant@club.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make('MotDePasseSolide2026'),
            'role' => 'club',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /** Requête telle qu'elle arrive derrière le load balancer Google. */
    private function requeteDerriereProxy(string $chaine): Request
    {
        $request = Request::create('/api/auth/login', 'POST');
        $request->headers->set('X-Forwarded-For', $chaine);
        $request->server->set('REMOTE_ADDR', '169.254.169.126');

        return $request;
    }

    #[Test]
    public function l_adresse_retenue_est_celle_du_visiteur_pas_celle_du_relais(): void
    {
        $resolver = app(ClientIpResolver::class);

        // « client, IP client ajoutée par le LB, IP du LB » : la bonne est l'avant-dernière.
        $this->assertEquals(
            '94.109.66.137',
            $resolver->resolve($this->requeteDerriereProxy('94.109.66.137, 34.54.99.89'))
        );
    }

    #[Test]
    public function une_chaine_forgee_par_le_client_n_est_pas_retenue(): void
    {
        $resolver = app(ClientIpResolver::class);

        // Le client prétend venir de 1.2.3.4 ; le LB ajoute ensuite sa vraie adresse
        // puis la sienne. On ne doit pas retenir la valeur de gauche.
        $ip = $resolver->resolve($this->requeteDerriereProxy('1.2.3.4, 94.109.66.137, 34.54.99.89'));

        $this->assertEquals('94.109.66.137', $ip);
        $this->assertNotEquals('1.2.3.4', $ip);
    }

    #[Test]
    public function sans_en_tete_on_retombe_sur_l_adresse_directe(): void
    {
        $request = Request::create('/api/auth/login', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.7');

        $this->assertEquals('203.0.113.7', app(ClientIpResolver::class)->resolve($request));
    }

    #[Test]
    public function une_connexion_reussie_est_enregistree_avec_son_contexte(): void
    {
        $user = $this->utilisateur();

        $this->withHeaders([
            'X-Forwarded-For' => '94.109.66.137, 34.54.99.89',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6.1 Mobile/15E148 Safari/604.1',
        ])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'MotDePasseSolide2026',
        ])->assertStatus(200);

        $attempt = LoginAttempt::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($attempt->successful);
        $this->assertEquals('94.109.66.137', $attempt->ip_address);
        $this->assertEquals('94.109.66.137, 34.54.99.89', $attempt->forwarded_for);
        $this->assertEquals('mobile', $attempt->device_type);
        $this->assertEquals('Safari', $attempt->browser);
        $this->assertEquals('iOS', $attempt->platform);
    }

    #[Test]
    public function une_tentative_refusee_est_rattachee_au_compte_vise(): void
    {
        $user = $this->utilisateur();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'mauvais',
        ])->assertStatus(401);

        $attempt = LoginAttempt::where('email', $user->email)->firstOrFail();

        // Rattachée au compte : c'est ce qui permet à quelqu'un de voir
        // qu'on a essayé d'entrer chez lui.
        $this->assertEquals($user->id, $attempt->user_id);
        $this->assertFalse($attempt->successful);
        $this->assertEquals(LoginAttempt::REASON_INVALID_CREDENTIALS, $attempt->failure_reason);
    }

    #[Test]
    public function une_tentative_sur_une_adresse_inconnue_est_conservee_sans_compte(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'inconnu@example.test',
            'password' => 'peu importe',
        ])->assertStatus(401);

        $attempt = LoginAttempt::where('email', 'inconnu@example.test')->firstOrFail();

        $this->assertNull($attempt->user_id);
        $this->assertFalse($attempt->successful);
    }

    #[Test]
    public function chacun_consulte_son_propre_historique(): void
    {
        $user = $this->utilisateur();
        $jeton = $user->createToken('navigateur')->plainTextToken;
        LoginAttempt::create(['user_id' => $user->id, 'successful' => true, 'ip_address' => '94.109.66.137', 'city' => 'Bruxelles', 'country' => 'Belgique']);

        $response = $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/auth/login-history');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.ip_address', '94.109.66.137')
            ->assertJsonPath('data.0.location.label', 'Bruxelles, Belgique')
            ->assertJsonPath('meta.retention_days', 365);
    }

    #[Test]
    public function l_historique_d_autrui_n_est_pas_accessible(): void
    {
        $user = $this->utilisateur();
        $autre = $this->utilisateur('autre@club.test');
        LoginAttempt::create(['user_id' => $autre->id, 'successful' => true, 'ip_address' => '203.0.113.9']);

        $jeton = $user->createToken('navigateur')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer '.$jeton)->getJson('/api/auth/login-history');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }

    #[Test]
    public function les_details_d_enquete_restent_reserves_aux_administrateurs(): void
    {
        $user = $this->utilisateur();
        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => false,
            'ip_address' => '94.109.66.137',
            'forwarded_for' => '1.2.3.4, 94.109.66.137, 34.54.99.89',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $jeton = $user->createToken('navigateur')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/auth/login-history')
            ->assertStatus(200)
            ->assertJsonMissingPath('data.0.forwarded_for')
            ->assertJsonMissingPath('data.0.user_agent');
    }

    #[Test]
    public function un_administrateur_consulte_l_historique_d_un_compte(): void
    {
        $user = $this->utilisateur();
        LoginAttempt::create(['user_id' => $user->id, 'successful' => true, 'ip_address' => '94.109.66.137', 'forwarded_for' => '94.109.66.137, 34.54.99.89']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'is_active' => true]);
        $jeton = $admin->createToken('navigateur')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson("/api/admin/users/{$user->id}/login-history")
            ->assertStatus(200)
            ->assertJsonPath('data.0.forwarded_for', '94.109.66.137, 34.54.99.89');
    }

    #[Test]
    public function la_purge_respecte_la_duree_de_conservation(): void
    {
        $user = $this->utilisateur();
        $recente = LoginAttempt::create(['user_id' => $user->id, 'successful' => true]);
        $ancienne = LoginAttempt::create(['user_id' => $user->id, 'successful' => true]);
        $ancienne->forceFill(['created_at' => now()->subDays(400)])->save();

        $this->artisan('auth:prune-login-history', ['--dry-run' => true])->assertSuccessful();
        $this->assertEquals(2, LoginAttempt::count());

        $this->artisan('auth:prune-login-history')->assertSuccessful();
        $this->assertEquals(1, LoginAttempt::count());
        $this->assertTrue(LoginAttempt::whereKey($recente->id)->exists());
    }

    #[Test]
    public function la_lecture_de_l_agent_distingue_les_navigateurs_qui_se_deguisent(): void
    {
        $recorder = app(LoginAttemptRecorder::class);

        // Edge et Chrome se présentent tous deux comme Chrome et Safari :
        // l'ordre de détection doit les départager.
        $edge = $recorder->summariseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0');
        $this->assertEquals(['device_type' => 'ordinateur', 'browser' => 'Edge', 'platform' => 'Windows'], $edge);

        $chrome = $recorder->summariseUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
        $this->assertEquals('Chrome', $chrome['browser']);

        $this->assertEquals(
            ['device_type' => null, 'browser' => null, 'platform' => null],
            $recorder->summariseUserAgent('')
        );
    }

    #[Test]
    public function une_adresse_privee_n_est_pas_geolocalisee(): void
    {
        $geo = app(\App\Services\GeoLocationResolver::class);

        $this->assertNull($geo->locate('192.168.1.10')['country']);
        $this->assertNull($geo->locate('pas une adresse')['country']);
        $this->assertNull($geo->locate(null)['country']);
    }

    #[Test]
    public function une_chaine_plus_courte_que_prevu_ne_fait_pas_confiance_au_client(): void
    {
        // Un seul maillon alors qu'on en attend deux : la requête n'est pas passée
        // par le load balancer. La valeur restante est écrite par le client, donc
        // inutilisable comme preuve — on retombe sur le pair direct.
        $request = $this->requeteDerriereProxy('1.2.3.4');

        $ip = app(ClientIpResolver::class)->resolve($request);

        $this->assertEquals('169.254.169.126', $ip);
        $this->assertNotEquals('1.2.3.4', $ip);
    }

    #[Test]
    public function une_adresse_avec_port_est_normalisee(): void
    {
        $resolver = app(ClientIpResolver::class);

        $this->assertEquals(
            '94.109.66.137',
            $resolver->resolve($this->requeteDerriereProxy('94.109.66.137:51514, 34.54.99.89'))
        );
        $this->assertEquals(
            '2a02:1811:c7d:2f00::1',
            $resolver->resolve($this->requeteDerriereProxy('[2a02:1811:c7d:2f00::1]:443, 34.54.99.89'))
        );
    }

    #[Test]
    public function une_localisation_trop_longue_est_tronquee_plutot_que_perdue(): void
    {
        // En MySQL strict, une valeur plus longue que la colonne ferait échouer
        // l'insertion ; l'écriture étant silencieuse, la connexion ne serait pas
        // tracée du tout. On préfère tronquer.
        $recorder = app(LoginAttemptRecorder::class);
        $methode = new \ReflectionMethod($recorder, 'fitToColumns');
        $methode->setAccessible(true);

        $ajuste = $methode->invoke($recorder, [
            'country_code' => 'BEL',
            'country' => str_repeat('a', 150),
            'region' => null,
            'city' => str_repeat('b', 150),
            'time_zone' => str_repeat('c', 90),
            'organisation' => str_repeat('d', 200),
            'accuracy_radius_km' => 99999,
        ]);

        $this->assertEquals(2, mb_strlen($ajuste['country_code']));
        $this->assertEquals(100, mb_strlen($ajuste['country']));
        $this->assertEquals(100, mb_strlen($ajuste['city']));
        $this->assertEquals(64, mb_strlen($ajuste['time_zone']));
        $this->assertEquals(150, mb_strlen($ajuste['organisation']));
        $this->assertNull($ajuste['accuracy_radius_km']);
        $this->assertNull($ajuste['region']);
    }

    #[Test]
    public function une_localisation_hors_norme_n_empeche_pas_la_connexion(): void
    {
        $user = $this->utilisateur();

        // Bout en bout, avec des valeurs extrêmes en en-tête : la connexion doit
        // aboutir et la trace exister.
        $this->withHeaders([
            'X-Forwarded-For' => str_repeat('94.109.66.137, ', 60).'34.54.99.89',
            'User-Agent' => str_repeat('A', 3000),
        ])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'MotDePasseSolide2026',
        ])->assertStatus(200);

        $attempt = LoginAttempt::where('user_id', $user->id)->firstOrFail();
        $this->assertLessThanOrEqual(500, mb_strlen((string) $attempt->forwarded_for));
        $this->assertLessThanOrEqual(1000, mb_strlen((string) $attempt->user_agent));
    }

    #[Test]
    public function les_resolveurs_sont_partages_dans_la_requete(): void
    {
        // Un lecteur GeoLite2 par tentative rouvrirait le fichier à chaque fois.
        $this->assertSame(app(ClientIpResolver::class), app(ClientIpResolver::class));
        $this->assertSame(
            app(\App\Services\GeoLocationResolver::class),
            app(\App\Services\GeoLocationResolver::class)
        );
    }
}
