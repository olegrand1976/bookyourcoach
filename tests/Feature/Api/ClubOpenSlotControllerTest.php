<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Club;
use App\Models\ClubManager;
use App\Models\ClubOpenSlot;
use App\Models\Discipline;
use App\Models\CourseType;
use Tests\TestCase;

class ClubOpenSlotControllerTest extends TestCase
{
    /** @test */
    public function it_can_list_club_open_slots()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $discipline = Discipline::factory()->create();
        
        ClubOpenSlot::factory()->count(3)->create([
            'club_id' => $club->id,
            'discipline_id' => $discipline->id,
        ]);
        
        // Act
        $response = $this->getJson('/api/club/open-slots');
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'club_id',
                             'day_of_week',
                             'start_time',
                             'end_time',
                             'discipline_id',
                             'max_capacity',
                             'duration',
                             'price',
                         ]
                     ]
                 ]);
        
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_create_open_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $discipline = Discipline::factory()->create();
        
        $slotData = [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'discipline_id' => $discipline->id,
            'max_capacity' => 5,
            'duration' => 60,
            'price' => 45.00,
            'description' => 'Cours du lundi matin',
        ];
        
        // Act
        $response = $this->postJson('/api/club/open-slots', $slotData);
        
        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'club_id',
                         'day_of_week',
                         'start_time',
                         'end_time',
                     ]
                 ]);
        
        $this->assertDatabaseHas('club_open_slots', [
            'club_id' => $club->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        // Arrange
        $this->actingAsClub();
        
        // Act
        $response = $this->postJson('/api/club/open-slots', []);
        
        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'day_of_week',
                     'start_time',
                     'end_time',
                     'max_capacity',
                 ]);
    }

    /** @test */
    public function it_validates_day_of_week_range()
    {
        // Arrange
        $this->actingAsClub();
        
        $slotData = [
            'day_of_week' => 8, // Invalid: must be 0-6
            'start_time' => '09:00',
            'end_time' => '10:00',
            'max_capacity' => 5,
        ];
        
        // Act
        $response = $this->postJson('/api/club/open-slots', $slotData);
        
        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['day_of_week']);
    }

    /** @test */
    public function it_can_update_open_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_capacity' => 3,
            'duration' => 60,
            'price' => 30.00,
        ]);
        
        $updateData = [
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration' => 60,
            'max_capacity' => 8,
            'price' => 50.00,
        ];
        
        // Act
        $response = $this->putJson("/api/club/open-slots/{$slot->id}", $updateData);
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                     'message' => 'Créneau ouvert mis à jour avec succès',
                 ]);
        
        $this->assertDatabaseHas('club_open_slots', [
            'id' => $slot->id,
            'max_capacity' => 8,
            'price' => 50.00,
        ]);
    }

    /** @test */
    public function it_can_delete_open_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
        ]);
        
        // Act
        $response = $this->deleteJson("/api/club/open-slots/{$slot->id}");
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                     'message' => 'Créneau ouvert supprimé avec succès',
                 ]);
        
        $this->assertDatabaseMissing('club_open_slots', [
            'id' => $slot->id,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access()
    {
        // Act
        $response = $this->getJson('/api/club/open-slots');
        
        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_access_from_non_club_users()
    {
        // Arrange
        $this->actingAsStudent();
        
        // Act
        $response = $this->getJson('/api/club/open-slots');
        
        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_updating_another_clubs_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        // Créer un autre club et son créneau
        $otherClub = Club::factory()->create();
        $otherSlot = ClubOpenSlot::factory()->create([
            'club_id' => $otherClub->id,
        ]);
        
        // Act
        $response = $this->putJson("/api/club/open-slots/{$otherSlot->id}", [
            'max_capacity' => 10,
        ]);
        
        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_prevents_deleting_another_clubs_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        
        // Créer un autre club et son créneau
        $otherClub = Club::factory()->create();
        $otherSlot = ClubOpenSlot::factory()->create([
            'club_id' => $otherClub->id,
        ]);
        
        // Act
        $response = $this->deleteJson("/api/club/open-slots/{$otherSlot->id}");
        
        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_show_single_open_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $discipline = Discipline::factory()->create();
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
            'discipline_id' => $discipline->id,
        ]);
        
        // Act
        $response = $this->getJson("/api/club/open-slots/{$slot->id}");
        
        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'club_id',
                         'day_of_week',
                         'start_time',
                         'end_time',
                         'discipline_id',
                         'discipline',
                         'course_types',
                     ]
                 ])
                 ->assertJsonFragment([
                     'id' => $slot->id,
                 ]);
    }

    /** @test */
    public function it_can_update_course_types_for_slot()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        $discipline = Discipline::factory()->create();
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
            'discipline_id' => $discipline->id,
        ]);

        $courseType1 = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $courseType2 = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        
        // Act
        $response = $this->putJson("/api/club/open-slots/{$slot->id}/course-types", [
            'course_type_ids' => [$courseType1->id, $courseType2->id],
        ]);
        
        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Types de cours mis à jour avec succès'
                 ]);

        $this->assertTrue($slot->fresh()->courseTypes->contains($courseType1));
        $this->assertTrue($slot->fresh()->courseTypes->contains($courseType2));
    }

    /** @test */
    public function it_validates_course_type_ids_on_update()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = Club::find($user->club_id);
        
        $slot = ClubOpenSlot::factory()->create([
            'club_id' => $club->id,
        ]);
        
        // Act
        $response = $this->putJson("/api/club/open-slots/{$slot->id}/course-types", [
            'course_type_ids' => [99999], // ID inexistant
        ]);
        
        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['course_type_ids.0']);
    }
}

