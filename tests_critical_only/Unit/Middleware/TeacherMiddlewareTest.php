<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TeacherMiddleware;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;


class TeacherMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_teacher_user()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $token = $teacher->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
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
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_unauthenticated_user()
    {
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
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
    public function it_denies_student_user()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $token = $student->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
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
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    #[Test]
    public function it_allows_user_with_teacher_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);
        // Créer un profil enseignant associé
        $user->teacher()->create([
            'specialization' => 'Dressage',
            'experience_years' => 5
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_user_without_teacher_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);
        // Ne pas créer de profil enseignant
        
        $token = $user->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Devrait passer car le rôle est teacher même sans profil
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_different_request_methods()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $token = $teacher->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        
        foreach ($methods as $method) {
            $request = Request::create('/api/teacher/test', $method);
            $request->headers->set('Authorization', 'Bearer ' . $token);

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            $this->assertEquals(200, $response->getStatusCode(), "Failed for method: {$method}");
        }
    }

    #[Test]
    public function it_handles_user_with_multiple_roles()
    {
        // Test avec un utilisateur qui pourrait avoir plusieurs rôles
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $token = $user->createToken('test-token')->plainTextToken;
        
        $middleware = new TeacherMiddleware();
        $request = Request::create('/api/teacher/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
