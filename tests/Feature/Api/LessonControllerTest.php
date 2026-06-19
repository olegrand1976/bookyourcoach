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
        $this->assertEquals($context['totalLessons'] - 1, $instance->getRemainingAttachmentSlots());

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
}
