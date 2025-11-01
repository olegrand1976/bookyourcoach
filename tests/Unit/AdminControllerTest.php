<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\AdminController;
use App\Models\User;
use App\Models\Club;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminController $adminController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminController = new AdminController();
    }

    /**
     * Test de la méthode getStats
     */
    public function test_get_stats_returns_correct_data(): void
    {
        // Authentifier un admin
        $this->actingAsAdmin();
        
        // Créer des données de test
        User::factory()->count(5)->create();
        Club::factory()->count(3)->create();

        $response = $this->adminController->getStats();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('stats', $responseData);
        $this->assertArrayHasKey('users', $responseData['stats']);
        $this->assertArrayHasKey('clubs', $responseData['stats']);
        // Compte les utilisateurs créés + l'admin authentifié = 6
        $this->assertGreaterThanOrEqual(5, $responseData['stats']['users']);
        $this->assertEquals(3, $responseData['stats']['clubs']);
    }

    /**
     * Test de la méthode getUsers avec filtres
     */
    public function test_get_users_with_filters(): void
    {
        // Authentifier un admin
        $this->actingAsAdmin();
        
        // Créer des utilisateurs de test
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'teacher']);
        User::factory()->create(['role' => 'student']);

        $request = new \Illuminate\Http\Request(['role' => 'teacher']);
        $response = $this->adminController->getUsers($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('current_page', $responseData);
        $this->assertCount(1, $responseData['data']); // Un seul teacher
    }

    /**
     * Test de la méthode getSettings
     */
    public function test_get_settings_returns_correct_data(): void
    {
        // Authentifier un admin
        $this->actingAsAdmin();
        
        // Créer des paramètres de test
        AppSetting::create([
            'group' => 'general',
            'key' => 'general.app_name',
            'value' => 'BookYourCoach',
            'type' => 'string'
        ]);

        $response = $this->adminController->getSettings('general');
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('app_name', $responseData);
        $this->assertEquals('BookYourCoach', $responseData['app_name']);
    }

    /**
     * Test de la méthode clearCache
     */
    public function test_clear_cache_returns_success(): void
    {
        // Authentifier un admin
        $this->actingAsAdmin();
        
        $response = $this->adminController->clearCache();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Cache vidé avec succès', $responseData['message']);
    }

    /**
     * Test de la méthode getAuditLogs
     */
    public function test_get_audit_logs_returns_correct_data(): void
    {
        // Authentifier un admin
        $this->actingAsAdmin();
        
        $request = new \Illuminate\Http\Request(['limit' => 10]);
        $response = $this->adminController->getAuditLogs($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('logs', $responseData);
    }
}
