<?php

namespace Tests\Unit\Models;

use App\Models\Discipline;
use App\Models\ActivityType;
use App\Models\Lesson;
use App\Models\CourseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplineTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_discipline()
    {
        $activityType = ActivityType::factory()->create();

        $discipline = Discipline::create([
            'activity_type_id' => $activityType->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'description' => 'Test discipline description',
            'min_participants' => 2,
            'max_participants' => 8,
            'duration_minutes' => 60,
            'equipment_required' => ['helmet', 'boots'],
            'skill_levels' => ['beginner', 'intermediate'],
            'base_price' => 25.00,
            'is_active' => true
        ]);

        $this->assertInstanceOf(Discipline::class, $discipline);
        $this->assertEquals('Test Discipline', $discipline->name);
        $this->assertEquals('test-discipline', $discipline->slug);
        $this->assertEquals($activityType->id, $discipline->activity_type_id);
        $this->assertTrue($discipline->is_active);
    }

    public function test_belongs_to_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        $discipline = Discipline::factory()->create([
            'activity_type_id' => $activityType->id
        ]);

        $this->assertInstanceOf(ActivityType::class, $discipline->activityType);
        $this->assertEquals($activityType->id, $discipline->activityType->id);
    }

    public function test_has_many_lessons()
    {
        $discipline = Discipline::factory()->create();
        $lesson = Lesson::factory()->create([
            'discipline_id' => $discipline->id
        ]);

        $this->assertTrue($discipline->lessons->contains($lesson));
    }

    public function test_has_many_course_types()
    {
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create([
            'discipline_id' => $discipline->id
        ]);

        $this->assertTrue($discipline->courseTypes->contains($courseType));
    }

    public function test_scope_active()
    {
        Discipline::factory()->create(['is_active' => true]);
        Discipline::factory()->create(['is_active' => false]);

        $activeDisciplines = Discipline::active()->get();

        $this->assertCount(1, $activeDisciplines);
        $this->assertTrue($activeDisciplines->first()->is_active);
    }

    public function test_scope_by_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        Discipline::factory()->create(['activity_type_id' => $activityType->id]);
        Discipline::factory()->create(['activity_type_id' => null]);

        $disciplines = Discipline::byActivityType($activityType->id)->get();

        $this->assertCount(1, $disciplines);
        $this->assertEquals($activityType->id, $disciplines->first()->activity_type_id);
    }

    public function test_scope_by_slug()
    {
        Discipline::factory()->create(['slug' => 'test-slug']);
        Discipline::factory()->create(['slug' => 'other-slug']);

        $disciplines = Discipline::bySlug('test-slug')->get();

        $this->assertCount(1, $disciplines);
        $this->assertEquals('test-slug', $disciplines->first()->slug);
    }

    public function test_equipment_required_casting()
    {
        $equipment = ['helmet', 'boots', 'gloves'];
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'equipment_required' => $equipment,
            'is_active' => true
        ]);

        $this->assertIsArray($discipline->equipment_required);
        $this->assertEquals($equipment, $discipline->equipment_required);
    }

    public function test_skill_levels_casting()
    {
        $skillLevels = ['beginner', 'intermediate', 'advanced'];
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'skill_levels' => $skillLevels,
            'is_active' => true
        ]);

        $this->assertIsArray($discipline->skill_levels);
        $this->assertEquals($skillLevels, $discipline->skill_levels);
    }

    public function test_get_equipment_required_attribute_with_null()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'equipment_required' => null,
            'is_active' => true
        ]);

        $this->assertEquals([], $discipline->getEquipmentRequiredAttribute(null));
    }

    public function test_get_skill_levels_attribute_with_null()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'skill_levels' => null,
            'is_active' => true
        ]);

        $this->assertEquals(['débutant', 'intermédiaire', 'expert'], $discipline->getSkillLevelsAttribute(null));
    }

    public function test_get_base_price_attribute_with_null()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'base_price' => null,
            'is_active' => true
        ]);

        $this->assertEquals(0.00, $discipline->getBasePriceAttribute(null));
    }

    public function test_base_price_casting()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'base_price' => 25.50,
            'is_active' => true
        ]);

        $this->assertIsFloat($discipline->base_price);
        $this->assertEquals(25.50, $discipline->base_price);
    }

    public function test_duration_minutes_casting()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'duration_minutes' => 90,
            'is_active' => true
        ]);

        $this->assertIsInt($discipline->duration_minutes);
        $this->assertEquals(90, $discipline->duration_minutes);
    }

    public function test_min_max_participants_casting()
    {
        $discipline = Discipline::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Discipline',
            'slug' => 'test-discipline',
            'min_participants' => 2,
            'max_participants' => 10,
            'is_active' => true
        ]);

        $this->assertIsInt($discipline->min_participants);
        $this->assertIsInt($discipline->max_participants);
        $this->assertEquals(2, $discipline->min_participants);
        $this->assertEquals(10, $discipline->max_participants);
    }

    public function test_fillable_attributes()
    {
        $discipline = new Discipline();
        $fillable = $discipline->getFillable();

        $expectedFillable = [
            'activity_type_id',
            'name',
            'slug',
            'description',
            'min_participants',
            'max_participants',
            'duration_minutes',
            'equipment_required',
            'skill_levels',
            'base_price',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $discipline = new Discipline();
        $casts = $discipline->getCasts();

        $this->assertArrayHasKey('equipment_required', $casts);
        $this->assertArrayHasKey('skill_levels', $casts);
        $this->assertArrayHasKey('base_price', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('min_participants', $casts);
        $this->assertArrayHasKey('max_participants', $casts);
        $this->assertArrayHasKey('duration_minutes', $casts);
    }
}
