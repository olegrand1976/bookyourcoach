<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;


class AdminStatsTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function admin_can_view_platform_stats()
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/stats');


        $response->assertOk()
                 ->assertJsonStructure([
                     'stats' => [
                         'users',
                         'teachers',
                         'students',
                         'clubs',
                         'active_users',
                         'lessons_today',
                         'revenue_month',
                     ],
                     'recentUsers',
                     'recentActivities',
                 ]);
    }

    #[Test]
    public function admin_can_list_users()
    {
        $admin = $this->actingAsAdmin();

        // create some users
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/users');


        $response->assertOk()
                 ->assertJsonFragment(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }
}