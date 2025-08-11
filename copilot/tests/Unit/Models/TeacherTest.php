<?php

namespace Tests\Unit\Models;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Availability;
use App\Models\Lesson;
use App\Models\CourseType;
use App\Models\Payout;
use App\Models\TimeBlock;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_required_fields()
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $teacherData = [
            'user_id' => $user->id,
            'hourly_rate' => 50.00,
        ];

        $teacher = Teacher::create($teacherData);

        $this->assertInstanceOf(Teacher::class, $teacher);
        $this->assertEquals($user->id, $teacher->user_id);
        $this->assertEquals(50.00, $teacher->hourly_rate);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $teacher->user());
        $this->assertInstanceOf(User::class, $teacher->user);
    }

    /** @test */
    public function it_has_availabilities_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $teacher->availabilities());
    }

    /** @test */
    public function it_has_lessons_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $teacher->lessons());
    }

    /** @test */
    public function it_has_course_types_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $teacher->courseTypes());
    }

    /** @test */
    public function it_has_payouts_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $teacher->payouts());
    }

    /** @test */
    public function it_has_time_blocks_relationship()
    {
        $teacher = Teacher::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $teacher->timeBlocks());
    }

    /** @test */
    public function it_casts_specialties_as_array()
    {
        $specialties = ['dressage', 'obstacle', 'cross'];

        $teacher = Teacher::factory()->create([
            'specialties' => $specialties
        ]);

        $this->assertIsArray($teacher->specialties);
        $this->assertEquals($specialties, $teacher->specialties);
    }

    /** @test */
    public function it_casts_certifications_as_array()
    {
        $certifications = ['FFE Galop 7', 'BPJEPS', 'CQP ASA'];

        $teacher = Teacher::factory()->create([
            'certifications' => $certifications
        ]);

        $this->assertIsArray($teacher->certifications);
        $this->assertEquals($certifications, $teacher->certifications);
    }

    /** @test */
    public function it_casts_preferred_locations_as_array()
    {
        $locations = ['Paris', 'Lyon', 'Marseille'];

        $teacher = Teacher::factory()->create([
            'preferred_locations' => $locations
        ]);

        $this->assertIsArray($teacher->preferred_locations);
        $this->assertEquals($locations, $teacher->preferred_locations);
    }

    /** @test */
    public function it_casts_hourly_rate_as_decimal()
    {
        $teacher = Teacher::factory()->create([
            'hourly_rate' => 45.50
        ]);

        $this->assertEquals('45.50', $teacher->hourly_rate);
    }

    /** @test */
    public function it_casts_rating_as_decimal()
    {
        $teacher = Teacher::factory()->create([
            'rating' => 4.75
        ]);

        $this->assertEquals('4.75', $teacher->rating);
    }

    /** @test */
    public function it_casts_is_available_as_boolean()
    {
        $teacher = Teacher::factory()->create([
            'is_available' => true
        ]);

        $this->assertIsBool($teacher->is_available);
        $this->assertTrue($teacher->is_available);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'specialties',
            'experience_years',
            'certifications',
            'hourly_rate',
            'bio',
            'is_available',
            'max_travel_distance',
            'preferred_locations',
            'stripe_account_id',
            'rating',
            'total_lessons',
        ];

        $teacher = new Teacher();
        $this->assertEquals($fillable, $teacher->getFillable());
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $teacher = Teacher::factory()->create();
        $teacherId = $teacher->id;

        $teacher->delete();

        $this->assertSoftDeleted('teachers', ['id' => $teacherId]);
        $this->assertNotNull($teacher->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_store_optional_fields()
    {
        $user = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $teacherData = [
            'user_id' => $user->id,
            'specialties' => ['dressage', 'obstacle'],
            'experience_years' => 10,
            'certifications' => ['FFE Galop 7', 'BPJEPS'],
            'hourly_rate' => 60.00,
            'bio' => 'Enseignant expérimenté en équitation',
            'is_available' => true,
            'max_travel_distance' => 50,
            'preferred_locations' => ['Paris', 'Versailles'],
            'stripe_account_id' => 'acct_123456789',
            'rating' => 4.8,
            'total_lessons' => 250,
        ];

        $teacher = Teacher::create($teacherData);

        $this->assertEquals(['dressage', 'obstacle'], $teacher->specialties);
        $this->assertEquals(10, $teacher->experience_years);
        $this->assertEquals(['FFE Galop 7', 'BPJEPS'], $teacher->certifications);
        $this->assertEquals('60.00', $teacher->hourly_rate);
        $this->assertEquals('Enseignant expérimenté en équitation', $teacher->bio);
        $this->assertTrue($teacher->is_available);
        $this->assertEquals(50, $teacher->max_travel_distance);
        $this->assertEquals(['Paris', 'Versailles'], $teacher->preferred_locations);
        $this->assertEquals('acct_123456789', $teacher->stripe_account_id);
        $this->assertEquals('4.80', $teacher->rating);
        $this->assertEquals(250, $teacher->total_lessons);
    }
}
