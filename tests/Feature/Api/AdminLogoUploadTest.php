<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'name' => 'Admin Test'
        ]);
        
        // Créer un token pour l'admin
        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_upload_logo_successfully()
    {
        // Créer un fichier image de test
        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        
        // Faire la requête d'upload
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        // Vérifier la réponse
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'logo_url'
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Logo uploadé avec succès'
                ]);

        // Vérifier que le fichier a été stocké
        $logoUrl = $response->json('logo_url');
        $this->assertStringContainsString('storage/logos/', $logoUrl);
        
        // Vérifier que le fichier existe physiquement
        $filename = basename($logoUrl);
        $this->assertTrue(Storage::disk('public')->exists('logos/' . $filename));
    }

    /** @test */
    public function admin_can_get_general_settings()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/settings/general');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'platform_name',
                    'logo_url',
                    'contact_email',
                    'contact_phone',
                    'timezone',
                    'company_address'
                ]);
    }

    /** @test */
    public function admin_can_update_general_settings()
    {
        $settings = [
            'platform_name' => 'BookYourCoach Test',
            'contact_email' => 'test@bookyourcoach.com',
            'contact_phone' => '+33 1 23 45 67 89',
            'timezone' => 'Europe/Paris',
            'company_address' => 'Test Address'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson('/api/admin/settings/general', $settings);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'settings'
                ])
                ->assertJson([
                    'message' => 'Paramètres mis à jour avec succès'
                ]);

        // Vérifier que les paramètres ont été sauvegardés
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.platform_name',
            'value' => 'BookYourCoach Test'
        ]);
    }

    /** @test */
    public function non_admin_cannot_upload_logo()
    {
        // Créer un utilisateur non-admin
        $user = User::factory()->create(['role' => 'student']);
        $userToken = $user->createToken('test-token')->plainTextToken;

        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Access denied - Admin rights required'
                ]);
    }

    /** @test */
    public function upload_logo_validates_file_type()
    {
        // Créer un fichier non-image
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo']);
    }

    /** @test */
    public function upload_logo_validates_file_size()
    {
        // Créer un fichier trop volumineux (>2MB)
        $file = UploadedFile::fake()->image('logo.png', 100, 100)->size(3000);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo']);
    }

    /** @test */
    public function settings_update_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson('/api/admin/settings/general', [
            'platform_name' => '', // Champ requis vide
            'contact_email' => 'invalid-email' // Email invalide
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform_name', 'contact_email']);
    }

    /** @test */
    public function settings_update_handles_invalid_type()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson('/api/admin/settings/invalid-type', [
            'test' => 'value'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Invalid settings type'
                ]);
    }

    /** @test */
    public function logo_upload_updates_app_settings()
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        $response->assertStatus(200);
        
        // Vérifier que le logo_url a été mis à jour dans les paramètres
        $logoUrl = $response->json('logo_url');
        $this->assertDatabaseHas('app_settings', [
            'key' => 'general.logo_url',
            'value' => '/storage/logos/' . basename($logoUrl)
        ]);
    }

    /** @test */
    public function complete_logo_upload_and_settings_workflow()
    {
        // 1. Upload du logo
        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        
        $uploadResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/admin/upload-logo', [
            'logo' => $file
        ]);

        $uploadResponse->assertStatus(200);
        $logoUrl = $uploadResponse->json('logo_url');

        // 2. Mettre à jour les paramètres avec le nouveau logo
        $settings = [
            'platform_name' => 'BookYourCoach',
            'logo_url' => $logoUrl,
            'contact_email' => 'contact@bookyourcoach.com',
            'contact_phone' => '+33 1 23 45 67 89',
            'timezone' => 'Europe/Paris',
            'company_address' => 'Paris, France'
        ];

        $settingsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson('/api/admin/settings/general', $settings);

        $settingsResponse->assertStatus(200);

        // 3. Vérifier que les paramètres ont été sauvegardés
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/settings/general');

        $getResponse->assertStatus(200)
                   ->assertJson([
                       'platform_name' => 'BookYourCoach',
                       'logo_url' => $logoUrl,
                       'contact_email' => 'contact@bookyourcoach.com'
                   ]);
    }
}
