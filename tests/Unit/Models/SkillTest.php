<?php

namespace Tests\Unit\Models;

use App\Models\Skill;
use App\Models\ActivityType;
use App\Models\TeacherSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class SkillTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_skill()
    {
        $activityType = ActivityType::factory()->create();

        $skill = Skill::create([
            'name' => 'Test Skill',
            'category' => 'technical',
            'activity_type_id' => $activityType->id,
            'description' => 'Test skill description',
            'icon' => 'skill-icon',
            'levels' => ['beginner', 'intermediate', 'advanced'],
            'requirements' => ['requirement1', 'requirement2'],
            'is_active' => true,
            'sort_order' => 1
        ]);

        $this->assertInstanceOf(Skill::class, $skill);
        $this->assertEquals('Test Skill', $skill->name);
        $this->assertEquals('technical', $skill->category);
        $this->assertEquals($activityType->id, $skill->activity_type_id);
        $this->assertTrue($skill->is_active);
    }

    #[Test]
    public function test_belongs_to_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        $skill = Skill::factory()->create([
            'activity_type_id' => $activityType->id
        ]);

        $this->assertInstanceOf(ActivityType::class, $skill->activityType);
        $this->assertEquals($activityType->id, $skill->activityType->id);
    }

    #[Test]
    public function test_has_many_teacher_skills()
    {
        $skill = Skill::factory()->create();
        $teacherSkill = TeacherSkill::factory()->create([
            'skill_id' => $skill->id
        ]);

        $this->assertTrue($skill->teacherSkills->contains($teacherSkill));
    }

    #[Test]
    public function test_scope_active()
    {
        Skill::factory()->create(['is_active' => true]);
        Skill::factory()->create(['is_active' => false]);

        $activeSkills = Skill::active()->get();

        $this->assertCount(1, $activeSkills);
        $this->assertTrue($activeSkills->first()->is_active);
    }

    #[Test]
    public function test_scope_by_category()
    {
        Skill::factory()->create(['category' => 'technical']);
        Skill::factory()->create(['category' => 'pedagogical']);

        $technicalSkills = Skill::byCategory('technical')->get();

        $this->assertCount(1, $technicalSkills);
        $this->assertEquals('technical', $technicalSkills->first()->category);
    }

    #[Test]
    public function test_scope_by_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        Skill::factory()->create(['activity_type_id' => $activityType->id]);
        Skill::factory()->create(['activity_type_id' => null]);

        $skills = Skill::byActivityType($activityType->id)->get();

        $this->assertCount(1, $skills);
        $this->assertEquals($activityType->id, $skills->first()->activity_type_id);
    }

    #[Test]
    public function test_levels_casting()
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'levels' => $levels,
            'is_active' => true
        ]);

        $this->assertIsArray($skill->levels);
        $this->assertEquals($levels, $skill->levels);
    }

    #[Test]
    public function test_requirements_casting()
    {
        $requirements = ['requirement1', 'requirement2'];
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'requirements' => $requirements,
            'is_active' => true
        ]);

        $this->assertIsArray($skill->requirements);
        $this->assertEquals($requirements, $skill->requirements);
    }

    #[Test]
    public function test_get_levels_attribute_with_null()
    {
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'levels' => null,
            'is_active' => true
        ]);

        $this->assertEquals(['beginner', 'intermediate', 'advanced', 'expert', 'master'], $skill->getLevelsAttribute(null));
    }

    #[Test]
    public function test_get_requirements_attribute_with_null()
    {
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'requirements' => null,
            'is_active' => true
        ]);

        $this->assertEquals([], $skill->getRequirementsAttribute(null));
    }

    #[Test]
    public function test_sort_order_casting()
    {
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'sort_order' => 5,
            'is_active' => true
        ]);

        $this->assertIsInt($skill->sort_order);
        $this->assertEquals(5, $skill->sort_order);
    }

    #[Test]
    public function test_is_active_casting()
    {
        $skill = Skill::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Skill',
            'category' => 'technical',
            'is_active' => true
        ]);

        $this->assertIsBool($skill->is_active);
        $this->assertTrue($skill->is_active);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $skill = new Skill();
        $fillable = $skill->getFillable();

        $expectedFillable = [
            'name',
            'category',
            'activity_type_id',
            'description',
            'icon',
            'levels',
            'requirements',
            'is_active',
            'sort_order'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $skill = new Skill();
        $casts = $skill->getCasts();

        $this->assertArrayHasKey('levels', $casts);
        $this->assertArrayHasKey('requirements', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('sort_order', $casts);
    }
}
