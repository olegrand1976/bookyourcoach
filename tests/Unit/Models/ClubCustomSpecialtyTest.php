<?php

namespace Tests\Unit\Models;

use App\Models\ClubCustomSpecialty;
use App\Models\Club;
use App\Models\ActivityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubCustomSpecialtyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_club_custom_specialty()
    {
        $club = Club::factory()->create();
        $activityType = ActivityType::factory()->create();

        $specialty = ClubCustomSpecialty::create([
            'club_id' => $club->id,
            'activity_type_id' => $activityType->id,
            'name' => 'Specialty Test',
            'description' => 'Test specialty description',
            'duration_minutes' => 60,
            'base_price' => 25.50,
            'skill_levels' => ['beginner', 'intermediate'],
            'min_participants' => 2,
            'max_participants' => 8,
            'equipment_required' => ['helmet', 'boots'],
            'is_active' => true
        ]);

        $this->assertInstanceOf(ClubCustomSpecialty::class, $specialty);
        $this->assertEquals('Specialty Test', $specialty->name);
        $this->assertEquals($club->id, $specialty->club_id);
        $this->assertEquals($activityType->id, $specialty->activity_type_id);
        $this->assertEquals(60, $specialty->duration_minutes);
        $this->assertEquals(25.50, $specialty->base_price);
        $this->assertTrue($specialty->is_active);
    }

    public function test_belongs_to_club()
    {
        $club = Club::factory()->create();
        $specialty = ClubCustomSpecialty::factory()->create([
            'club_id' => $club->id
        ]);

        $this->assertInstanceOf(Club::class, $specialty->club);
        $this->assertEquals($club->id, $specialty->club->id);
    }

    public function test_belongs_to_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        $specialty = ClubCustomSpecialty::factory()->create([
            'activity_type_id' => $activityType->id
        ]);

        $this->assertInstanceOf(ActivityType::class, $specialty->activityType);
        $this->assertEquals($activityType->id, $specialty->activityType->id);
    }

    public function test_skill_levels_casting()
    {
        $skillLevels = ['beginner', 'intermediate', 'advanced'];
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'skill_levels' => $skillLevels,
            'is_active' => true
        ]);

        $this->assertIsArray($specialty->skill_levels);
        $this->assertEquals($skillLevels, $specialty->skill_levels);
    }

    public function test_equipment_required_casting()
    {
        $equipment = ['helmet', 'boots', 'gloves'];
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'equipment_required' => $equipment,
            'is_active' => true
        ]);

        $this->assertIsArray($specialty->equipment_required);
        $this->assertEquals($equipment, $specialty->equipment_required);
    }

    public function test_base_price_casting()
    {
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'base_price' => 25.50,
            'is_active' => true
        ]);

        $this->assertIsFloat($specialty->base_price);
        $this->assertEquals(25.50, $specialty->base_price);
    }

    public function test_duration_minutes_casting()
    {
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'duration_minutes' => 90,
            'is_active' => true
        ]);

        $this->assertIsInt($specialty->duration_minutes);
        $this->assertEquals(90, $specialty->duration_minutes);
    }

    public function test_min_max_participants_casting()
    {
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'min_participants' => 2,
            'max_participants' => 10,
            'is_active' => true
        ]);

        $this->assertIsInt($specialty->min_participants);
        $this->assertIsInt($specialty->max_participants);
        $this->assertEquals(2, $specialty->min_participants);
        $this->assertEquals(10, $specialty->max_participants);
    }

    public function test_is_active_casting()
    {
        $specialty = ClubCustomSpecialty::create([
            'club_id' => Club::factory()->create()->id,
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Specialty',
            'is_active' => true
        ]);

        $this->assertIsBool($specialty->is_active);
        $this->assertTrue($specialty->is_active);
    }

    public function test_fillable_attributes()
    {
        $specialty = new ClubCustomSpecialty();
        $fillable = $specialty->getFillable();

        $expectedFillable = [
            'club_id',
            'activity_type_id',
            'name',
            'description',
            'duration_minutes',
            'base_price',
            'skill_levels',
            'min_participants',
            'max_participants',
            'equipment_required',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $specialty = new ClubCustomSpecialty();
        $casts = $specialty->getCasts();

        $this->assertArrayHasKey('skill_levels', $casts);
        $this->assertArrayHasKey('equipment_required', $casts);
        $this->assertArrayHasKey('base_price', $casts);
        $this->assertArrayHasKey('duration_minutes', $casts);
        $this->assertArrayHasKey('min_participants', $casts);
        $this->assertArrayHasKey('max_participants', $casts);
        $this->assertArrayHasKey('is_active', $casts);
    }
}
