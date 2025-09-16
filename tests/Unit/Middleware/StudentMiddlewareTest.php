<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\StudentMiddleware;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;


class StudentMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_student_user()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $token = $student->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_admin_user()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $admin->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_unauthenticated_user()
    {
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Missing token', $response->getContent());
    }

    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $token = $teacher->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    #[Test]
    public function it_denies_club_user()
    {
        $clubUser = User::factory()->create(['role' => User::ROLE_CLUB]);
        $token = $clubUser->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    #[Test]
    public function it_allows_user_with_student_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        // Créer un profil étudiant associé
        $user->student()->create([
            'level' => 'debutant',
            'goals' => 'Apprendre les bases'
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_user_without_student_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        // Ne pas créer de profil étudiant
        
        $token = $user->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        $request = Request::create('/api/student/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Devrait passer car le rôle est student même sans profil
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_different_request_methods()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $token = $student->createToken('test-token')->plainTextToken;
        
        $middleware = new StudentMiddleware();
        
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        
        foreach ($methods as $method) {
            $request = Request::create('/api/student/test', $method);
            $request->headers->set('Authorization', 'Bearer ' . $token);

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            $this->assertEquals(200, $response->getStatusCode(), "Failed for method: {$method}");
        }
    }
}
