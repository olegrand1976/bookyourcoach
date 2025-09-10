<?php

namespace Tests\Unit\Models;

use App\Models\Certification;
use App\Models\ActivityType;
use App\Models\TeacherCertification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class CertificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_certification()
    {
        $certification = Certification::create([
            'name' => 'Certification Test',
            'issuing_authority' => 'Test Authority',
            'category' => 'official',
            'validity_years' => 3,
            'requirements' => ['test1', 'test2'],
            'description' => 'Test certification',
            'icon' => 'certificate-icon',
            'is_active' => true,
            'sort_order' => 1
        ]);

        $this->assertInstanceOf(Certification::class, $certification);
        $this->assertEquals('Certification Test', $certification->name);
        $this->assertEquals('Test Authority', $certification->issuing_authority);
        $this->assertEquals('official', $certification->category);
        $this->assertEquals(3, $certification->validity_years);
        $this->assertTrue($certification->is_active);
    }

    #[Test]
    public function test_belongs_to_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        $certification = Certification::factory()->create([
            'activity_type_id' => $activityType->id
        ]);

        $this->assertInstanceOf(ActivityType::class, $certification->activityType);
        $this->assertEquals($activityType->id, $certification->activityType->id);
    }

    #[Test]
    public function test_has_many_teacher_certifications()
    {
        $certification = Certification::factory()->create();
        $teacherCertification = TeacherCertification::factory()->create([
            'certification_id' => $certification->id
        ]);

        $this->assertTrue($certification->teacherCertifications->contains($teacherCertification));
    }

    #[Test]
    public function test_scope_active()
    {
        Certification::factory()->create(['is_active' => true]);
        Certification::factory()->create(['is_active' => false]);

        $activeCertifications = Certification::active()->get();

        $this->assertCount(1, $activeCertifications);
        $this->assertTrue($activeCertifications->first()->is_active);
    }

    #[Test]
    public function test_scope_by_category()
    {
        Certification::factory()->create(['category' => 'official']);
        Certification::factory()->create(['category' => 'federation']);

        $officialCertifications = Certification::byCategory('official')->get();

        $this->assertCount(1, $officialCertifications);
        $this->assertEquals('official', $officialCertifications->first()->category);
    }

    #[Test]
    public function test_scope_by_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        Certification::factory()->create(['activity_type_id' => $activityType->id]);
        Certification::factory()->create(['activity_type_id' => null]);

        $certifications = Certification::byActivityType($activityType->id)->get();

        $this->assertCount(1, $certifications);
        $this->assertEquals($activityType->id, $certifications->first()->activity_type_id);
    }

    #[Test]
    public function test_requirements_casting()
    {
        $requirements = ['requirement1', 'requirement2'];
        $certification = Certification::create([
            'name' => 'Test Certification',
            'issuing_authority' => 'Test Authority',
            'category' => 'official',
            'requirements' => $requirements,
            'is_active' => true
        ]);

        $this->assertIsArray($certification->requirements);
        $this->assertEquals($requirements, $certification->requirements);
    }

    #[Test]
    public function test_is_expired_returns_false_for_permanent_certification()
    {
        $certification = Certification::create([
            'name' => 'Permanent Certification',
            'issuing_authority' => 'Test Authority',
            'category' => 'official',
            'validity_years' => null,
            'is_active' => true
        ]);

        $this->assertFalse($certification->isExpired());
    }

    #[Test]
    public function test_is_expired_returns_true_for_expired_certification()
    {
        $certification = Certification::create([
            'name' => 'Expired Certification',
            'issuing_authority' => 'Test Authority',
            'category' => 'official',
            'validity_years' => 1,
            'is_active' => true
        ]);
        
        // Mettre à jour directement dans la base de données pour simuler une certification expirée
        \DB::table('certifications')
            ->where('id', $certification->id)
            ->update(['created_at' => now()->subYears(2)]);
        
        $certification = $certification->fresh();

        $this->assertTrue($certification->isExpired());
    }

    #[Test]
    public function test_is_expired_returns_false_for_valid_certification()
    {
        $certification = Certification::create([
            'name' => 'Valid Certification',
            'issuing_authority' => 'Test Authority',
            'category' => 'official',
            'validity_years' => 3,
            'is_active' => true,
            'created_at' => now()->subYear()
        ]);

        $this->assertFalse($certification->isExpired());
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $certification = new Certification();
        $fillable = $certification->getFillable();

        $expectedFillable = [
            'name',
            'issuing_authority',
            'category',
            'activity_type_id',
            'validity_years',
            'requirements',
            'description',
            'icon',
            'is_active',
            'sort_order'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $certification = new Certification();
        $casts = $certification->getCasts();

        $this->assertArrayHasKey('requirements', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('sort_order', $casts);
        $this->assertArrayHasKey('validity_years', $casts);
    }
}
