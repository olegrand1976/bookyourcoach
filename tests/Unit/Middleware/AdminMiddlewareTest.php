<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;


class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_admin_user_with_valid_token()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $admin->createToken('test-token')->plainTextToken;

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_non_admin_user_with_valid_token()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $token = $user->createToken('test-token')->plainTextToken;

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

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
        $this->assertStringContainsString('Missing token', $response->getContent());
    }

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
        $this->assertStringContainsString('Missing token', $response->getContent());
    }

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
        $this->assertStringContainsString('Invalid token', $response->getContent());
    }

    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $token = $teacher->createToken('test-token')->plainTextToken;

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_club_user()
    {
        $clubUser = User::factory()->create(['role' => User::ROLE_CLUB]);
        $token = $clubUser->createToken('test-token')->plainTextToken;

        $middleware = new AdminMiddleware();
        $request = Request::create('/api/admin/test');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

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
