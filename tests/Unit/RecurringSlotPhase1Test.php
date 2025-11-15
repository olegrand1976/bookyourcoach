<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\RecurringSlot;
use App\Models\RecurringSlotSubscription;
use App\Models\LessonRecurringSlot;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Club;
use App\Models\SubscriptionInstance;
use App\Models\Lesson;
use Carbon\Carbon;
use RRule\RRule;

class RecurringSlotPhase1Test extends TestCase
{
    /**
     * Test 1: Vérification que les modèles existent et sont instanciables
     */
    public function test_models_exist_and_are_instantiable(): void
    {
        $this->assertTrue(class_exists(RecurringSlot::class));
        $this->assertTrue(class_exists(RecurringSlotSubscription::class));
        $this->assertTrue(class_exists(LessonRecurringSlot::class));

        $recurringSlot = new RecurringSlot();
        $this->assertInstanceOf(RecurringSlot::class, $recurringSlot);

        $recurringSlotSubscription = new RecurringSlotSubscription();
        $this->assertInstanceOf(RecurringSlotSubscription::class, $recurringSlotSubscription);

        $lessonRecurringSlot = new LessonRecurringSlot();
        $this->assertInstanceOf(LessonRecurringSlot::class, $lessonRecurringSlot);
    }

    /**
     * Test 2: Vérification des fillable attributes
     */
    public function test_recurring_slot_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'student_id',
            'teacher_id',
            'club_id',
            'course_type_id',
            'rrule',
            'reference_start_time',
            'duration_minutes',
            'status',
            'notes',
        ];

        $recurringSlot = new RecurringSlot();
        $this->assertEquals($expectedFillable, $recurringSlot->getFillable());
    }

    /**
     * Test 3: Vérification des casts
     */
    public function test_recurring_slot_has_correct_casts(): void
    {
        $recurringSlot = new RecurringSlot();
        $casts = $recurringSlot->getCasts();

        $this->assertArrayHasKey('reference_start_time', $casts);
        $this->assertEquals('datetime', $casts['reference_start_time']);
        $this->assertArrayHasKey('duration_minutes', $casts);
        $this->assertEquals('integer', $casts['duration_minutes']);
    }

    /**
     * Test 4: Vérification de la génération de dates avec RRULE (sans base de données)
     */
    public function test_rrule_date_generation(): void
    {
        // Test avec une règle simple : tous les samedis
        $rrule = new RRule('FREQ=WEEKLY;BYDAY=SA', Carbon::now()->next(Carbon::SATURDAY));
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addMonths(1);
        
        $occurrences = $rrule->getOccurrencesBetween($startDate, $endDate);
        
        // On devrait avoir environ 4-5 samedis dans un mois
        $this->assertGreaterThanOrEqual(4, count($occurrences));
        $this->assertLessThanOrEqual(5, count($occurrences));

        // Vérifier que toutes les dates sont des samedis
        foreach ($occurrences as $occurrence) {
            $date = Carbon::instance($occurrence);
            $this->assertEquals(Carbon::SATURDAY, $date->dayOfWeek);
        }
    }

    /**
     * Test 5: Vérification des méthodes de statut RecurringSlot
     */
    public function test_recurring_slot_status_methods(): void
    {
        $slot = new RecurringSlot();
        $slot->status = 'active';
        
        $this->assertTrue($slot->isActive());
        $this->assertFalse($slot->isPaused());

        $slot->status = 'paused';
        $this->assertFalse($slot->isActive());
        $this->assertTrue($slot->isPaused());
    }

    /**
     * Test 6: Vérification des relations (structure uniquement)
     */
    public function test_recurring_slot_has_relations_defined(): void
    {
        $slot = new RecurringSlot();
        
        // Vérifier que les méthodes de relations existent
        $this->assertTrue(method_exists($slot, 'student'));
        $this->assertTrue(method_exists($slot, 'teacher'));
        $this->assertTrue(method_exists($slot, 'club'));
        $this->assertTrue(method_exists($slot, 'courseType'));
        $this->assertTrue(method_exists($slot, 'subscriptions'));
        $this->assertTrue(method_exists($slot, 'activeSubscription'));
        $this->assertTrue(method_exists($slot, 'lessons'));
    }

    /**
     * Test 7: Vérification des scopes RecurringSlot
     */
    public function test_recurring_slot_has_scopes(): void
    {
        $slot = new RecurringSlot();
        
        $this->assertTrue(method_exists($slot, 'scopeActive'));
        $this->assertTrue(method_exists($slot, 'scopePaused'));
        $this->assertTrue(method_exists($slot, 'scopeNotCancelled'));
    }

    /**
     * Test 8: Vérification des méthodes RecurringSlotSubscription
     */
    public function test_recurring_slot_subscription_methods(): void
    {
        $subscription = new RecurringSlotSubscription();
        
        $this->assertTrue(method_exists($subscription, 'recurringSlot'));
        $this->assertTrue(method_exists($subscription, 'subscriptionInstance'));
        $this->assertTrue(method_exists($subscription, 'isActive'));
        $this->assertTrue(method_exists($subscription, 'expire'));
        $this->assertTrue(method_exists($subscription, 'cancel'));
        $this->assertTrue(method_exists($subscription, 'scopeActive'));
        $this->assertTrue(method_exists($subscription, 'scopeExpired'));
    }

    /**
     * Test 9: Vérification des méthodes LessonRecurringSlot
     */
    public function test_lesson_recurring_slot_methods(): void
    {
        $lessonSlot = new LessonRecurringSlot();
        
        $this->assertTrue(method_exists($lessonSlot, 'lesson'));
        $this->assertTrue(method_exists($lessonSlot, 'recurringSlot'));
        $this->assertTrue(method_exists($lessonSlot, 'subscriptionInstance'));
        $this->assertTrue(method_exists($lessonSlot, 'isAutoGenerated'));
        $this->assertTrue(method_exists($lessonSlot, 'isManualGenerated'));
    }

    /**
     * Test 10: Vérification que les modèles existants ont les nouvelles relations
     */
    public function test_existing_models_have_new_relations(): void
    {
        // Student
        $student = new Student();
        $this->assertTrue(method_exists($student, 'recurringSlots'));

        // Teacher
        $teacher = new Teacher();
        $this->assertTrue(method_exists($teacher, 'recurringSlots'));

        // Club
        $club = new Club();
        $this->assertTrue(method_exists($club, 'recurringSlots'));

        // SubscriptionInstance
        $subscriptionInstance = new SubscriptionInstance();
        $this->assertTrue(method_exists($subscriptionInstance, 'recurringSlotSubscriptions'));

        // Lesson
        $lesson = new Lesson();
        $this->assertTrue(method_exists($lesson, 'lessonRecurringSlot'));
    }

    /**
     * Test 11: Vérification de différents types de RRULE courants
     */
    public function test_common_rrule_patterns(): void
    {
        $now = Carbon::now();
        $nextMonth = $now->copy()->addMonths(1);

        // Test 1: Tous les samedis
        $rrule1 = new RRule('FREQ=WEEKLY;BYDAY=SA', $now->copy()->next(Carbon::SATURDAY));
        $dates1 = $rrule1->getOccurrencesBetween($now, $nextMonth);
        $this->assertGreaterThan(0, count($dates1));

        // Test 2: Un samedi sur deux
        $rrule2 = new RRule('FREQ=WEEKLY;INTERVAL=2;BYDAY=SA', $now->copy()->next(Carbon::SATURDAY));
        $dates2 = $rrule2->getOccurrencesBetween($now, $nextMonth);
        $this->assertGreaterThan(0, count($dates2));
        $this->assertLessThanOrEqual(count($dates1), count($dates2));

        // Test 3: Lundi et Mercredi
        $rrule3 = new RRule('FREQ=WEEKLY;BYDAY=MO,WE', $now->copy()->next(Carbon::MONDAY));
        $dates3 = $rrule3->getOccurrencesBetween($now, $nextMonth);
        $this->assertGreaterThan(0, count($dates3));
    }

    /**
     * Test 12: Vérification de la méthode generateDates du modèle RecurringSlot
     */
    public function test_recurring_slot_generate_dates_method(): void
    {
        $slot = new RecurringSlot();
        $slot->rrule = 'FREQ=WEEKLY;BYDAY=SA';
        $slot->reference_start_time = Carbon::now()->next(Carbon::SATURDAY);

        // La méthode devrait exister
        $this->assertTrue(method_exists($slot, 'generateDates'));

        // Test avec des dates par défaut (devrait utiliser Carbon::now() et +3 mois)
        // Note: On ne peut pas vraiment tester sans base de données, mais on vérifie que la méthode existe
    }
}
