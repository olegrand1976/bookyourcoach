<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ActivityType;
use App\Models\Facility;
use App\Models\Discipline;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_activity_type()
    {
        $activityType = ActivityType::create([
            'name' => 'Équitation',
            'slug' => 'equestrian',
            'description' => 'Club d\'équitation',
            'icon' => '🐎',
            'color' => '#8B4513',
            'is_active' => true
        ]);

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Équitation',
            'slug' => 'equestrian',
            'icon' => '🐎',
            'color' => '#8B4513'
        ]);
    }

    public function test_activity_type_has_facilities()
    {
        $activityType = ActivityType::factory()->create();
        $facility = Facility::factory()->create(['activity_type_id' => $activityType->id]);

        $this->assertTrue($activityType->facilities->contains($facility));
        $this->assertEquals(1, $activityType->facilities->count());
    }

    public function test_activity_type_has_disciplines()
    {
        $activityType = ActivityType::factory()->create();
        $discipline = Discipline::factory()->create(['activity_type_id' => $activityType->id]);

        $this->assertTrue($activityType->disciplines->contains($discipline));
        $this->assertEquals(1, $activityType->disciplines->count());
    }

    public function test_activity_type_has_clubs()
    {
        $activityType = ActivityType::factory()->create();
        $club = Club::factory()->create(['activity_type_id' => $activityType->id]);

        $this->assertTrue($activityType->clubs->contains($club));
        $this->assertEquals(1, $activityType->clubs->count());
    }

    public function test_scope_active()
    {
        ActivityType::factory()->create(['is_active' => true]);
        ActivityType::factory()->create(['is_active' => false]);

        $activeTypes = ActivityType::active()->get();

        $this->assertEquals(1, $activeTypes->count());
        $this->assertTrue($activeTypes->first()->is_active);
    }

    public function test_scope_by_slug()
    {
        ActivityType::factory()->create(['slug' => 'equestrian']);
        ActivityType::factory()->create(['slug' => 'swimming']);

        $equestrian = ActivityType::bySlug('equestrian')->first();

        $this->assertNotNull($equestrian);
        $this->assertEquals('equestrian', $equestrian->slug);
    }

    public function test_default_icon()
    {
        $activityType = ActivityType::create([
            'name' => 'Test',
            'slug' => 'test',
            'icon' => null
        ]);

        $this->assertEquals('🏃‍♂️', $activityType->getIconAttribute(null));
    }

    public function test_default_color()
    {
        $activityType = ActivityType::create([
            'name' => 'Test',
            'slug' => 'test',
            'color' => null
        ]);

        $this->assertEquals('#6B7280', $activityType->getColorAttribute(null));
    }
}
