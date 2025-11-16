<?php

namespace App\Services;

use App\Models\SubscriptionRecurringSlot;
use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LegacyRecurringSlotService
{
    /**
     * Génère automatiquement les lessons pour un créneau récurrent legacy
     * basé sur day_of_week et start_time/end_time
     * 
     * @param SubscriptionRecurringSlot $recurringSlot Le créneau récurrent
     * @param Carbon|null $startDate Date de début (par défaut: maintenant)
     * @param Carbon|null $endDate Date de fin (par défaut: +3 mois)
     * @return array ['generated' => int, 'skipped' => int, 'errors' => int]
     */
    public function generateLessonsForSlot(
        SubscriptionRecurringSlot $recurringSlot,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now();
        $endDate = $endDate ?? Carbon::now()->addMonths(3);

        $stats = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Charger les relations nécessaires
        $recurringSlot->load(['subscriptionInstance', 'student', 'teacher']);

        // Vérifier que l'abonnement est actif
        $subscriptionInstance = $recurringSlot->subscriptionInstance;
        if (!$subscriptionInstance || $subscriptionInstance->status !== 'active') {
            Log::info("L'abonnement #{$subscriptionInstance->id} n'est pas actif pour le créneau récurrent #{$recurringSlot->id}");
            return $stats;
        }

        // Vérifier que le créneau est dans sa période de validité
        $recurringStartDate = Carbon::parse($recurringSlot->start_date);
        $recurringEndDate = Carbon::parse($recurringSlot->end_date);
        
        // Ajuster startDate si nécessaire
        if ($recurringStartDate->isAfter($startDate)) {
            $startDate = $recurringStartDate->copy();
        }
        
        // Ajuster endDate si nécessaire
        if ($recurringEndDate->isBefore($endDate)) {
            $endDate = $recurringEndDate->copy();
        }

        // Vérifier que l'abonnement est valide pour cette période
        $subscriptionStartedAt = Carbon::parse($subscriptionInstance->started_at);
        $subscriptionExpiresAt = $subscriptionInstance->expires_at ? Carbon::parse($subscriptionInstance->expires_at) : null;

        // Générer les dates pour chaque semaine dans la plage
        $dates = $this->generateDatesForRecurringSlot(
            $recurringSlot,
            $startDate,
            $endDate,
            $subscriptionStartedAt,
            $subscriptionExpiresAt
        );

        Log::info("Génération de lessons pour créneau récurrent legacy #{$recurringSlot->id}", [
            'total_dates' => count($dates),
            'subscription_instance_id' => $subscriptionInstance->id,
            'day_of_week' => $recurringSlot->day_of_week,
            'start_time' => $recurringSlot->start_time,
        ]);

        // Générer les lessons pour chaque date valide
        foreach ($dates as $date) {
            try {
                $lesson = $this->createLessonFromRecurringSlot($recurringSlot, $date, $subscriptionInstance);
                
                if ($lesson) {
                    $stats['generated']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Erreur lors de la génération d'une lesson pour créneau récurrent #{$recurringSlot->id}", [
                    'date' => $date->format('Y-m-d H:i'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $stats;
    }

    /**
     * Génère les dates pour un créneau récurrent legacy
     */
    private function generateDatesForRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $subscriptionStartedAt,
        ?Carbon $subscriptionExpiresAt
    ): array {
        $dates = [];
        
        // Trouver le premier jour correspondant au day_of_week à partir de startDate
        $currentDate = $startDate->copy();
        
        // Ajuster pour trouver le prochain jour correspondant
        while ($currentDate->dayOfWeek != $recurringSlot->day_of_week) {
            $currentDate->addDay();
        }
        
        // Si on est avant la date de début du créneau récurrent, avancer d'une semaine
        $recurringStartDate = Carbon::parse($recurringSlot->start_date);
        if ($currentDate->isBefore($recurringStartDate)) {
            $currentDate = $recurringStartDate->copy();
            while ($currentDate->dayOfWeek != $recurringSlot->day_of_week) {
                $currentDate->addDay();
            }
        }

        // Générer les dates jusqu'à endDate
        while ($currentDate->lte($endDate)) {
            // Vérifier que la date est dans la période de validité de l'abonnement
            if ($currentDate->isBefore($subscriptionStartedAt)) {
                $currentDate->addWeek();
                continue;
            }
            
            if ($subscriptionExpiresAt && $currentDate->isAfter($subscriptionExpiresAt)) {
                break;
            }

            // Vérifier que la date est dans la période de validité du créneau récurrent
            $recurringStartDate = Carbon::parse($recurringSlot->start_date);
            $recurringEndDate = Carbon::parse($recurringSlot->end_date);
            
            if ($currentDate->isBefore($recurringStartDate) || $currentDate->isAfter($recurringEndDate)) {
                $currentDate->addWeek();
                continue;
            }

            $dates[] = $currentDate->copy();
            $currentDate->addWeek();
        }

        return $dates;
    }

    /**
     * Crée une lesson depuis un créneau récurrent legacy
     */
    private function createLessonFromRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $date,
        SubscriptionInstance $subscriptionInstance
    ): ?Lesson {
        // Vérifier si une lesson existe déjà pour cette date et ce créneau
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $recurringSlot->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $recurringSlot->end_time);

        // Vérifier si une lesson existe déjà
        $existingLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', $startTime)
            ->first();

        if ($existingLesson) {
            Log::info("Lesson déjà existante pour créneau récurrent #{$recurringSlot->id}", [
                'lesson_id' => $existingLesson->id,
                'date' => $date->format('Y-m-d'),
            ]);
            return null;
        }

        // Récupérer le dernier cours créé pour ce créneau récurrent pour avoir les mêmes infos
        $lastLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$lastLesson) {
            Log::warning("Aucun cours précédent trouvé pour créneau récurrent #{$recurringSlot->id}");
            return null;
        }

        // Créer la nouvelle lesson
        $lesson = Lesson::create([
            'club_id' => $lastLesson->club_id,
            'teacher_id' => $recurringSlot->teacher_id,
            'student_id' => $recurringSlot->student_id,
            'course_type_id' => $lastLesson->course_type_id,
            'location_id' => $lastLesson->location_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'confirmed',
            'price' => $lastLesson->price,
            'notes' => "Cours généré automatiquement depuis créneau récurrent",
        ]);

        // Lier la lesson à l'abonnement
        $subscriptionInstance->consumeLesson($lesson);

        Log::info("✅ Lesson générée depuis créneau récurrent legacy", [
            'lesson_id' => $lesson->id,
            'recurring_slot_id' => $recurringSlot->id,
            'date' => $date->format('Y-m-d'),
            'subscription_instance_id' => $subscriptionInstance->id,
        ]);

        return $lesson;
    }

    /**
     * Génère les lessons pour tous les créneaux récurrents actifs
     */
    public function generateLessonsForAllActiveSlots(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now();
        $endDate = $endDate ?? Carbon::now()->addMonths(3);

        $totalStats = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Récupérer tous les créneaux récurrents avec des abonnements actifs
        $recurringSlots = SubscriptionRecurringSlot::whereHas('subscriptionInstance', function ($query) {
                $query->where('status', 'active');
            })
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        Log::info("Génération de lessons pour tous les créneaux récurrents legacy", [
            'slots_count' => $recurringSlots->count(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        foreach ($recurringSlots as $slot) {
            try {
                $stats = $this->generateLessonsForSlot($slot, $startDate, $endDate);
                $totalStats['generated'] += $stats['generated'];
                $totalStats['skipped'] += $stats['skipped'];
                $totalStats['errors'] += $stats['errors'];
            } catch (\Exception $e) {
                $totalStats['errors']++;
                Log::error("Erreur lors de la génération pour créneau #{$slot->id}: " . $e->getMessage());
            }
        }

        return $totalStats;
    }
}

