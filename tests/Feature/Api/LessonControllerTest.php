<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CourseType;
use App\Models\Location;
use App\Models\Subscription;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionTemplate;
use Tests\TestCase;

class LessonControllerTest extends TestCase
{
    private function teacherAttachedToClub(\App\Models\Club $club): Teacher
    {
        $teacher = Teacher::factory()->create();
        $teacher->clubs()->attach($club->id, ['is_active' => true, 'joined_at' => now()]);

        return $teacher;
    }

    /**
     * @return array{
     *     teacher: Teacher,
     *     student: Student,
     *     courseType: CourseType,
     *     location: Location,
     *     instance: SubscriptionInstance,
     *     totalLessons: int
     * }
     */
    private function createSubscriptionInstanceForClub(
        \App\Models\Club $club,
        string $subscriptionNumber = 'TEST-SUB',
        int $totalLessons = 10,
    ): array {
        $teacher = $this->teacherAttachedToClub($club);
        $discipline = \App\Models\Discipline::factory()->create();
        $courseType = CourseType::factory()->create(['discipline_id' => $discipline->id]);
        $location = Location::factory()->create();
        $student = Student::factory()->create(['club_id' => $club->id]);

        $template = SubscriptionTemplate::create([
            'club_id' => $club->id,
            'model_number' => 'MOD-' . $subscriptionNumber,
            'name' => 'Template ' . $subscriptionNumber,
            'total_lessons' => $totalLessons,
            'validity_months' => 4,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $template->courseTypes()->attach($courseType->id);

        $subscription = Subscription::create([
            'club_id' => $club->id,
            'subscription_template_id' => $template->id,
            'subscription_number' => $subscriptionNumber,
        ]);

        $instance = SubscriptionInstance::create([
            'subscription_id' => $subscription->id,
            'lessons_used' => 0,
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(3),
            'status' => 'active',
        ]);
        $instance->students()->attach($student->id);

        return compact('teacher', 'student', 'courseType', 'location', 'instance') + [
            'totalLessons' => $totalLessons,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createLessonForSubscriptionContext(array $context, \App\Models\Club $club, array $overrides = []): Lesson
    {
        return Lesson::factory()->create(array_merge([
            'club_id' => $club->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
        ], $overrides));
    }

    /** @test */
    public function it_can_list_lessons()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->count(3)->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
        ]);

        // Act
        $response = $this->getJson('/api/lessons');

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'teacher_id',
                             'course_type_id',
                             'location_id',
                             'start_time',
                             'end_time',
                             'status',
                             'price',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_can_create_lesson_with_all_required_fields()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $student = Student::factory()->create();
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lessonData = [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 60,
            'price' => 45.00,
            'notes' => 'Cours de test',
            'deduct_from_subscription' => false,
            'recurring_interval' => 0,
        ];

        // Act
        $response = $this->postJson('/api/lessons', $lessonData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'teacher_id',
                         'student_id',
                         'course_type_id',
                         'location_id',
                         'start_time',
                         'end_time',
                         'status',
                         'price',
                     ]
                 ]);

        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_validates_lesson_creation_data()
    {
        // Arrange
        $this->actingAsClub();

        // Act - Envoyer des données vides
        $response = $this->postJson('/api/lessons', []);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'teacher_id',
                     'course_type_id',
                     'start_time',
                 ]);
    }

    /** @test */
    public function it_validates_teacher_id_exists()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lessonData = [
            'teacher_id' => 99999, // ID inexistant
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 60,
        ];

        // Act
        $response = $this->postJson('/api/lessons', $lessonData);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['teacher_id']);
    }

    /** @test */
    public function it_can_show_lesson_details()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
        ]);

        // Act
        $response = $this->getJson("/api/lessons/{$lesson->id}");

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'teacher_id',
                         'course_type_id',
                         'location_id',
                         'start_time',
                         'end_time',
                         'status',
                         'price',
                     ]
                 ])
                 ->assertJsonFragment([
                     'id' => $lesson->id,
                 ]);
    }

    /** @test */
    public function it_can_update_lesson()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'status' => 'pending',
            'price' => 30.00,
        ]);

        $updateData = [
            'status' => 'confirmed',
            'price' => 45.00,
            'notes' => 'Cours confirmé',
        ];

        // Act
        $response = $this->putJson("/api/lessons/{$lesson->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'success' => true,
                 ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'confirmed',
            'price' => 45.00,
            'notes' => 'Cours confirmé',
        ]);
    }

    /** @test */
    public function it_cancels_lesson_when_club_deletes_without_cancel_scope()
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'status' => 'confirmed',
        ]);

        $response = $this->deleteJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Cours annulé avec succès',
            ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access()
    {
        // Act
        $response = $this->getJson('/api/lessons');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_calculates_end_time_from_duration()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->withDuration(90)->create();
        $location = Location::factory()->create();

        $lessonData = [
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'duration' => 90,
        ];

        // Act
        $response = $this->postJson('/api/lessons', $lessonData);

        // Assert
        $response->assertStatus(201);

        $lesson = Lesson::latest()->first();
        $this->assertEquals('2025-10-15 11:30:00', $lesson->end_time);
    }

    /** @test */
    public function it_can_filter_lessons_by_date_range()
    {
        // Arrange
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $teacher = $this->teacherAttachedToClub($club);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-15 10:00:00',
            'status' => 'confirmed',
        ]);

        Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-20 14:00:00',
            'status' => 'confirmed',
        ]);

        Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $teacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => '2025-10-25 16:00:00',
            'status' => 'confirmed',
        ]);

        // Act
        $response = $this->getJson('/api/lessons?date_from=2025-10-14&date_to=2025-10-21');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function club_cannot_access_lessons_from_another_club(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        $otherClub = \App\Models\Club::factory()->create();
        $otherTeacher = $this->teacherAttachedToClub($otherClub);
        $courseType = CourseType::factory()->create();
        $location = Location::factory()->create();

        $foreignLesson = Lesson::factory()->create([
            'club_id' => $otherClub->id,
            'teacher_id' => $otherTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $ownTeacher = $this->teacherAttachedToClub($club);
        $ownLesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $ownTeacher->id,
            'course_type_id' => $courseType->id,
            'location_id' => $location->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'status' => 'confirmed',
        ]);

        $listResponse = $this->getJson('/api/lessons');
        $listResponse->assertStatus(200);
        $listedIds = collect($listResponse->json('data'))->pluck('id')->all();
        $this->assertContains($ownLesson->id, $listedIds);
        $this->assertNotContains($foreignLesson->id, $listedIds);

        $this->getJson("/api/lessons/{$foreignLesson->id}")
            ->assertStatus(404);

        $this->putJson("/api/lessons/{$foreignLesson->id}", ['notes' => 'hack'])
            ->assertStatus(404);

        $this->putJson("/api/lessons/{$foreignLesson->id}/subscription", [
            'deduct_from_subscription' => false,
        ])->assertStatus(404);

        $this->deleteJson("/api/lessons/{$foreignLesson->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('lessons', [
            'id' => $foreignLesson->id,
            'status' => 'confirmed',
        ]);

        $this->getJson("/api/lessons/{$ownLesson->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $ownLesson->id);
    }

    /** @test */
    public function club_unlinking_subscription_recalculates_lessons_used(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'UNLINK-001');

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $context['instance']->consumeLesson($lesson);
        $this->assertEquals(1, $context['instance']->fresh()->lessons_used);

        $this->putJson("/api/lessons/{$lesson->id}/subscription", [
            'deduct_from_subscription' => false,
        ])->assertStatus(200);

        $this->assertEquals(0, $context['instance']->fresh()->lessons_used);
        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $context['instance']->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /** @test */
    public function club_unlinking_future_lesson_frees_attachment_slot_without_changing_lessons_used(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'UNLINK-FUTURE');

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
        ]);

        $context['instance']->consumeLesson($lesson);

        $instance = $context['instance']->fresh();
        $this->assertEquals(0, $instance->lessons_used);
        // Cours futur : n'entre pas dans le plafond à « maintenant »
        $this->assertEquals($context['totalLessons'], $instance->getRemainingAttachmentSlots());
        $this->assertEquals(
            $context['totalLessons'] - 1,
            $instance->getRemainingAttachmentSlots(now()->addWeek()->addMinute())
        );

        $this->putJson("/api/lessons/{$lesson->id}/subscription", [
            'deduct_from_subscription' => false,
        ])->assertStatus(200);

        $instance = $context['instance']->fresh();
        $this->assertEquals(0, $instance->lessons_used);
        $this->assertEquals($context['totalLessons'], $instance->getRemainingAttachmentSlots());
        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $instance->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /** @test */
    public function club_unlinking_past_lesson_reopens_completed_subscription(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'UNLINK-REOPEN', totalLessons: 3);

        $pastLessons = [];
        for ($i = 0; $i < 3; $i++) {
            $pastLessons[] = Lesson::factory()->create([
                'club_id' => $club->id,
                'teacher_id' => $context['teacher']->id,
                'student_id' => $context['student']->id,
                'course_type_id' => $context['courseType']->id,
                'location_id' => $context['location']->id,
                'start_time' => now()->subDays($i + 1),
                'end_time' => now()->subDays($i + 1)->addHour(),
                'status' => 'confirmed',
            ]);
            $context['instance']->consumeLesson($pastLessons[$i]);
        }

        $instance = $context['instance']->fresh();
        $this->assertEquals(3, $instance->lessons_used);
        $this->assertEquals('completed', $instance->status);

        $lessonToUnlink = $pastLessons[0];
        $this->putJson("/api/lessons/{$lessonToUnlink->id}/subscription", [
            'deduct_from_subscription' => false,
        ])->assertStatus(200);

        $instance = $context['instance']->fresh();
        $this->assertEquals(2, $instance->lessons_used);
        $this->assertEquals('active', $instance->status);
        $this->assertEquals(1, $instance->getRemainingAttachmentSlots());
    }

    /** @test */
    public function club_cancellation_releases_subscription_and_sets_explicit_false_flag(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'CANCEL-CLUB');

        $lesson = Lesson::factory()->create([
            'club_id' => $club->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addWeek()->addHour(),
            'status' => 'confirmed',
        ]);

        $context['instance']->consumeLesson($lesson);
        $this->assertEquals(0, $context['instance']->fresh()->lessons_used);
        $this->assertEquals(9, $context['instance']->fresh()->getRemainingAttachmentSlots());

        $this->deleteJson("/api/lessons/{$lesson->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $lesson->refresh();
        $this->assertEquals('cancelled', $lesson->status);
        $this->assertFalse((bool) $lesson->cancellation_count_in_subscription);
        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $context['instance']->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(10, $context['instance']->fresh()->getRemainingAttachmentSlots());
    }

    /** @test */
    public function club_cancellation_via_update_releases_subscription_and_sets_false_flag(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'CANCEL-UPDATE');

        $lesson = $this->createLessonForSubscriptionContext($context, $club);
        $context['instance']->consumeLesson($lesson);

        $this->putJson("/api/lessons/{$lesson->id}", [
            'status' => 'cancelled',
        ])->assertStatus(200)->assertJson(['success' => true]);

        $lesson->refresh();
        $this->assertEquals('cancelled', $lesson->status);
        $this->assertFalse((bool) $lesson->cancellation_count_in_subscription);
        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $context['instance']->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(10, $context['instance']->fresh()->getRemainingAttachmentSlots());
    }

    /** @test */
    public function club_cancellation_via_cancel_with_future_releases_subscription_and_sets_false_flag(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'CANCEL-FUTURE');

        $lesson = $this->createLessonForSubscriptionContext($context, $club);
        $context['instance']->consumeLesson($lesson);

        $this->postJson("/api/lessons/{$lesson->id}/cancel-with-future", [
            'cancel_scope' => 'single',
            'action' => 'cancel',
            'reason' => 'Test annulation club',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $lesson->refresh();
        $this->assertEquals('cancelled', $lesson->status);
        $this->assertFalse((bool) $lesson->cancellation_count_in_subscription);
        $this->assertDatabaseMissing('subscription_lessons', [
            'subscription_instance_id' => $context['instance']->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertEquals(10, $context['instance']->fresh()->getRemainingAttachmentSlots());
    }

    /** @test */
    public function club_cancellation_of_past_lesson_recalculates_lessons_used(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'CANCEL-PAST');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHour(),
        ]);
        $context['instance']->consumeLesson($lesson);
        $this->assertEquals(1, $context['instance']->fresh()->lessons_used);

        $this->deleteJson("/api/lessons/{$lesson->id}")
            ->assertStatus(200);

        $lesson->refresh();
        $this->assertEquals('cancelled', $lesson->status);
        $this->assertFalse((bool) $lesson->cancellation_count_in_subscription);
        $this->assertEquals(0, $context['instance']->fresh()->lessons_used);
        $this->assertEquals(10, $context['instance']->fresh()->getRemainingAttachmentSlots());
    }

    /** @test */
    public function planning_context_returns_slim_payload_without_remaining_appends(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'PLAN-SLIM');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(2)->setTime(10, 0),
            'end_time' => now()->addDays(2)->setTime(11, 0),
        ]);
        $context['instance']->lessons()->attach($lesson->id);

        $from = now()->toDateString();
        $to = now()->addWeeks(2)->toDateString();

        $response = $this->getJson("/api/lessons?context=planning&date_from={$from}&date_to={$to}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $rows = $response->json('data');
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        $row = collect($rows)->firstWhere('id', $lesson->id);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('teacher', $row);
        $this->assertArrayHasKey('subscription_instances', $row);
        $this->assertArrayNotHasKey('club', $row);
        $this->assertArrayNotHasKey('location', $row);

        $instances = $row['subscription_instances'] ?? [];
        $this->assertNotEmpty($instances);
        $this->assertArrayHasKey('id', $instances[0]);
        $this->assertArrayNotHasKey('remaining_bookable', $instances[0]);
        $this->assertArrayNotHasKey('remaining_consumed', $instances[0]);
        $this->assertArrayNotHasKey('remaining_lessons', $instances[0]);

        // Élève sans remaining_* ni templates (subscription_instances = ids actifs seulement)
        if (isset($row['student']['subscription_instances'])) {
            foreach ($row['student']['subscription_instances'] as $si) {
                $this->assertArrayHasKey('id', $si);
                $this->assertArrayNotHasKey('remaining_bookable', $si);
            }
        }
    }

    /**
     * Mesure : context=planning doit faire nettement moins de requêtes SQL
     * qu'un index classique (évite remaining_* N+1 par instance).
     *
     * @test
     */
    public function planning_context_uses_fewer_queries_than_default_index(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);

        for ($i = 0; $i < 8; $i++) {
            $context = $this->createSubscriptionInstanceForClub($club, 'PERF-'.$i);
            $lesson = $this->createLessonForSubscriptionContext($context, $club, [
                'start_time' => now()->addDays($i + 1)->setTime(10, 0),
                'end_time' => now()->addDays($i + 1)->setTime(11, 0),
            ]);
            $context['instance']->lessons()->attach($lesson->id);
        }

        $from = now()->toDateString();
        $to = now()->addWeeks(2)->toDateString();

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->getJson("/api/lessons?date_from={$from}&date_to={$to}")->assertStatus(200);
        $defaultCount = count(\Illuminate\Support\Facades\DB::getQueryLog());

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->getJson("/api/lessons?context=planning&date_from={$from}&date_to={$to}")->assertStatus(200);
        $planningCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThan(
            $defaultCount,
            $planningCount,
            "planning ({$planningCount}) doit être < default ({$defaultCount})"
        );
        // Au moins ~30% de requêtes en moins (remaining_* N+1 évité)
        $this->assertLessThanOrEqual(
            (int) floor($defaultCount * 0.7),
            $planningCount,
            "gain insuffisant: planning={$planningCount} default={$defaultCount}"
        );
    }

    /** @test */
    public function default_index_still_includes_remaining_when_instances_serialized(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'DEFAULT-REM');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(3)->setTime(10, 0),
            'end_time' => now()->addDays(3)->setTime(11, 0),
        ]);
        $context['instance']->lessons()->attach($lesson->id);

        $from = now()->toDateString();
        $to = now()->addWeeks(2)->toDateString();

        $response = $this->getJson("/api/lessons?date_from={$from}&date_to={$to}");
        $response->assertStatus(200);
        $row = collect($response->json('data'))->firstWhere('id', $lesson->id);
        $this->assertNotNull($row);

        $instances = $row['subscription_instances'] ?? [];
        $this->assertNotEmpty($instances);
        // Path non-planning : appends historiques toujours présents (pas de régression silencieuse)
        $this->assertArrayHasKey('remaining_bookable', $instances[0]);
    }

    /** @test */
    public function planning_context_includes_cancelled_and_soft_deleted_lessons_for_club(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'PLAN-CANCEL');

        $cancelled = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $user->id,
            'cancelled_by_role' => 'club',
        ]);

        $deleted = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(1)->setTime(11, 0),
            'end_time' => now()->addDays(1)->setTime(12, 0),
            'status' => 'confirmed',
        ]);
        $deleted->delete();

        $from = now()->toDateString();
        $to = now()->addWeeks(2)->toDateString();

        $response = $this->getJson("/api/lessons?context=planning&date_from={$from}&date_to={$to}");
        $response->assertStatus(200)->assertJsonPath('success', true);

        $rows = collect($response->json('data'));
        $cancelledRow = $rows->firstWhere('id', $cancelled->id);
        $this->assertNotNull($cancelledRow, 'Cancelled lesson should appear in club planning');
        $this->assertSame('cancelled', $cancelledRow['status']);
        $this->assertArrayHasKey('cancelled_at', $cancelledRow);
        $this->assertArrayHasKey('cancelled_by_role', $cancelledRow);
        $this->assertSame('club', $cancelledRow['cancelled_by_role']);
        $this->assertSame($user->id, $cancelledRow['cancelled_by_user']['id'] ?? null);

        $deletedRow = $rows->firstWhere('id', $deleted->id);
        $this->assertNotNull($deletedRow, 'Soft-deleted lesson should appear in club planning');
        $this->assertNotNull($deletedRow['deleted_at'] ?? null);
    }

    /** @test */
    public function non_planning_index_still_excludes_cancelled_for_club(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'NO-PLAN-CANCEL');

        $cancelled = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'cancelled',
        ]);

        $from = now()->toDateString();
        $to = now()->addWeeks(2)->toDateString();

        $response = $this->getJson("/api/lessons?date_from={$from}&date_to={$to}");
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($cancelled->id, $ids);
    }

    /** @test */
    public function store_allows_one_off_when_teacher_active_series_has_no_lesson_that_day(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'GAP-SERIES');

        $occurrence = \Carbon\Carbon::parse('2026-09-16 16:20:00'); // mercredi
        $this->assertSame(3, $occurrence->dayOfWeek);

        \App\Models\SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $context['instance']->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'day_of_week' => 3,
            'start_time' => '16:20:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-09-02',
            'end_date' => '2027-03-01',
            'status' => 'active',
        ]);

        // Autre élève, même enseignant / horaire — doit passer (série sans cours matérialisé ce jour)
        $otherStudent = Student::factory()->create(['club_id' => $club->id]);

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $context['teacher']->id,
            'student_id' => $otherStudent->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => '2026-09-16 16:20:00',
            'duration' => 60,
            'price' => 18.00,
            'deduct_from_subscription' => false,
            'recurring_interval' => 0,
        ]);

        if ($response->status() !== 201) {
            $this->fail('Expected 201, got '.$response->status().': '.json_encode($response->json(), JSON_UNESCAPED_UNICODE));
        }

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $context['teacher']->id,
            'student_id' => $otherStudent->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function store_blocks_one_off_when_teacher_series_already_has_confirmed_lesson(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'FULL-SERIES');

        \App\Models\SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $context['instance']->id,
            'teacher_id' => $context['teacher']->id,
            'student_id' => $context['student']->id,
            'day_of_week' => 3,
            'start_time' => '16:20:00',
            'end_time' => '17:20:00',
            'recurring_interval' => 1,
            'start_date' => '2026-09-02',
            'end_date' => '2027-03-01',
            'status' => 'active',
        ]);

        $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => '2026-09-16 16:20:00',
            'end_time' => '2026-09-16 17:20:00',
            'status' => 'confirmed',
        ]);

        $otherStudent = Student::factory()->create(['club_id' => $club->id]);

        $response = $this->postJson('/api/lessons', [
            'teacher_id' => $context['teacher']->id,
            'student_id' => $otherStudent->id,
            'course_type_id' => $context['courseType']->id,
            'location_id' => $context['location']->id,
            'start_time' => '2026-09-16 16:20:00',
            'duration' => 60,
            'price' => 18.00,
            'deduct_from_subscription' => false,
            'recurring_interval' => 0,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('déjà', (string) $response->json('message'));
    }

    /** @test */
    public function deletion_preview_finds_soft_deleted_lesson_for_club(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'DEL-PREVIEW-TRASH');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(5)->setTime(8, 40),
            'end_time' => now()->addDays(5)->setTime(9, 40),
        ]);
        $context['instance']->lessons()->attach($lesson->id);
        $lesson->delete();

        $this->assertNotNull($lesson->fresh());
        $this->assertTrue($lesson->fresh()->trashed());

        $response = $this->getJson(
            "/api/club/lessons/{$lesson->id}/deletion-preview?cancel_scope=all_future&action=delete"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_lesson.id', $lesson->id);
    }

    /** @test */
    public function destroy_with_cancel_scope_accepts_soft_deleted_lesson_all_future(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'DEL-TRASH-FUTURE');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(6)->setTime(8, 40),
            'end_time' => now()->addDays(6)->setTime(9, 40),
        ]);
        $context['instance']->lessons()->attach($lesson->id);
        $lesson->delete();

        $response = $this->deleteJson("/api/club/lessons/{$lesson->id}", [
            'cancel_scope' => 'all_future',
            'action' => 'delete',
            'reason' => 'Test soft-deleted all_future',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.processed_count', 0)
            ->assertJsonPath('data.skipped_archived_count', 1);

        $this->assertTrue(Lesson::withTrashed()->find($lesson->id)->trashed());
        $this->assertStringContainsString('déjà archivé', (string) $response->json('message'));
    }

    /** @test */
    public function cancel_with_future_rejects_cancel_on_soft_deleted_lesson(): void
    {
        $user = $this->actingAsClub();
        $club = \App\Models\Club::find($user->club_id);
        $context = $this->createSubscriptionInstanceForClub($club, 'CANCEL-TRASH-BLOCK');

        $lesson = $this->createLessonForSubscriptionContext($context, $club, [
            'start_time' => now()->addDays(7)->setTime(8, 40),
            'end_time' => now()->addDays(7)->setTime(9, 40),
        ]);
        $context['instance']->lessons()->attach($lesson->id);
        $lesson->delete();

        $this->deleteJson("/api/club/lessons/{$lesson->id}", [
            'cancel_scope' => 'single',
            'action' => 'cancel',
            'reason' => 'Ne doit pas passer',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Impossible d\'annuler un cours déjà archivé. Réactivez-le ou supprimez les séances futures encore actives.');
    }

    /** @test */
    public function deletion_preview_still_404_for_unknown_lesson(): void
    {
        $this->actingAsClub();

        $this->getJson('/api/club/lessons/999999999/deletion-preview?cancel_scope=single&action=delete')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cours non trouvé');
    }
}
