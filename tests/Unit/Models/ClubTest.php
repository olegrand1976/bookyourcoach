<?php

namespace Tests\Unit\Models;

use App\Models\Club;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class ClubTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_created_with_required_fields()
    {
        $clubData = [
            'name' => 'Club Équestre de Paris',
            'description' => 'Un club d\'équitation situé à Paris',
            'address' => '123 Rue de la Selle, 75001 Paris',
            'phone' => '01 23 45 67 89',
            'email' => 'contact@club-paris.fr',
            'max_students' => 100,
            'subscription_price' => 150.00,
            'is_active' => true,
        ];

        $club = Club::create($clubData);

        $this->assertInstanceOf(Club::class, $club);
        $this->assertEquals($clubData['name'], $club->name);
        $this->assertEquals($clubData['description'], $club->description);
        $this->assertEquals($clubData['address'], $club->address);
        $this->assertEquals($clubData['phone'], $club->phone);
        $this->assertEquals($clubData['email'], $club->email);
        $this->assertEquals($clubData['max_students'], $club->max_students);
        $this->assertEquals($clubData['subscription_price'], $club->subscription_price);
        $this->assertTrue($club->is_active);
    }

    #[Test]
    public function it_has_users_relationship()
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $club->users()->attach($user->id, [
            'role' => 'owner',
            'is_admin' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $club->users());
        $this->assertTrue($club->users->contains($user));
    }

    #[Test]
    public function it_has_teachers_relationship()
    {
        $club = Club::factory()->create();
        $teacher = Teacher::factory()->create();

        $club->teachers()->attach($teacher->id, [
            'allowed_disciplines' => json_encode([]),
            'restricted_disciplines' => json_encode([]),
            'hourly_rate' => 50.00,
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $club->teachers());
        $this->assertTrue($club->teachers->contains($teacher));
    }

    #[Test]
    public function it_has_students_relationship()
    {
        $club = Club::factory()->create();
        $student = Student::factory()->create();

        $club->students()->attach($student->id, [
            'level' => 'beginner',
            'goals' => 'Learn basics',
            'medical_info' => null,
            'preferred_disciplines' => json_encode([]),
            'is_active' => true,
            'joined_at' => now()
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $club->students());
        $this->assertTrue($club->students->contains($student));
    }

    #[Test]
    public function it_casts_subscription_price_as_decimal()
    {
        $club = Club::factory()->create([
            'subscription_price' => 150.50
        ]);

        $this->assertEquals('150.50', $club->subscription_price);
    }

    #[Test]
    public function it_casts_is_active_as_boolean()
    {
        $club = Club::factory()->create([
            'is_active' => true
        ]);

        $this->assertIsBool($club->is_active);
        $this->assertTrue($club->is_active);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'email',
            'phone',
            'address',
            'city',
            'postal_code',
            'country',
            'website',
            'facilities',
            'disciplines',
            'max_students',
            'subscription_price',
            'is_active',
            'terms_and_conditions',
            'activity_type_id',
            'seasonal_variation',
            'weather_dependency',
            'qr_code',
            'qr_code_generated_at',
        ];

        $club = new Club();
        $this->assertEquals($fillable, $club->getFillable());
    }

    #[Test]
    public function it_can_count_teachers()
    {
        $club = Club::factory()->create();
        
        $teacher1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacher2 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club->users()->attach($teacher1->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($teacher2->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($student->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $this->assertEquals(2, $club->users()->wherePivot('role', 'teacher')->count());
    }

    #[Test]
    public function it_can_count_students()
    {
        $club = Club::factory()->create();
        
        $student1 = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $student2 = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $club->users()->attach($student1->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($student2->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($teacher->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $this->assertEquals(2, $club->users()->wherePivot('role', 'student')->count());
    }

    #[Test]
    public function it_can_calculate_occupancy_rate()
    {
        $club = Club::factory()->create([
            'max_students' => 100
        ]);
        
        $student1 = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $student2 = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club->users()->attach($student1->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);
        $club->users()->attach($student2->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $occupancyRate = $club->max_students > 0 ? 
            round(($club->users()->wherePivot('role', 'student')->count() / $club->max_students) * 100, 2) : 0;

        $this->assertEquals(2.0, $occupancyRate);
    }

    #[Test]
    public function it_can_get_recent_teachers()
    {
        $club = Club::factory()->create();
        
        $teacher1 = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $teacher2 = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $club->users()->attach($teacher1->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()->subDays(1)
        ]);
        $club->users()->attach($teacher2->id, [
            'role' => 'teacher',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $recentTeachers = $club->users()
            ->wherePivot('role', 'teacher')
            ->orderBy('club_user.created_at', 'desc')
            ->limit(5)
            ->get();

        $this->assertCount(2, $recentTeachers);
        $this->assertTrue($recentTeachers->contains('id', $teacher1->id));
        $this->assertTrue($recentTeachers->contains('id', $teacher2->id));
    }

    #[Test]
    public function it_can_get_recent_students()
    {
        $club = Club::factory()->create();
        
        $student1 = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $student2 = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $club->users()->attach($student1->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()->subDays(1)
        ]);
        $club->users()->attach($student2->id, [
            'role' => 'student',
            'is_admin' => false,
            'joined_at' => now()
        ]);

        $recentStudents = $club->users()
            ->wherePivot('role', 'student')
            ->orderBy('club_user.created_at', 'desc')
            ->limit(5)
            ->get();

        $this->assertCount(2, $recentStudents);
        $this->assertTrue($recentStudents->contains('id', $student1->id));
        $this->assertTrue($recentStudents->contains('id', $student2->id));
    }

    #[Test]
    public function it_can_store_optional_fields()
    {
        $clubData = [
            'name' => 'Club Équestre de Lyon',
            'description' => 'Un club d\'équitation situé à Lyon',
            'address' => '456 Avenue du Cheval, 69000 Lyon',
            'phone' => '04 78 90 12 34',
            'email' => 'contact@club-lyon.fr',
            'max_students' => 80,
            'subscription_price' => 120.00,
            'is_active' => true,
        ];

        $club = Club::create($clubData);

        $this->assertEquals('Club Équestre de Lyon', $club->name);
        $this->assertEquals('Un club d\'équitation situé à Lyon', $club->description);
        $this->assertEquals('456 Avenue du Cheval, 69000 Lyon', $club->address);
        $this->assertEquals('04 78 90 12 34', $club->phone);
        $this->assertEquals('contact@club-lyon.fr', $club->email);
        $this->assertEquals(80, $club->max_students);
        $this->assertEquals('120.00', $club->subscription_price);
        $this->assertTrue($club->is_active);
    }
}
