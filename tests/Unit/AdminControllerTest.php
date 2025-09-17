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
        // Créer des données de test
        User::factory()->count(5)->create();
        Club::factory()->count(3)->create();

        $stats = $this->adminController->getStats();

        $this->assertArrayHasKey('users', $stats);
        $this->assertArrayHasKey('clubs', $stats);
        $this->assertEquals(5, $stats['users']);
        $this->assertEquals(3, $stats['clubs']);
    }

    /**
     * Test de la méthode getUsers avec filtres
     */
    public function test_get_users_with_filters(): void
    {
        // Créer des utilisateurs de test
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'teacher']);
        User::factory()->create(['role' => 'student']);

        $request = new \Illuminate\Http\Request(['role' => 'teacher']);
        $users = $this->adminController->getUsers($request);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $users);
    }

    /**
     * Test de la méthode getSettings
     */
    public function test_get_settings_returns_correct_data(): void
    {
        // Créer des paramètres de test
        AppSetting::create([
            'type' => 'general',
            'key' => 'app_name',
            'value' => 'BookYourCoach',
            'data_type' => 'string'
        ]);

        $settings = $this->adminController->getSettings();

        $this->assertArrayHasKey('general', $settings);
        $this->assertEquals('BookYourCoach', $settings['general']['app_name']);
    }

    /**
     * Test de la méthode clearCache
     */
    public function test_clear_cache_returns_success(): void
    {
        $response = $this->adminController->clearCache();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    /**
     * Test de la méthode getAuditLogs
     */
    public function test_get_audit_logs_returns_correct_data(): void
    {
        $request = new \Illuminate\Http\Request(['limit' => 10]);
        $response = $this->adminController->getAuditLogs($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('logs', $responseData);
    }
}
