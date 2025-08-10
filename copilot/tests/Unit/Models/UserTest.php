<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT,
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertEquals($userData['role'], $user->role);
    }

    /** @test */
    public function it_has_role_constants()
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('teacher', User::ROLE_TEACHER);
        $this->assertEquals('student', User::ROLE_STUDENT);
    }

    /** @test */
    public function it_can_check_if_user_has_specific_role()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
        $this->assertFalse($user->hasRole(User::ROLE_TEACHER));
        $this->assertFalse($user->hasRole(User::ROLE_STUDENT));
    }

    /** @test */
    public function it_can_check_if_user_is_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($teacher->isAdmin());
    }

    /** @test */
    public function it_can_check_if_user_is_teacher()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($student->isTeacher());
    }

    /** @test */
    public function it_can_check_if_user_is_student()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($student->isStudent());
        $this->assertFalse($admin->isStudent());
    }

    /** @test */
    public function it_has_profile_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->profile());
    }

    /** @test */
    public function it_has_teacher_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->teacher());
    }

    /** @test */
    public function it_has_student_relationship()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->student());
    }

    /** @test */
    public function it_defaults_to_student_role()
    {
        $user = User::factory()->create();

        $this->assertEquals(User::ROLE_STUDENT, $user->role);
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create();
        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }
}
