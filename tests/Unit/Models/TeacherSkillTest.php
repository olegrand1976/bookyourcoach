<?php

namespace Tests\Unit\Models;

use App\Models\TeacherSkill;
use App\Models\Teacher;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class TeacherSkillTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_teacher_skill()
    {
        $teacher = Teacher::factory()->create();
        $skill = Skill::factory()->create();

        $teacherSkill = TeacherSkill::create([
            'teacher_id' => $teacher->id,
            'skill_id' => $skill->id,
            'level' => 'intermediate',
            'experience_years' => 5,
            'acquired_date' => now()->subYears(5),
            'last_practiced' => now()->subDays(10),
            'notes' => 'Test skill notes',
            'evidence' => ['certificate1', 'video1'],
            'is_verified' => true,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now(),
            'is_active' => true
        ]);

        $this->assertInstanceOf(TeacherSkill::class, $teacherSkill);
        $this->assertEquals($teacher->id, $teacherSkill->teacher_id);
        $this->assertEquals($skill->id, $teacherSkill->skill_id);
        $this->assertEquals('intermediate', $teacherSkill->level);
        $this->assertEquals(5, $teacherSkill->experience_years);
        $this->assertTrue($teacherSkill->is_verified);
        $this->assertTrue($teacherSkill->is_active);
    }

    #[Test]
    public function test_belongs_to_teacher()
    {
        $teacher = Teacher::factory()->create();
        $teacherSkill = TeacherSkill::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $this->assertInstanceOf(Teacher::class, $teacherSkill->teacher);
        $this->assertEquals($teacher->id, $teacherSkill->teacher->id);
    }

    #[Test]
    public function test_belongs_to_skill()
    {
        $skill = Skill::factory()->create();
        $teacherSkill = TeacherSkill::factory()->create([
            'skill_id' => $skill->id
        ]);

        $this->assertInstanceOf(Skill::class, $teacherSkill->skill);
        $this->assertEquals($skill->id, $teacherSkill->skill->id);
    }

    #[Test]
    public function test_belongs_to_verified_by_user()
    {
        $user = User::factory()->create();
        $teacherSkill = TeacherSkill::factory()->create([
            'verified_by' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $teacherSkill->verifiedBy);
        $this->assertEquals($user->id, $teacherSkill->verifiedBy->id);
    }

    #[Test]
    public function test_scope_active()
    {
        TeacherSkill::factory()->create(['is_active' => true]);
        TeacherSkill::factory()->create(['is_active' => false]);

        $activeSkills = TeacherSkill::active()->get();

        $this->assertCount(1, $activeSkills);
        $this->assertTrue($activeSkills->first()->is_active);
    }

    #[Test]
    public function test_scope_verified()
    {
        TeacherSkill::factory()->create(['is_verified' => true]);
        TeacherSkill::factory()->create(['is_verified' => false]);

        $verifiedSkills = TeacherSkill::verified()->get();

        $this->assertCount(1, $verifiedSkills);
        $this->assertTrue($verifiedSkills->first()->is_verified);
    }

    #[Test]
    public function test_scope_by_level()
    {
        TeacherSkill::factory()->create(['level' => 'beginner']);
        TeacherSkill::factory()->create(['level' => 'intermediate']);

        $beginnerSkills = TeacherSkill::byLevel('beginner')->get();

        $this->assertCount(1, $beginnerSkills);
        $this->assertEquals('beginner', $beginnerSkills->first()->level);
    }

    #[Test]
    public function test_evidence_casting()
    {
        $evidence = ['certificate1', 'video1', 'testimonial1'];
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'evidence' => $evidence,
            'is_active' => true
        ]);

        $this->assertIsArray($teacherSkill->evidence);
        $this->assertEquals($evidence, $teacherSkill->evidence);
    }

    #[Test]
    public function test_get_evidence_attribute_with_null()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'evidence' => null,
            'is_active' => true
        ]);

        $this->assertEquals([], $teacherSkill->getEvidenceAttribute(null));
    }

    #[Test]
    public function test_is_recently_practiced_returns_true_for_recent_practice()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'last_practiced' => now()->subDays(15),
            'is_active' => true
        ]);

        $this->assertTrue($teacherSkill->isRecentlyPracticed());
    }

    #[Test]
    public function test_is_recently_practiced_returns_false_for_old_practice()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'last_practiced' => now()->subDays(45),
            'is_active' => true
        ]);

        $this->assertFalse($teacherSkill->isRecentlyPracticed());
    }

    #[Test]
    public function test_is_recently_practiced_returns_false_for_no_practice()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'last_practiced' => null,
            'is_active' => true
        ]);

        $this->assertFalse($teacherSkill->isRecentlyPracticed());
    }

    #[Test]
    public function test_get_experience_level_returns_expert_for_10_years()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'experience_years' => 10,
            'is_active' => true
        ]);

        $this->assertEquals('expert', $teacherSkill->getExperienceLevel());
    }

    #[Test]
    public function test_get_experience_level_returns_advanced_for_5_years()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'experience_years' => 5,
            'is_active' => true
        ]);

        $this->assertEquals('advanced', $teacherSkill->getExperienceLevel());
    }

    #[Test]
    public function test_get_experience_level_returns_intermediate_for_2_years()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'experience_years' => 2,
            'is_active' => true
        ]);

        $this->assertEquals('intermediate', $teacherSkill->getExperienceLevel());
    }

    #[Test]
    public function test_get_experience_level_returns_beginner_for_less_than_2_years()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'experience_years' => 1,
            'is_active' => true
        ]);

        $this->assertEquals('beginner', $teacherSkill->getExperienceLevel());
    }

    #[Test]
    public function test_date_casting()
    {
        $acquiredDate = now()->subYears(5);
        $lastPracticed = now()->subDays(10);
        $verifiedAt = now();

        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'acquired_date' => $acquiredDate,
            'last_practiced' => $lastPracticed,
            'verified_at' => $verifiedAt,
            'is_active' => true
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherSkill->acquired_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherSkill->last_practiced);
        $this->assertInstanceOf(\Carbon\Carbon::class, $teacherSkill->verified_at);
    }

    #[Test]
    public function test_boolean_casting()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'is_verified' => true,
            'is_active' => false
        ]);

        $this->assertIsBool($teacherSkill->is_verified);
        $this->assertIsBool($teacherSkill->is_active);
        $this->assertTrue($teacherSkill->is_verified);
        $this->assertFalse($teacherSkill->is_active);
    }

    #[Test]
    public function test_experience_years_casting()
    {
        $teacherSkill = TeacherSkill::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'skill_id' => Skill::factory()->create()->id,
            'level' => 'intermediate',
            'experience_years' => 7,
            'is_active' => true
        ]);

        $this->assertIsInt($teacherSkill->experience_years);
        $this->assertEquals(7, $teacherSkill->experience_years);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $teacherSkill = new TeacherSkill();
        $fillable = $teacherSkill->getFillable();

        $expectedFillable = [
            'teacher_id',
            'skill_id',
            'level',
            'experience_years',
            'acquired_date',
            'last_practiced',
            'notes',
            'evidence',
            'is_verified',
            'verified_by',
            'verified_at',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $teacherSkill = new TeacherSkill();
        $casts = $teacherSkill->getCasts();

        $this->assertArrayHasKey('evidence', $casts);
        $this->assertArrayHasKey('is_verified', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('acquired_date', $casts);
        $this->assertArrayHasKey('last_practiced', $casts);
        $this->assertArrayHasKey('verified_at', $casts);
        $this->assertArrayHasKey('experience_years', $casts);
    }
}
