<?php

namespace Tests\Feature\Api;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AppSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_index_returns_active_settings()
    {
        $settings = AppSetting::factory()->create([
            'app_name' => 'Test App',
            'primary_color' => '#2563eb',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/app-settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'app_name' => 'Test App',
                    'primary_color' => '#2563eb',
                    'is_active' => true
                ]
            ]);
    }

    #[Test]
    public function test_index_creates_default_settings_when_none_exist()
    {
        $response = $this->getJson('/api/app-settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'app_name' => 'activibe',
                    'primary_color' => '#2563eb',
                    'is_active' => true
                ]
            ]);
    }

    #[Test]
    public function test_show_returns_setting_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = AppSetting::factory()->create();

        $response = $this->actingAs($admin)->getJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $settings->id,
                    'app_name' => $settings->app_name
                ]
            ]);
    }

    #[Test]
    public function test_show_denies_access_for_non_admin()
    {
        $user = User::factory()->create(['role' => 'student']);
        $settings = AppSetting::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Accès non autorisé'
            ]);
    }

    #[Test]
    public function test_show_denies_access_for_unauthenticated_user()
    {
        $settings = AppSetting::factory()->create();

        $response = $this->getJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Accès non autorisé'
            ]);
    }

    #[Test]
    public function test_store_creates_new_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $data = [
            'app_name' => 'New App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'is_active' => true
        ];

        $response = $this->actingAs($admin)->postJson('/api/app-settings', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Paramètres d\'application créés avec succès',
                'data' => [
                    'app_name' => 'New App',
                    'primary_color' => '#ff0000',
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('app_settings', [
            'app_name' => 'New App',
            'primary_color' => '#ff0000',
            'is_active' => true
        ]);
    }

    #[Test]
    public function test_store_deactivates_other_settings_when_creating_active()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Créer des paramètres actifs existants
        $existingSettings = AppSetting::factory()->create(['is_active' => true]);

        $data = [
            'app_name' => 'New App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'is_active' => true
        ];

        $response = $this->actingAs($admin)->postJson('/api/app-settings', $data);

        $response->assertStatus(201);

        // Vérifier que les anciens paramètres sont désactivés
        $this->assertDatabaseHas('app_settings', [
            'id' => $existingSettings->id,
            'is_active' => false
        ]);

        // Vérifier que les nouveaux paramètres sont actifs
        $this->assertDatabaseHas('app_settings', [
            'app_name' => 'New App',
            'is_active' => true
        ]);
    }

    #[Test]
    public function test_store_validates_required_fields()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/app-settings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['app_name', 'primary_color', 'secondary_color', 'accent_color']);
    }

    #[Test]
    public function test_store_validates_color_format()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $data = [
            'app_name' => 'Test App',
            'primary_color' => 'invalid-color',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff'
        ];

        $response = $this->actingAs($admin)->postJson('/api/app-settings', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['primary_color']);
    }

    #[Test]
    public function test_update_modifies_existing_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = AppSetting::factory()->create([
            'app_name' => 'Original App',
            'primary_color' => '#2563eb'
        ]);

        $data = [
            'app_name' => 'Updated App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'is_active' => $settings->is_active
        ];

        $response = $this->actingAs($admin)->putJson("/api/app-settings/{$settings->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Paramètres d\'application mis à jour avec succès',
                'data' => [
                    'app_name' => 'Updated App',
                    'primary_color' => '#ff0000'
                ]
            ]);

        $this->assertDatabaseHas('app_settings', [
            'id' => $settings->id,
            'app_name' => 'Updated App',
            'primary_color' => '#ff0000'
        ]);
    }

    #[Test]
    public function test_update_deactivates_other_settings_when_activating()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $settingsToUpdate = AppSetting::factory()->create(['is_active' => false]);
        $otherSettings = AppSetting::factory()->create(['is_active' => true]);

        $data = [
            'app_name' => $settingsToUpdate->app_name,
            'primary_color' => $settingsToUpdate->primary_color,
            'secondary_color' => $settingsToUpdate->secondary_color,
            'accent_color' => $settingsToUpdate->accent_color,
            'is_active' => true
        ];

        $response = $this->actingAs($admin)->putJson("/api/app-settings/{$settingsToUpdate->id}", $data);

        $response->assertStatus(200);

        // Vérifier que les autres paramètres sont désactivés
        $this->assertDatabaseHas('app_settings', [
            'id' => $otherSettings->id,
            'is_active' => false
        ]);

        // Vérifier que les paramètres mis à jour sont actifs
        $this->assertDatabaseHas('app_settings', [
            'id' => $settingsToUpdate->id,
            'is_active' => true
        ]);
    }

    #[Test]
    public function test_destroy_deletes_inactive_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = AppSetting::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->deleteJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Paramètres d\'application supprimés avec succès'
            ]);

        $this->assertDatabaseMissing('app_settings', [
            'id' => $settings->id
        ]);
    }

    #[Test]
    public function test_destroy_prevents_deletion_of_active_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $settings = AppSetting::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->deleteJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Impossible de supprimer les paramètres d\'application actifs'
            ]);

        $this->assertDatabaseHas('app_settings', [
            'id' => $settings->id
        ]);
    }

    #[Test]
    public function test_destroy_denies_access_for_non_admin()
    {
        $user = User::factory()->create(['role' => 'student']);
        $settings = AppSetting::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->deleteJson("/api/app-settings/{$settings->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Accès non autorisé'
            ]);
    }

    #[Test]
    public function test_activate_activates_specific_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $settingsToActivate = AppSetting::factory()->create(['is_active' => false]);
        $otherSettings = AppSetting::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->postJson("/api/app-settings/{$settingsToActivate->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Paramètres d\'application activés avec succès',
                'data' => [
                    'id' => $settingsToActivate->id,
                    'is_active' => true
                ]
            ]);

        // Vérifier que les autres paramètres sont désactivés
        $this->assertDatabaseHas('app_settings', [
            'id' => $otherSettings->id,
            'is_active' => false
        ]);

        // Vérifier que les paramètres spécifiés sont actifs
        $this->assertDatabaseHas('app_settings', [
            'id' => $settingsToActivate->id,
            'is_active' => true
        ]);
    }

    #[Test]
    public function test_activate_denies_access_for_non_admin()
    {
        $user = User::factory()->create(['role' => 'student']);
        $settings = AppSetting::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/app-settings/{$settings->id}/activate");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Accès non autorisé'
            ]);
    }
}
