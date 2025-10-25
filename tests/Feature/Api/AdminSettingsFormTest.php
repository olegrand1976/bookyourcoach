<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\AppSetting;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminSettingsFormTest extends TestCase
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

    public function test_admin_can_get_general_settings_with_default_logo()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/settings/general');

        $response->assertStatus(200)
                ->assertJson([
                    'platform_name' => 'activibe',
                    'logo_url' => '/logo-activibe.svg',
                    'contact_email' => 'contact@activibe.fr',
                    'contact_phone' => '+33 1 23 45 67 89',
                    'timezone' => 'Europe/Brussels',
                    'company_address' => 'activibe\nBelgique'
                ]);
    }

    public function test_admin_can_save_general_settings_form()
    {
        $formData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Activibe Pro'],
                ['key' => 'general.contact_email', 'value' => 'pro@activibe.fr'],
                ['key' => 'general.contact_phone', 'value' => '+33 1 98 76 54 32'],
                ['key' => 'general.timezone', 'value' => 'Europe/Paris'],
                ['key' => 'general.company_address', 'value' => 'Activibe Pro\n123 Rue de la Forme\n75001 Paris\nFrance']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les données ont été sauvegardées
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.platform_name',
            'value' => 'Activibe Pro'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.contact_email',
            'value' => 'pro@activibe.fr'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.contact_phone',
            'value' => '+33 1 98 76 54 32'
        ]);
    }

    public function test_admin_can_save_booking_settings_form()
    {
        $formData = [
            'settings' => [
                ['key' => 'booking.min_booking_hours', 'value' => '3'],
                ['key' => 'booking.max_booking_days', 'value' => '60'],
                ['key' => 'booking.cancellation_hours', 'value' => '48'],
                ['key' => 'booking.default_lesson_duration', 'value' => '90'],
                ['key' => 'booking.auto_confirm_bookings', 'value' => 'false'],
                ['key' => 'booking.send_reminder_emails', 'value' => 'true'],
                ['key' => 'booking.allow_student_cancellation', 'value' => 'false']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les données ont été sauvegardées
        $this->assertDatabaseHas('app_settings', [
            'key' => 'booking.min_booking_hours',
            'value' => '3'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'booking.auto_confirm_bookings',
            'value' => 'false'
        ]);
    }

    public function test_admin_can_save_payment_settings_form()
    {
        $formData = [
            'settings' => [
                ['key' => 'payment.platform_commission', 'value' => '15'],
                ['key' => 'payment.vat_rate', 'value' => '20'],
                ['key' => 'payment.default_currency', 'value' => 'USD'],
                ['key' => 'payment.payout_delay_days', 'value' => '14'],
                ['key' => 'payment.stripe_enabled', 'value' => 'false'],
                ['key' => 'payment.auto_payout', 'value' => 'true']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les données ont été sauvegardées
        $this->assertDatabaseHas('app_settings', [
            'key' => 'payment.platform_commission',
            'value' => '15'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'payment.default_currency',
            'value' => 'USD'
        ]);
    }

    public function test_admin_can_save_notifications_settings_form()
    {
        $formData = [
            'settings' => [
                ['key' => 'notifications.email_new_booking', 'value' => 'false'],
                ['key' => 'notifications.email_booking_cancelled', 'value' => 'true'],
                ['key' => 'notifications.email_payment_received', 'value' => 'false'],
                ['key' => 'notifications.email_lesson_reminder', 'value' => 'true'],
                ['key' => 'notifications.sms_new_booking', 'value' => 'true'],
                ['key' => 'notifications.sms_lesson_reminder', 'value' => 'false']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paramètres sauvegardés avec succès'
                ]);

        // Vérifier que les données ont été sauvegardées
        $this->assertDatabaseHas('app_settings', [
            'key' => 'notifications.email_new_booking',
            'value' => 'false'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'notifications.sms_new_booking',
            'value' => 'true'
        ]);
    }

    public function test_settings_form_validation_email()
    {
        $formData = [
            'settings' => [
                ['key' => 'general.contact_email', 'value' => 'invalid-email-format']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_settings_form_validation_platform_name_length()
    {
        $formData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => str_repeat('a', 300)]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_settings_form_validation_booking_hours()
    {
        $formData = [
            'settings' => [
                ['key' => 'booking.min_booking_hours', 'value' => '50']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_complete_settings_form_workflow()
    {
        // 1. Récupérer les paramètres par défaut
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/settings/general');

        $getResponse->assertStatus(200)
                   ->assertJson([
                       'platform_name' => 'activibe',
                       'logo_url' => '/logo-activibe.svg'
                   ]);

        // 2. Modifier les paramètres
        $formData = [
            'settings' => [
                ['key' => 'general.platform_name', 'value' => 'Activibe Test'],
                ['key' => 'general.contact_email', 'value' => 'test@activibe.fr'],
                ['key' => 'booking.min_booking_hours', 'value' => '4'],
                ['key' => 'payment.platform_commission', 'value' => '12']
            ]
        ];

        $putResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $putResponse->assertStatus(200)
                   ->assertJson([
                       'success' => true,
                       'message' => 'Paramètres sauvegardés avec succès'
                   ]);

        // 3. Vérifier que les modifications ont été sauvegardées
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.platform_name',
            'value' => 'Activibe Test'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'booking.min_booking_hours',
            'value' => '4'
        ]);
        
        $this->assertDatabaseHas('app_settings', [
            'key' => 'payment.platform_commission',
            'value' => '12'
        ]);
    }

    public function test_logo_url_is_not_editable_via_form()
    {
        // Le logo_url ne devrait pas être modifiable via le formulaire
        // car il est maintenant fixe (/logo-activibe.svg)
        
        $formData = [
            'settings' => [
                ['key' => 'general.logo_url', 'value' => '/custom-logo.svg']
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson('/api/admin/settings', $formData);

        $response->assertStatus(200);

        // Vérifier que le logo_url par défaut est toujours retourné
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/settings/general');

        $getResponse->assertStatus(200)
                   ->assertJson([
                       'logo_url' => '/logo-activibe.svg'
                   ]);
    }
}
