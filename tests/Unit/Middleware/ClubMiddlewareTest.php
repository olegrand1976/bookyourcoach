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


class ClubMiddlewareTest extends TestCase
{
    use RefreshDatabase;

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

    #[Test]
    public function it_allows_admin_user()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
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

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_unauthenticated_user()
    {
        $middleware = new ClubMiddleware();
        $request = Request::create('/api/club/dashboard');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }

    #[Test]
    public function it_denies_student_user()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

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
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    #[Test]
    public function it_denies_teacher_user()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

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
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    #[Test]
    public function it_denies_club_user_without_club_association()
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

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('not associated with any club', $response->getContent());
    }

    #[Test]
    public function it_denies_admin_user_without_club_association()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
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
        $this->assertStringContainsString('not associated with any club', $response->getContent());
    }

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

    #[Test]
    public function it_allows_club_user_with_different_roles_in_clubs()
    {
        $clubUser = User::factory()->create(['role' => 'club']);
        $club = Club::factory()->create();
        
        // Tester différents rôles dans le club
        $roles = ['owner', 'manager', 'member'];
        
        foreach ($roles as $role) {
            $club->users()->detach($clubUser->id);
            $club->users()->attach($clubUser->id, [
                'role' => $role,
                'is_admin' => $role === 'owner',
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

            $this->assertEquals(200, $response->getStatusCode(), "Failed for role: {$role}");
        }
    }
}
