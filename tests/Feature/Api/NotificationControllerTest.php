<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Notification;
use App\Models\Club;
use App\Models\Teacher;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_notifications_for_club_user()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/club/notifications');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'type',
                             'data',
                             'read_at',
                             'created_at',
                         ]
                     ]
                 ]);

        $notifications = $response->json('data');
        $this->assertGreaterThanOrEqual(8, count($notifications));
    }

    #[Test]
    public function it_can_list_notifications_for_teacher_user()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        
        Notification::factory()->count(4)->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->getJson('/api/teacher/notifications');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);

        $notifications = $response->json('data');
        $this->assertGreaterThanOrEqual(4, count($notifications));
    }

    #[Test]
    public function it_can_get_unread_count_for_club()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/api/club/notifications/unread-count');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'count',
                 ]);

        $this->assertEquals(5, $response->json('count'));
    }

    #[Test]
    public function it_can_get_unread_count_for_teacher()
    {
        // Arrange
        $user = $this->actingAsTeacher();
        
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        // Act
        $response = $this->getJson('/api/teacher/notifications/unread-count');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('count'));
    }

    #[Test]
    public function it_can_mark_notification_as_read()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        // Act
        $response = $this->postJson("/api/club/notifications/{$notification->id}/read");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification marquée comme lue',
                 ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull(Notification::find($notification->id)->read_at);
    }

    #[Test]
    public function it_cannot_mark_other_users_notification_as_read()
    {
        // Arrange
        $user = $this->actingAsClub();
        $otherUser = User::factory()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        // Act
        $response = $this->postJson("/api/club/notifications/{$notification->id}/read");

        // Assert
        $response->assertStatus(404); // Not found car la notification appartient à un autre utilisateur
    }

    #[Test]
    public function it_can_mark_all_notifications_as_read()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(), // Déjà lues
        ]);

        // Act
        $response = $this->postJson('/api/club/notifications/read-all');

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Toutes les notifications ont été marquées comme lues',
                 ]);

        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        $this->assertEquals(0, $unreadCount);
    }

    #[Test]
    public function it_requires_authentication_to_access_notifications()
    {
        // Act
        $response = $this->getJson('/api/club/notifications');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_appropriate_role_to_access_notifications()
    {
        // Arrange
        $studentUser = $this->actingAsStudent();

        // Act - Tentative d'accès aux notifications club
        $response = $this->getJson('/api/club/notifications');

        // Assert
        $response->assertStatus(403);
    }
}

