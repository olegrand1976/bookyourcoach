<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\ActivityType;
use App\Models\Lesson;
use App\Models\Location;
use App\Models\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class FacilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_facility()
    {
        $activityType = ActivityType::factory()->create();

        $facility = Facility::create([
            'activity_type_id' => $activityType->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'capacity' => 20,
            'dimensions' => ['length' => 50, 'width' => 30],
            'equipment' => ['mats', 'bars'],
            'description' => 'Test facility description',
            'is_active' => true
        ]);

        $this->assertInstanceOf(Facility::class, $facility);
        $this->assertEquals('Test Facility', $facility->name);
        $this->assertEquals('indoor', $facility->type);
        $this->assertEquals(20, $facility->capacity);
        $this->assertTrue($facility->is_active);
    }

    #[Test]
    public function test_belongs_to_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        $facility = Facility::factory()->create([
            'activity_type_id' => $activityType->id
        ]);

        $this->assertInstanceOf(ActivityType::class, $facility->activityType);
        $this->assertEquals($activityType->id, $facility->activityType->id);
    }

    #[Test]
    public function test_has_many_lessons()
    {
        $facility = Facility::factory()->create();
        $location = Location::factory()->create();
        $lesson = Lesson::factory()->create([
            'location_id' => $location->id
        ]);

        // Vérifier que la lesson existe
        $this->assertNotNull($lesson);
        $this->assertEquals($location->id, $lesson->location_id);
    }

    #[Test]
    public function test_has_many_availabilities()
    {
        $facility = Facility::factory()->create();
        $availability = Availability::factory()->create();

        // Vérifier que l'availability existe
        $this->assertNotNull($availability);
    }

    #[Test]
    public function test_scope_active()
    {
        Facility::factory()->create(['is_active' => true]);
        Facility::factory()->create(['is_active' => false]);

        $activeFacilities = Facility::active()->get();

        $this->assertCount(1, $activeFacilities);
        $this->assertTrue($activeFacilities->first()->is_active);
    }

    #[Test]
    public function test_scope_by_activity_type()
    {
        $activityType = ActivityType::factory()->create();
        Facility::factory()->create(['activity_type_id' => $activityType->id]);
        Facility::factory()->create(['activity_type_id' => ActivityType::factory()->create()->id]);

        $facilities = Facility::byActivityType($activityType->id)->get();

        $this->assertCount(1, $facilities);
        $this->assertEquals($activityType->id, $facilities->first()->activity_type_id);
    }

    #[Test]
    public function test_scope_by_type()
    {
        Facility::factory()->create(['type' => 'indoor']);
        Facility::factory()->create(['type' => 'outdoor']);

        $indoorFacilities = Facility::byType('indoor')->get();

        $this->assertCount(1, $indoorFacilities);
        $this->assertEquals('indoor', $indoorFacilities->first()->type);
    }

    #[Test]
    public function test_dimensions_casting()
    {
        $dimensions = ['length' => 50, 'width' => 30, 'height' => 10];
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'dimensions' => $dimensions,
            'is_active' => true
        ]);

        $this->assertIsArray($facility->dimensions);
        $this->assertEquals($dimensions, $facility->dimensions);
    }

    #[Test]
    public function test_equipment_casting()
    {
        $equipment = ['mats', 'bars', 'rings'];
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'equipment' => $equipment,
            'is_active' => true
        ]);

        $this->assertIsArray($facility->equipment);
        $this->assertEquals($equipment, $facility->equipment);
    }

    #[Test]
    public function test_get_capacity_attribute_with_null()
    {
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'capacity' => 10,
            'is_active' => true
        ]);

        $this->assertEquals(10, $facility->getCapacityAttribute($facility->capacity));
    }

    #[Test]
    public function test_get_dimensions_attribute_with_null()
    {
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'dimensions' => null,
            'is_active' => true
        ]);

        $this->assertEquals([], $facility->getDimensionsAttribute(null));
    }

    #[Test]
    public function test_get_equipment_attribute_with_null()
    {
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'equipment' => null,
            'is_active' => true
        ]);

        $this->assertEquals([], $facility->getEquipmentAttribute(null));
    }

    #[Test]
    public function test_capacity_casting()
    {
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'capacity' => 25,
            'is_active' => true
        ]);

        $this->assertIsInt($facility->capacity);
        $this->assertEquals(25, $facility->capacity);
    }

    #[Test]
    public function test_is_active_casting()
    {
        $facility = Facility::create([
            'activity_type_id' => ActivityType::factory()->create()->id,
            'name' => 'Test Facility',
            'type' => 'indoor',
            'is_active' => true
        ]);

        $this->assertIsBool($facility->is_active);
        $this->assertTrue($facility->is_active);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $facility = new Facility();
        $fillable = $facility->getFillable();

        $expectedFillable = [
            'activity_type_id',
            'name',
            'type',
            'capacity',
            'dimensions',
            'equipment',
            'description',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $facility = new Facility();
        $casts = $facility->getCasts();

        $this->assertArrayHasKey('dimensions', $casts);
        $this->assertArrayHasKey('equipment', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('capacity', $casts);
    }
}
