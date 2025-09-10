<?php

namespace Tests\Unit\Models;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_app_setting()
    {
        $setting = AppSetting::create([
            'app_name' => 'Test App',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
            'accent_color' => '#3b82f6',
            'is_active' => true
        ]);

        $this->assertInstanceOf(AppSetting::class, $setting);
        $this->assertEquals('Test App', $setting->app_name);
        $this->assertEquals('#2563eb', $setting->primary_color);
        $this->assertTrue($setting->is_active);
    }

    public function test_get_active_settings_returns_active_setting()
    {
        // Créer un paramètre actif
        AppSetting::create([
            'app_name' => 'Active App',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
            'accent_color' => '#3b82f6',
            'is_active' => true
        ]);

        // Créer un paramètre inactif
        AppSetting::create([
            'app_name' => 'Inactive App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'is_active' => false
        ]);

        $activeSetting = AppSetting::getActiveSettings();

        $this->assertNotNull($activeSetting);
        $this->assertEquals('Active App', $activeSetting->app_name);
        $this->assertTrue($activeSetting->is_active);
    }

    public function test_get_or_create_default_creates_default_when_none_exists()
    {
        $setting = AppSetting::getOrCreateDefault();

        $this->assertInstanceOf(AppSetting::class, $setting);
        $this->assertEquals('activibe', $setting->app_name);
        $this->assertEquals('#2563eb', $setting->primary_color);
        $this->assertTrue($setting->is_active);
    }

    public function test_get_or_create_default_returns_existing_active_setting()
    {
        // Créer un paramètre actif existant
        $existingSetting = AppSetting::create([
            'app_name' => 'Existing App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'is_active' => true
        ]);

        $setting = AppSetting::getOrCreateDefault();

        $this->assertEquals($existingSetting->id, $setting->id);
        $this->assertEquals('Existing App', $setting->app_name);
    }

    public function test_primary_color_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La couleur doit être au format hexadécimal (#000000)');

        AppSetting::create([
            'app_name' => 'Test App',
            'primary_color' => 'invalid-color',
            'secondary_color' => '#1e40af',
            'accent_color' => '#3b82f6',
            'is_active' => true
        ]);
    }

    public function test_secondary_color_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La couleur doit être au format hexadécimal (#000000)');

        AppSetting::create([
            'app_name' => 'Test App',
            'primary_color' => '#2563eb',
            'secondary_color' => 'invalid-color',
            'accent_color' => '#3b82f6',
            'is_active' => true
        ]);
    }

    public function test_accent_color_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La couleur doit être au format hexadécimal (#000000)');

        AppSetting::create([
            'app_name' => 'Test App',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
            'accent_color' => 'invalid-color',
            'is_active' => true
        ]);
    }

    public function test_social_links_casting()
    {
        $socialLinks = [
            'facebook' => 'https://facebook.com/test',
            'twitter' => 'https://twitter.com/test'
        ];

        $setting = AppSetting::create([
            'app_name' => 'Test App',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
            'accent_color' => '#3b82f6',
            'social_links' => $socialLinks,
            'is_active' => true
        ]);

        $this->assertIsArray($setting->social_links);
        $this->assertEquals($socialLinks, $setting->social_links);
    }

    public function test_key_value_system()
    {
        $setting = AppSetting::create([
            'key' => 'test_key',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'general',
            'is_active' => true
        ]);

        $this->assertEquals('test_key', $setting->key);
        $this->assertEquals('test_value', $setting->value);
        $this->assertEquals('string', $setting->type);
        $this->assertEquals('general', $setting->group);
    }
}
