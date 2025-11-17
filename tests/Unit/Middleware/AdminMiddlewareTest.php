<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour AdminMiddleware
 * 
 * Ce fichier teste les fonctionnalités du middleware AdminMiddleware :
 * - Autorisation des utilisateurs avec le rôle admin
 * - Refus des utilisateurs avec d'autres rôles
 * - Gestion des utilisateurs non authentifiés (401)
 * - Gestion des tokens invalides ou manquants
 * 
 * Note : Ce middleware protège les routes admin et vérifie que l'utilisateur
 *        authentifié a le rôle 'admin'.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Autorisation d'un utilisateur admin avec token valide
     * 
     * BUT : Vérifier qu'un utilisateur avec le rôle admin peut accéder aux routes protégées
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'admin'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - La requête passe au middleware suivant
     * 
     * POURQUOI : Les administrateurs doivent pouvoir accéder aux routes admin.
     */
    #[Test]
    public function it_allows_admin_user_with_valid_token()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un utilisateur non-admin avec token valide
     * 
     * BUT : Vérifier qu'un utilisateur avec un autre rôle ne peut pas accéder
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * - Message JSON avec erreur d'accès refusé
     * 
     * POURQUOI : Seuls les administrateurs doivent accéder aux routes admin.
     */
    #[Test]
    public function it_denies_non_admin_user_with_valid_token()
    {
        $user = User::factory()->create(['role' => 'student']);

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
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
     * Test : Refus d'une requête sans token
     * 
     * BUT : Vérifier qu'une requête sans authentification est refusée
     * 
     * ENTRÉE : 
     * - Une requête sans header Authorization
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * - Message JSON indiquant que le token est manquant
     * 
     * POURQUOI : Les routes admin nécessitent une authentification. Sans token,
     *            l'utilisateur n'est pas authentifié (401).
     */
    #[Test]
    public function it_denies_request_without_token()
    {
        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');

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
     * Test : Refus d'une requête avec format de token invalide
     * 
     * BUT : Vérifier qu'un header Authorization mal formaté est rejeté
     * 
     * ENTRÉE : 
     * - Un header Authorization sans "Bearer "
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * - Message JSON indiquant que le token est manquant
     * 
     * POURQUOI : Le format Bearer token est requis. Un format invalide signifie
     *            qu'il n'y a pas de token valide (401).
     */
    #[Test]
    public function it_denies_request_with_invalid_token_format()
    {
        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'InvalidToken');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * Test : Refus d'une requête avec token invalide
     * 
     * BUT : Vérifier qu'un token invalide est rejeté
     * 
     * ENTRÉE : 
     * - Un header Authorization avec un token invalide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * - Message JSON indiquant que le token est invalide
     * 
     * POURQUOI : Un token invalide signifie que l'utilisateur n'est pas authentifié (401).
     *            Note : Dans les tests unitaires, Sanctum ne valide pas réellement le token,
     *            donc ce test vérifie principalement le comportement du middleware.
     */
    #[Test]
    public function it_denies_request_with_invalid_token()
    {
        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * Test : Refus d'un utilisateur enseignant
     * 
     * BUT : Vérifier qu'un enseignant ne peut pas accéder aux routes admin
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'teacher'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Seuls les administrateurs doivent accéder aux routes admin.
     */
    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->setUserResolver(function () use ($teacher) {
            return $teacher;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un utilisateur club
     * 
     * BUT : Vérifier qu'un utilisateur club ne peut pas accéder aux routes admin
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Seuls les administrateurs doivent accéder aux routes admin.
     */
    #[Test]
    public function it_denies_club_user()
    {
        $clubUser = User::factory()->create(['role' => 'club']);

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->setUserResolver(function () use ($clubUser) {
            return $clubUser;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test : Gestion d'un header Authorization malformé
     * 
     * BUT : Vérifier qu'un header Authorization incomplet est rejeté
     * 
     * ENTRÉE : 
     * - Un header Authorization avec seulement "Bearer" sans token
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * 
     * POURQUOI : Un header malformé signifie qu'il n'y a pas de token valide (401).
     */
    #[Test]
    public function it_handles_malformed_authorization_header()
    {
        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test : Gestion d'un header Authorization vide
     * 
     * BUT : Vérifier qu'un header Authorization vide est rejeté
     * 
     * ENTRÉE : 
     * - Un header Authorization vide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 401
     * 
     * POURQUOI : Un header vide signifie qu'il n'y a pas de token (401).
     */
    #[Test]
    public function it_handles_empty_authorization_header()
    {
        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', '');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }
}
