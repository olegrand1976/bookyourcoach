<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\AppSetting;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminSettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        // Créer un token pour l'admin
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_admin_can_update_general_settings()
    {
        $settingsData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Test Platform'],
                ['key' => 'general.contact_email', 'value' => 'test@example.com'],
                ['key' => 'general.contact_phone', 'value' => '+33 1 23 45 67 89'],
                ['key' => 'general.timezone', 'value' => 'Europe/Paris'],
                ['key' => 'general.company_address', 'value' => 'Test Address']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $settingsData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les paramètres ont été sauvegardés
        foreach ($settingsData['settings'] as $setting) {
            $this->assertDatabaseHas('app_settings', [
                'key' => $setting['key'],
                'value' => $setting['value'],
                'is_active' => true
            ]);
        }
    }

    public function test_admin_can_update_booking_settings()
    {
        $settingsData = [
            'settings' => [
                ['key' => 'booking.min_booking_hours', 'value' => '2'],
                ['key' => 'booking.max_booking_days', 'value' => '30'],
                ['key' => 'booking.cancellation_hours', 'value' => '24'],
                ['key' => 'booking.default_lesson_duration', 'value' => '60'],
                ['key' => 'booking.auto_confirm_bookings', 'value' => 'true'],
                ['key' => 'booking.send_reminder_emails', 'value' => 'true'],
                ['key' => 'booking.allow_student_cancellation', 'value' => 'true']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $settingsData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les paramètres ont été sauvegardés
        foreach ($settingsData['settings'] as $setting) {
            $this->assertDatabaseHas('app_settings', [
                'key' => $setting['key'],
                'value' => $setting['value'],
                'is_active' => true
            ]);
        }
    }

    public function test_non_admin_cannot_update_settings()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Test Platform']
            ]
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Access denied - Admin rights required'
                ]);
    }

    public function test_settings_update_validation_errors()
    {
        $invalidData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => str_repeat('a', 300)], // Trop long
                ['key' => 'general.contact_email', 'value' => 'invalid-email'], // Email invalide
                ['key' => 'booking.min_booking_hours', 'value' => '50'] // Valeur invalide
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_settings_update_without_token()
    {
        $response = $this->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Test Platform']
            ]
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Missing token'
                ]);
    }

    public function test_settings_update_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Test Platform']
            ]
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid token'
                ]);
    }

    public function test_settings_update_partial_data()
    {
        // Test avec seulement quelques champs
        $partialData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Updated Platform'],
                ['key' => 'general.contact_email', 'value' => 'updated@example.com']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $partialData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que seuls les champs fournis ont été mis à jour
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.platform_name',
            'value' => 'Updated Platform'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.contact_email',
            'value' => 'updated@example.com'
        ]);
    }
}
