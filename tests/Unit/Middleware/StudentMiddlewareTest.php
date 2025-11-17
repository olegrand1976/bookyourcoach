<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\StudentMiddleware;
use App\Models\User;
use App\Models\Student;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires pour StudentMiddleware
 * 
 * Ce fichier teste les fonctionnalités du middleware StudentMiddleware :
 * - Autorisation des utilisateurs avec le rôle student
 * - Refus des utilisateurs avec d'autres rôles
 * - Gestion des utilisateurs non authentifiés (401)
 * - Support des différents types de requêtes HTTP
 * 
 * Note : Ce middleware protège les routes student et vérifie que l'utilisateur
 *        authentifié a le rôle 'student'.
 */
class StudentMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Autorisation d'un utilisateur étudiant
     * 
     * BUT : Vérifier qu'un utilisateur avec le rôle student peut accéder aux routes protégées
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * - La requête passe au middleware suivant
     * 
     * POURQUOI : Les étudiants doivent pouvoir accéder aux routes student.
     */
    #[Test]
    public function it_allows_student_user()
    {
        $student = User::factory()->create(['role' => 'student']);
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($student) {
            return $student;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Refus d'un utilisateur admin
     * 
     * BUT : Vérifier qu'un admin ne peut pas accéder aux routes student
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'admin'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Seuls les étudiants doivent accéder aux routes student.
     *            Note : Si vous voulez permettre les admins, modifiez le middleware.
     */
    #[Test]
    public function it_denies_admin_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Le middleware actuel ne permet que le rôle 'student'
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
     * - Message JSON indiquant que le token est manquant
     * 
     * POURQUOI : Les routes student nécessitent une authentification. Sans utilisateur,
     *            l'accès est refusé avec 401.
     */
    #[Test]
    public function it_denies_unauthenticated_user()
    {
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');

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
     * Test : Refus d'un utilisateur enseignant
     * 
     * BUT : Vérifier qu'un enseignant ne peut pas accéder aux routes student
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'teacher'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * - Message JSON indiquant que l'accès est refusé
     * 
     * POURQUOI : Seuls les étudiants doivent accéder aux routes student.
     */
    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($teacher) {
            return $teacher;
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
     * Test : Refus d'un utilisateur club
     * 
     * BUT : Vérifier qu'un utilisateur club ne peut pas accéder aux routes student
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'club'
     * - Un token Sanctum valide
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 403
     * 
     * POURQUOI : Seuls les étudiants doivent accéder aux routes student.
     */
    #[Test]
    public function it_denies_club_user()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($clubUser) {
            return $clubUser;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * Test : Autorisation d'un utilisateur avec profil étudiant
     * 
     * BUT : Vérifier qu'un utilisateur avec un profil Student peut accéder
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * - Un profil Student associé
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * 
     * POURQUOI : Le middleware vérifie uniquement le rôle utilisateur, pas le profil Student.
     *            Ce test vérifie que le profil existe mais n'est pas requis.
     */
    #[Test]
    public function it_allows_user_with_student_profile()
    {
        $user = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $user->id]);
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Gestion d'un utilisateur sans profil étudiant
     * 
     * BUT : Vérifier qu'un utilisateur student peut accéder même sans profil Student
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * - Pas de profil Student associé
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200
     * 
     * POURQUOI : Le middleware vérifie uniquement le rôle, pas le profil Student.
     */
    #[Test]
    public function it_handles_user_without_student_profile()
    {
        $user = User::factory()->create(['role' => 'student']);
        // Ne pas créer de profil étudiant
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Devrait passer car le rôle est student même sans profil
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test : Gestion de différents types de requêtes HTTP
     * 
     * BUT : Vérifier que le middleware fonctionne avec tous les types de requêtes
     * 
     * ENTRÉE : 
     * - Un utilisateur avec role = 'student'
     * - Différentes méthodes HTTP (GET, POST, PUT, DELETE)
     * 
     * SORTIE ATTENDUE : 
     * - Code HTTP 200 pour toutes les méthodes
     * 
     * POURQUOI : Le middleware doit fonctionner indépendamment de la méthode HTTP.
     */
    #[Test]
    public function it_handles_different_request_methods()
    {
        $student = User::factory()->create(['role' => 'student']);
        $middleware = new StudentMiddleware();
        
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        
        foreach ($methods as $method) {
            $request = Request::create('/api/student/test', $method);
            $request->setUserResolver(function () use ($student) {
                return $student;
            });

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            $this->assertEquals(200, $response->getStatusCode(), "Failed for method: {$method}");
        }
    }
}
