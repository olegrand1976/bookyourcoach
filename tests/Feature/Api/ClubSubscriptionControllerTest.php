<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\CourseType;
use App\Models\Discipline;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;

class ClubSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_subscriptions()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        Subscription::factory()->count(3)->create([
            'club_id' => $club->id,
            'is_active' => true,
        ]);

        // Act
        $response = $this->getJson('/api/club/subscriptions');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'club_id',
                             'name',
                             'price',
                             'total_lessons',
                             'is_active',
                         ]
                     ]
                 ]);

        $subscriptions = $response->json('data');
        $this->assertCount(3, $subscriptions);
    }

    #[Test]
    public function it_can_create_subscription()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $discipline = Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);

        $subscriptionData = [
            'name' => 'Abonnement Mensuel',
            'total_lessons' => 10,
            'free_lessons' => 1,
            'price' => 250.00,
            'description' => 'Abonnement pour 10 cours',
            'is_active' => true,
            'course_type_ids' => [$courseType->id],
        ];

        // Act
        $response = $this->postJson('/api/club/subscriptions', $subscriptionData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'price',
                         'total_lessons',
                     ]
                 ]);

        $this->assertDatabaseHas('subscriptions', [
            'club_id' => $club->id,
            'name' => 'Abonnement Mensuel',
            'price' => 250.00,
        ]);
    }

    #[Test]
    public function it_validates_subscription_creation_data()
    {
        // Arrange
        $user = $this->actingAsClub();

        // Act
        $response = $this->postJson('/api/club/subscriptions', []);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'total_lessons', 'price', 'course_type_ids']);
    }

    #[Test]
    public function it_can_show_subscription()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $subscription = Subscription::factory()->create([
            'club_id' => $club->id,
        ]);

        // Act
        $response = $this->getJson("/api/club/subscriptions/{$subscription->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'price',
                         'club_id',
                     ]
                 ])
                 ->assertJsonFragment([
                     'id' => $subscription->id,
                 ]);
    }

    #[Test]
    public function it_can_update_subscription()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $subscription = Subscription::factory()->create([
            'club_id' => $club->id,
            'price' => 200.00,
        ]);

        $updateData = [
            'name' => 'Abonnement Modifié',
            'price' => 300.00,
        ];

        // Act
        $response = $this->putJson("/api/club/subscriptions/{$subscription->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => 'Abonnement Modifié',
            'price' => 300.00,
        ]);
    }

    #[Test]
    public function it_can_delete_subscription()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $subscription = Subscription::factory()->create([
            'club_id' => $club->id,
        ]);

        // Act
        $response = $this->deleteJson("/api/club/subscriptions/{$subscription->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_can_assign_subscription_to_student()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $subscription = Subscription::factory()->create([
            'club_id' => $club->id,
            'total_lessons' => 10,
        ]);

        $student = Student::factory()->create(['club_id' => $club->id]);
        
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignData = [
            'subscription_id' => $subscription->id,
            'student_id' => $student->id,
            'start_date' => now()->format('Y-m-d'),
        ];

        // Act
        $response = $this->postJson('/api/club/subscriptions/assign', $assignData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'subscription_id',
                         'student_id',
                     ]
                 ]);

        // Vérifier que l'instance existe et que l'élève y est attaché via la table pivot
        $this->assertDatabaseHas('subscription_instances', [
            'subscription_id' => $subscription->id,
        ]);
        
        $instance = \App\Models\SubscriptionInstance::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($instance);
        $this->assertTrue($instance->students->contains($student->id));
    }

    #[Test]
    public function it_can_get_student_subscriptions()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);

        $student = Student::factory()->create(['club_id' => $club->id]);
        
        DB::table('club_students')->insert([
            'club_id' => $club->id,
            'student_id' => $student->id,
            'is_active' => true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subscription = Subscription::factory()->create([
            'club_id' => $club->id,
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now(),
            'status' => 'active',
        ]);
        
        // Attacher l'élève via la relation many-to-many
        $instance->students()->attach($student->id);

        // Act
        $response = $this->getJson("/api/club/students/{$student->id}/subscriptions");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'subscription_id',
                             'status',
                         ]
                     ]
                 ]);

        $subscriptions = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($subscriptions));
    }

    #[Test]
    public function it_requires_club_role_to_access_subscriptions()
    {
        // Arrange
        $teacherUser = $this->actingAsTeacher();

        // Act
        $response = $this->getJson('/api/club/subscriptions');

        // Assert
        $response->assertStatus(403);
    }

    #[Test]
    public function it_requires_authentication_to_access_subscriptions()
    {
        // Act
        $response = $this->getJson('/api/club/subscriptions');

        // Assert
        $response->assertStatus(401);
    }
}

