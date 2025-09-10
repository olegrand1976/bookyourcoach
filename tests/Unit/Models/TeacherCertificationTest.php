<?php

namespace Tests\Unit\Models;

use App\Models\TeacherCertification;
use App\Models\Teacher;
use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherCertificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_teacher_certification()
    {
        $teacher = Teacher::factory()->create();
        $certification = Certification::factory()->create();

        $teacherCertification = TeacherCertification::create([
            'teacher_id' => $teacher->id,
            'certification_id' => $certification->id,
            'obtained_date' => now()->subYear(),
            'expiry_date' => now()->addYears(2),
            'certificate_number' => 'CERT123456',
            'issuing_authority' => 'Test Authority',
            'certificate_file' => '/path/to/certificate.pdf',
            'notes' => 'Test certification notes',
            'is_valid' => true,
            'renewal_required' => true,
            'renewal_reminder_date' => now()->addDays(30),
            'is_verified' => true,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now()
        ]);

        $this->assertInstanceOf(TeacherCertification::class, $teacherCertification);
        $this->assertEquals($teacher->id, $teacherCertification->teacher_id);
        $this->assertEquals($certification->id, $teacherCertification->certification_id);
        $this->assertEquals('CERT123456', $teacherCertification->certificate_number);
        $this->assertTrue($teacherCertification->is_valid);
        $this->assertTrue($teacherCertification->is_verified);
    }

    public function test_belongs_to_teacher()
    {
        $teacher = Teacher::factory()->create();
        $teacherCertification = TeacherCertification::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $this->assertInstanceOf(Teacher::class, $teacherCertification->teacher);
        $this->assertEquals($teacher->id, $teacherCertification->teacher->id);
    }

    public function test_belongs_to_certification()
    {
        $certification = Certification::factory()->create();
        $teacherCertification = TeacherCertification::factory()->create([
            'certification_id' => $certification->id
        ]);

        $this->assertInstanceOf(Certification::class, $teacherCertification->certification);
        $this->assertEquals($certification->id, $teacherCertification->certification->id);
    }

    public function test_belongs_to_verified_by_user()
    {
        $user = User::factory()->create();
        $teacherCertification = TeacherCertification::factory()->create([
            'verified_by' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $teacherCertification->verifiedBy);
        $this->assertEquals($user->id, $teacherCertification->verifiedBy->id);
    }

    public function test_scope_valid()
    {
        TeacherCertification::factory()->create(['is_valid' => true]);
        TeacherCertification::factory()->create(['is_valid' => false]);

        $validCertifications = TeacherCertification::valid()->get();

        $this->assertCount(1, $validCertifications);
        $this->assertTrue($validCertifications->first()->is_valid);
    }

    public function test_scope_verified()
    {
        TeacherCertification::factory()->create(['is_verified' => true]);
        TeacherCertification::factory()->create(['is_verified' => false]);

        $verifiedCertifications = TeacherCertification::verified()->get();

        $this->assertCount(1, $verifiedCertifications);
        $this->assertTrue($verifiedCertifications->first()->is_verified);
    }

    public function test_scope_expiring_soon()
    {
        TeacherCertification::factory()->create([
            'expiry_date' => now()->addDays(15)
        ]);
        TeacherCertification::factory()->create([
            'expiry_date' => now()->addDays(45)
        ]);
        TeacherCertification::factory()->create([
            'expiry_date' => now()->subDays(10)
        ]);

        $expiringSoon = TeacherCertification::expiringSoon()->get();

        $this->assertCount(1, $expiringSoon);
        $this->assertTrue($expiringSoon->first()->expiry_date->isAfter(now()));
        $this->assertTrue($expiringSoon->first()->expiry_date->isBefore(now()->addDays(30)));
    }

    public function test_is_expired_returns_true_for_expired_certification()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => now()->subDays(10),
            'is_valid' => true
        ]);

        $this->assertTrue($teacherCertification->isExpired());
    }

    public function test_is_expired_returns_false_for_valid_certification()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => now()->addDays(30),
            'is_valid' => true
        ]);

        $this->assertFalse($teacherCertification->isExpired());
    }

    public function test_is_expired_returns_false_for_certification_without_expiry()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => null,
            'is_valid' => true
        ]);

        $this->assertFalse($teacherCertification->isExpired());
    }

    public function test_is_expiring_soon_returns_true_for_certification_expiring_within_days()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => now()->addDays(15),
            'is_valid' => true
        ]);

        $this->assertTrue($teacherCertification->isExpiringSoon(30));
    }

    public function test_is_expiring_soon_returns_false_for_certification_expiring_after_days()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => now()->addDays(45),
            'is_valid' => true
        ]);

        $this->assertFalse($teacherCertification->isExpiringSoon(30));
    }

    public function test_get_days_until_expiry_returns_correct_days()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => now()->addDays(30),
            'is_valid' => true
        ]);

        $this->assertEquals(30, $teacherCertification->getDaysUntilExpiry());
    }

    public function test_get_days_until_expiry_returns_null_for_certification_without_expiry()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'expiry_date' => null,
            'is_valid' => true
        ]);

        $this->assertNull($teacherCertification->getDaysUntilExpiry());
    }

    public function test_date_casting()
    {
        $obtainedDate = now()->subYear();
        $expiryDate = now()->addYear();
        $renewalReminderDate = now()->addDays(30);
        $verifiedAt = now();

        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'obtained_date' => $obtainedDate,
            'expiry_date' => $expiryDate,
            'renewal_reminder_date' => $renewalReminderDate,
            'verified_at' => $verifiedAt,
            'is_valid' => true
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherCertification->obtained_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherCertification->expiry_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherCertification->renewal_reminder_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherCertification->verified_at);
    }

    public function test_boolean_casting()
    {
        $teacherCertification = TeacherCertification::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'certification_id' => Certification::factory()->create()->id,
            'is_valid' => true,
            'renewal_required' => false,
            'is_verified' => true
        ]);

        $this->assertIsBool($teacherCertification->is_valid);
        $this->assertIsBool($teacherCertification->renewal_required);
        $this->assertIsBool($teacherCertification->is_verified);
        $this->assertTrue($teacherCertification->is_valid);
        $this->assertFalse($teacherCertification->renewal_required);
        $this->assertTrue($teacherCertification->is_verified);
    }

    public function test_fillable_attributes()
    {
        $teacherCertification = new TeacherCertification();
        $fillable = $teacherCertification->getFillable();

        $expectedFillable = [
            'teacher_id',
            'certification_id',
            'obtained_date',
            'expiry_date',
            'certificate_number',
            'issuing_authority',
            'certificate_file',
            'notes',
            'is_valid',
            'renewal_required',
            'renewal_reminder_date',
            'is_verified',
            'verified_by',
            'verified_at'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $teacherCertification = new TeacherCertification();
        $casts = $teacherCertification->getCasts();

        $this->assertArrayHasKey('is_valid', $casts);
        $this->assertArrayHasKey('renewal_required', $casts);
        $this->assertArrayHasKey('is_verified', $casts);
        $this->assertArrayHasKey('obtained_date', $casts);
        $this->assertArrayHasKey('expiry_date', $casts);
        $this->assertArrayHasKey('renewal_reminder_date', $casts);
        $this->assertArrayHasKey('verified_at', $casts);
    }
}
