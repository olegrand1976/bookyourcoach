<?php

namespace Tests\Unit\Models;

use App\Models\ClubSettings;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_club_settings()
    {
        $club = Club::factory()->create();

        $settings = ClubSettings::create([
            'club_id' => $club->id,
            'feature_key' => 'test_feature',
            'feature_name' => 'Test Feature',
            'feature_category' => 'general',
            'is_enabled' => true,
            'configuration' => ['key1' => 'value1'],
            'description' => 'Test feature description',
            'icon' => 'test-icon',
            'sort_order' => 1
        ]);

        $this->assertInstanceOf(ClubSettings::class, $settings);
        $this->assertEquals($club->id, $settings->club_id);
        $this->assertEquals('test_feature', $settings->feature_key);
        $this->assertEquals('Test Feature', $settings->feature_name);
        $this->assertTrue($settings->is_enabled);
    }

    public function test_belongs_to_club()
    {
        $club = Club::factory()->create();
        $settings = ClubSettings::factory()->create([
            'club_id' => $club->id
        ]);

        $this->assertInstanceOf(Club::class, $settings->club);
        $this->assertEquals($club->id, $settings->club->id);
    }

    public function test_scope_by_category()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'general'
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'advanced'
        ]);

        $generalSettings = ClubSettings::byCategory('general')->get();

        $this->assertCount(1, $generalSettings);
        $this->assertEquals('general', $generalSettings->first()->feature_category);
    }

    public function test_scope_enabled()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'is_enabled' => true
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'is_enabled' => false
        ]);

        $enabledSettings = ClubSettings::enabled()->get();

        $this->assertCount(1, $enabledSettings);
        $this->assertTrue($enabledSettings->first()->is_enabled);
    }

    public function test_scope_disabled()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'is_enabled' => true
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'is_enabled' => false
        ]);

        $disabledSettings = ClubSettings::disabled()->get();

        $this->assertCount(1, $disabledSettings);
        $this->assertFalse($disabledSettings->first()->is_enabled);
    }

    public function test_scope_ordered()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'sort_order' => 2,
            'feature_name' => 'Feature B'
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'sort_order' => 1,
            'feature_name' => 'Feature A'
        ]);

        $orderedSettings = ClubSettings::ordered()->get();

        $this->assertEquals('Feature A', $orderedSettings->first()->feature_name);
        $this->assertEquals('Feature B', $orderedSettings->last()->feature_name);
    }

    public function test_enable_method()
    {
        $settings = ClubSettings::factory()->create(['is_enabled' => false]);

        $settings->enable();

        $this->assertTrue($settings->fresh()->is_enabled);
    }

    public function test_disable_method()
    {
        $settings = ClubSettings::factory()->create(['is_enabled' => true]);

        $settings->disable();

        $this->assertFalse($settings->fresh()->is_enabled);
    }

    public function test_toggle_method()
    {
        $settings = ClubSettings::factory()->create(['is_enabled' => false]);

        $settings->toggle();

        $this->assertTrue($settings->fresh()->is_enabled);

        $settings->toggle();

        $this->assertFalse($settings->fresh()->is_enabled);
    }

    public function test_update_configuration()
    {
        $settings = ClubSettings::factory()->create([
            'configuration' => ['key1' => 'value1']
        ]);

        $settings->updateConfiguration(['key2' => 'value2']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2'
        ], $settings->fresh()->configuration);
    }

    public function test_get_configuration_value()
    {
        $settings = ClubSettings::factory()->create([
            'configuration' => ['nested' => ['key' => 'value']]
        ]);

        $this->assertEquals('value', $settings->getConfigurationValue('nested.key'));
        $this->assertEquals('default', $settings->getConfigurationValue('nonexistent', 'default'));
    }

    public function test_set_configuration_value()
    {
        $settings = ClubSettings::factory()->create([
            'configuration' => ['key1' => 'value1']
        ]);

        $settings->setConfigurationValue('key2', 'value2');

        $this->assertEquals('value2', $settings->fresh()->configuration['key2']);
    }

    public function test_is_feature_enabled_static_method()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_key' => 'test_feature',
            'is_enabled' => true
        ]);

        $this->assertTrue(ClubSettings::isFeatureEnabled($club->id, 'test_feature'));
        $this->assertFalse(ClubSettings::isFeatureEnabled($club->id, 'nonexistent_feature'));
    }

    public function test_get_enabled_features_static_method()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_key' => 'feature1',
            'is_enabled' => true
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_key' => 'feature2',
            'is_enabled' => false
        ]);

        $enabledFeatures = ClubSettings::getEnabledFeatures($club->id);

        $this->assertCount(1, $enabledFeatures);
        $this->assertContains('feature1', $enabledFeatures);
    }

    public function test_get_features_by_category_static_method()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'general',
            'feature_name' => 'General Feature'
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'advanced',
            'feature_name' => 'Advanced Feature'
        ]);

        $generalFeatures = ClubSettings::getFeaturesByCategory($club->id, 'general');

        $this->assertCount(1, $generalFeatures);
        $this->assertEquals('General Feature', $generalFeatures->first()->feature_name);
    }

    public function test_get_all_features_static_method()
    {
        $club = Club::factory()->create();
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'general',
            'feature_name' => 'General Feature'
        ]);
        ClubSettings::factory()->create([
            'club_id' => $club->id,
            'feature_category' => 'advanced',
            'feature_name' => 'Advanced Feature'
        ]);

        $allFeatures = ClubSettings::getAllFeatures($club->id);

        $this->assertCount(2, $allFeatures);
        $this->assertArrayHasKey('general', $allFeatures->toArray());
        $this->assertArrayHasKey('advanced', $allFeatures->toArray());
    }

    public function test_configuration_casting()
    {
        $configuration = ['key1' => 'value1', 'key2' => 'value2'];
        $settings = ClubSettings::factory()->create([
            'configuration' => $configuration
        ]);

        $this->assertIsArray($settings->configuration);
        $this->assertEquals($configuration, $settings->configuration);
    }

    public function test_fillable_attributes()
    {
        $settings = new ClubSettings();
        $fillable = $settings->getFillable();

        $expectedFillable = [
            'club_id',
            'feature_key',
            'feature_name',
            'feature_category',
            'is_enabled',
            'configuration',
            'description',
            'icon',
            'sort_order'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_casts()
    {
        $settings = new ClubSettings();
        $casts = $settings->getCasts();

        $this->assertArrayHasKey('is_enabled', $casts);
        $this->assertArrayHasKey('configuration', $casts);
        $this->assertArrayHasKey('sort_order', $casts);
    }
}
