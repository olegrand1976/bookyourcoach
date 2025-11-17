<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ClubMiddleware;
use App\Models\User;
use App\Models\Club;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour ClubMiddleware
 * 
 * Ce fichier teste les fonctionnalités du middleware ClubMiddleware :
 * - Autorisation des utilisateurs avec le rôle club
 * - Refus des utilisateurs avec d'autres rôles
 * - Gestion des utilisateurs non authentifiés (401)
 * - Support des utilisateurs avec plusieurs associations de club
 * 
 * Note : Ce middleware protège les routes club et vérifie que l'utilisateur
 *        authentifié a le rôle 'club'.
 */
class ClubMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Autorisation d'un utilisateur club avec association
     * 
     * BUT : Vérifier qu'un utilisateur avec le rôle club peut accéder aux routes protégées
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Une association avec un club
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - La requête passe au middleware suivant
     * 
     * POURQUOI : Les utilisateurs club doivent pouvoir accéder aux routes club.
     */
    #[Test]
    public function it_allows_club_user_with_club_association()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        $club->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($clubUser) {
            return $clubUser;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un utilisateur admin
     * 
     * BUT : Vérifier qu'un admin ne peut pas accéder aux routes club
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'admin'
     * - Une association avec un club
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Le middleware actuel vérifie uniquement le rôle 'club',
     *            donc les admins sont refusés même s'ils sont associés à un club.
     */
    #[Test]
    public function it_denies_admin_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $club = Club::factory()->create();
        
        $club->users()->attach($admin->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Note : Le middleware actuel ne permet que le rôle 'club', donc les admins sont refusés
        // Si vous voulez permettre les admins, modifiez le middleware
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un utilisateur non authentifié
     * 
     * BUT : Vérifier qu'un utilisateur non authentifié est refusé
     * 
     * ENTRÉE : 
     * - Une requête sans utilisateur authentifié
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * - Message JSON indiquant que l'utilisateur n'est pas authentifié
     * 
     * POURQUOI : Les routes club nécessitent une authentification. Sans utilisateur,
     *            l'accès est refusé avec 401.
     */
    #[Test]
    public function it_denies_unauthenticated_user()
    {
        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Unauthenticated', $data['message']);
    }

    /**
     * Test : Refus d'un utilisateur étudiant
     * 
     * BUT : Vérifier qu'un étudiant ne peut pas accéder aux routes club
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * - Message JSON indiquant que l'accès est refusé
     * 
     * POURQUOI : Seuls les utilisateurs avec le rôle 'club' doivent accéder aux routes club.
     */
    #[Test]
    public function it_denies_student_user()
    {
        $student = User::factory()->create(['role' => 'student']);

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($student) {
            return $student;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Unauthorized', $data['message']);
    }

    /**
     * Test : Refus d'un utilisateur enseignant
     * 
     * BUT : Vérifier qu'un enseignant ne peut pas accéder aux routes club
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'teacher'
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Seuls les utilisateurs avec le rôle 'club' doivent accéder aux routes club.
     */
    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($teacher) {
            return $teacher;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * Test : Autorisation d'un utilisateur club même sans association explicite
     * 
     * BUT : Vérifier qu'un utilisateur club peut accéder même sans association dans club_user
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Pas d'association explicite avec un club
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * 
     * POURQUOI : Le middleware vérifie uniquement le rôle, pas l'association avec un club.
     *            Si vous voulez vérifier l'association, modifiez le middleware.
     */
    #[Test]
    public function it_allows_club_user_without_club_association()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        // Ne pas associer l'utilisateur à un club

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($clubUser) {
            return $clubUser;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Le middleware actuel vérifie uniquement le rôle, pas l'association
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un admin sans association club
     * 
     * BUT : Vérifier qu'un admin sans association club est refusé
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'admin'
     * - Pas d'association avec un club
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Le middleware vérifie uniquement le rôle 'club', donc les admins sont refusés.
     */
    #[Test]
    public function it_denies_admin_user_without_club_association()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Ne pas associer l'admin à un club

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * Test : Autorisation d'un utilisateur avec plusieurs associations de club
     * 
     * BUT : Vérifier qu'un utilisateur peut avoir plusieurs associations de club
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Plusieurs associations avec différents clubs
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * 
     * POURQUOI : Un utilisateur peut être associé à plusieurs clubs.
     */
    #[Test]
    public function it_allows_club_user_with_multiple_club_associations()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();
        
        $club1->users()->attach($clubUser->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        $club2->users()->attach($clubUser->id, [
            'role' => 'manager',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () use ($clubUser) {
            return $clubUser;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Autorisation avec différents rôles dans le club
     * 
     * BUT : Vérifier qu'un utilisateur peut avoir différents rôles dans différents clubs
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Différents rôles dans le club (owner, manager, member)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200 pour tous les rôles
     * 
     * POURQUOI : Le middleware vérifie uniquement le rôle utilisateur, pas le rôle dans le club.
     */
    #[Test]
    public function it_allows_club_user_with_different_roles_in_clubs()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        // Tester différents rôles dans le club
        $roles = ['owner', 'manager', 'member'];
        
        $middleware = new ClubMiddleware();
        
        foreach ($roles as $role) {
            $club->users()->detach($clubUser->id);
            $club->users()->attach($clubUser->id, [
                'role' => $role,
                'is_admin' => $role === 'owner',
                'joined_at' => now()
            ]);

            $request = Request::create('/api/club/dashboard');
            $request->setUserResolver(function () use ($clubUser) {
                return $clubUser;
            });

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            $this->assertEquals(200, $response->getStatusCode(), "Failed for role: {$role}");
        }
    }
}
