<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class LessonControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_list_lessons_when_authenticated()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours de test
        Lesson::factory()->count(3)->create();

        $response = $this->getJson('/api/lessons');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'price',
                        'duration',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_requires_authentication_to_access_lessons()
    {
        $response = $this->getJson('/api/lessons');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_show_a_specific_lesson()
    {
        $user = $this->actingAsAdmin();
        
        $lesson = Lesson::factory()->create();

        $response = $this->getJson("/api/lessons/{$lesson->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'price',
                    'duration'
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_lesson()
    {
        $user = $this->actingAsAdmin();

        $response = $this->getJson('/api/lessons/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_search_lessons_by_title()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours avec des titres spécifiques
        Lesson::factory()->create(['title' => 'Cours de dressage débutant']);
        Lesson::factory()->create(['title' => 'Cours d\'obstacles avancé']);
        Lesson::factory()->create(['title' => 'Cours de natation']);

        $response = $this->getJson('/api/lessons?search=dressage');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Cours de dressage débutant');
    }

    #[Test]
    public function it_can_filter_lessons_by_status()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours avec différents statuts
        Lesson::factory()->create(['status' => 'available']);
        Lesson::factory()->create(['status' => 'pending']);
        Lesson::factory()->create(['status' => 'completed']);

        $response = $this->getJson('/api/lessons?status=available');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'available');
    }

    #[Test]
    public function it_can_filter_lessons_by_price_range()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours avec différents prix
        Lesson::factory()->create(['price' => 30.00]);
        Lesson::factory()->create(['price' => 50.00]);
        Lesson::factory()->create(['price' => 80.00]);

        $response = $this->getJson('/api/lessons?min_price=40&max_price=60');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', '50.00');
    }

    #[Test]
    public function it_can_paginate_lessons()
    {
        $user = $this->actingAsAdmin();
        
        // Créer plus de cours que la pagination par défaut
        Lesson::factory()->count(15)->create();

        $response = $this->getJson('/api/lessons?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 15);
    }

    #[Test]
    public function it_can_sort_lessons_by_price()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours avec des prix différents
        Lesson::factory()->create(['price' => 80.00]);
        Lesson::factory()->create(['price' => 30.00]);
        Lesson::factory()->create(['price' => 50.00]);

        $response = $this->getJson('/api/lessons?sort_by=price&sort_order=asc');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.price', '30.00')
            ->assertJsonPath('data.1.price', '50.00')
            ->assertJsonPath('data.2.price', '80.00');
    }

    #[Test]
    public function it_can_sort_lessons_by_date()
    {
        $user = $this->actingAsAdmin();
        
        // Créer des cours avec des dates différentes
        $oldLesson = Lesson::factory()->create();
        $oldLesson->created_at = now()->subDays(5);
        $oldLesson->save();

        $newLesson = Lesson::factory()->create();
        $newLesson->created_at = now()->subDays(1);
        $newLesson->save();

        $response = $this->getJson('/api/lessons?sort_by=created_at&sort_order=desc');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newLesson->id)
            ->assertJsonPath('data.1.id', $oldLesson->id);
    }
}