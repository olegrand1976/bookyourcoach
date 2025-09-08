<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Club;
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
        $this->assertEquals('club', User::ROLE_CLUB);
    }

    /** @test */
    public function it_can_check_if_user_has_specific_role()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
        $this->assertFalse($user->hasRole(User::ROLE_TEACHER));
        $this->assertFalse($user->hasRole(User::ROLE_STUDENT));
        $this->assertFalse($user->hasRole(User::ROLE_CLUB));
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
    public function it_can_check_if_user_is_club()
    {
        $club = User::factory()->create(['role' => User::ROLE_CLUB]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($club->isClub());
        $this->assertFalse($admin->isClub());
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
    public function it_has_clubs_relationship()
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        $user->clubs()->attach($club->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->clubs());
        $this->assertTrue($user->clubs->contains($club));
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

    /** @test */
    public function it_can_act_as_teacher()
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertTrue($teacher->canActAsTeacher());
        $this->assertFalse($student->canActAsTeacher());
    }

    /** @test */
    public function it_can_act_as_student()
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->assertTrue($student->canActAsStudent());
        $this->assertFalse($teacher->canActAsStudent());
    }

    /** @test */
    public function it_can_be_associated_with_multiple_clubs()
    {
        $user = User::factory()->create(['role' => User::ROLE_CLUB]);
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();

        $user->clubs()->attach($club1->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        $user->clubs()->attach($club2->id, [
            'role' => 'manager',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $this->assertCount(2, $user->clubs);
        $this->assertTrue($user->clubs->contains($club1));
        $this->assertTrue($user->clubs->contains($club2));
    }

    /** @test */
    public function it_can_have_different_roles_in_different_clubs()
    {
        $user = User::factory()->create(['role' => User::ROLE_CLUB]);
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();

        $user->clubs()->attach($club1->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);
        $user->clubs()->attach($club2->id, [
            'role' => 'manager',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $club1User = $user->clubs()->where('club_id', $club1->id)->first();
        $club2User = $user->clubs()->where('club_id', $club2->id)->first();

        $this->assertEquals('owner', $club1User->pivot->role);
        $this->assertTrue($club1User->pivot->is_admin);
        $this->assertEquals('manager', $club2User->pivot->role);
        $this->assertFalse($club2User->pivot->is_admin);
    }
}