<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Console\Commands\MigrateRecurringSlotsToRrule;
use App\Models\SubscriptionRecurringSlot;
use App\Models\RecurringSlot;
use Carbon\Carbon;

class RecurringSlotMigrationTest extends TestCase
{
    /**
     * Test que la commande existe et est instanciable
     */
    public function test_command_exists(): void
    {
        $command = new MigrateRecurringSlotsToRrule();
        $this->assertInstanceOf(MigrateRecurringSlotsToRrule::class, $command);
    }

    /**
     * Test de la conversion day_of_week vers RRULE
     */
    public function test_day_of_week_to_rrule_conversion(): void
    {
        $command = new MigrateRecurringSlotsToRrule();
        
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('convertToRrule');
        $method->setAccessible(true);

        // Test samedi (6)
        $slot1 = new SubscriptionRecurringSlot();
        $slot1->day_of_week = 6;
        $rrule = $method->invoke($command, $slot1);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=SA', $rrule);

        // Test lundi (1)
        $slot2 = new SubscriptionRecurringSlot();
        $slot2->day_of_week = 1;
        $rrule = $method->invoke($command, $slot2);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=MO', $rrule);

        // Test dimanche (0)
        $slot3 = new SubscriptionRecurringSlot();
        $slot3->day_of_week = 0;
        $rrule = $method->invoke($command, $slot3);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=SU', $rrule);
    }

    /**
     * Test de la conversion de statut
     */
    public function test_status_conversion(): void
    {
        $command = new MigrateRecurringSlotsToRrule();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('convertStatus');
        $method->setAccessible(true);

        $this->assertEquals('active', $method->invoke($command, 'active'));
        $this->assertEquals('cancelled', $method->invoke($command, 'cancelled'));
        $this->assertEquals('expired', $method->invoke($command, 'expired'));
        $this->assertEquals('expired', $method->invoke($command, 'completed'));
        $this->assertEquals('active', $method->invoke($command, 'unknown'));
    }

    /**
     * Test du calcul de durée
     */
    public function test_duration_calculation(): void
    {
        $command = new MigrateRecurringSlotsToRrule();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateDuration');
        $method->setAccessible(true);

        // Test avec start_time et end_time
        $slot = new SubscriptionRecurringSlot();
        $slot->start_time = '09:00:00';
        $slot->end_time = '10:30:00';
        $duration = $method->invoke($command, $slot);
        $this->assertEquals(90, $duration); // 1h30 = 90 minutes

        // Test sans heures (devrait retourner 60 par défaut)
        $slot2 = new SubscriptionRecurringSlot();
        $slot2->start_time = null;
        $slot2->end_time = null;
        $duration = $method->invoke($command, $slot2);
        $this->assertEquals(60, $duration);
    }

    /**
     * Test de la génération de reference_start_time
     */
    public function test_reference_start_time_generation(): void
    {
        $command = new MigrateRecurringSlotsToRrule();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getReferenceStartTime');
        $method->setAccessible(true);

        // Test avec start_date et start_time
        // Utiliser un samedi (22 novembre 2025 est un samedi)
        $slot1 = new SubscriptionRecurringSlot();
        $slot1->start_date = '2025-11-22';
        $slot1->start_time = '09:00:00';
        $slot1->day_of_week = 6; // Samedi
        
        $referenceTime = $method->invoke($command, $slot1);
        $this->assertInstanceOf(Carbon::class, $referenceTime);
        $this->assertEquals('2025-11-22', $referenceTime->format('Y-m-d'));
        $this->assertEquals('09:00:00', $referenceTime->format('H:i:s'));

        // Test sans start_date (devrait utiliser prochaine occurrence)
        $slot2 = new SubscriptionRecurringSlot();
        $slot2->start_date = null;
        $slot2->start_time = '14:00:00';
        $slot2->day_of_week = 6; // Samedi
        
        $referenceTime = $method->invoke($command, $slot2);
        $this->assertInstanceOf(Carbon::class, $referenceTime);
        // Vérifier que c'est un samedi
        $this->assertEquals(6, $referenceTime->dayOfWeek);
    }
}
