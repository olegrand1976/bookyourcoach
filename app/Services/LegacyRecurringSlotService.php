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
        // Par défaut, générer jusqu'à la fin de la période de validité de la récurrence
        $recurringEndDate = Carbon::parse($recurringSlot->end_date);
        $recurringStartDate = Carbon::parse($recurringSlot->start_date);
        
        // ⚠️ IMPORTANT : Utiliser la date du dernier cours créé pour ce créneau récurrent
        // au lieu de Carbon::now() pour éviter de sauter des semaines
        $lastLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->orderBy('start_time', 'desc')
            ->first();
        
        if ($lastLesson) {
            // Commencer à partir de la semaine suivant le dernier cours créé
            $defaultStartDate = Carbon::parse($lastLesson->start_time)->addWeek();
            Log::info("📅 Utilisation du dernier cours pour déterminer la date de début", [
                'last_lesson_date' => $lastLesson->start_time,
                'calculated_start_date' => $defaultStartDate->format('Y-m-d'),
                'recurring_slot_id' => $recurringSlot->id
            ]);
        } else {
            // Si aucun cours n'existe encore, utiliser la date de début de la récurrence
            $defaultStartDate = $recurringStartDate->copy();
            Log::info("📅 Aucun cours précédent, utilisation de la date de début de la récurrence", [
                'recurring_start_date' => $recurringStartDate->format('Y-m-d'),
                'recurring_slot_id' => $recurringSlot->id
            ]);
        }
        
        // S'assurer que la date de début n'est pas avant la date de début de la récurrence
        if ($defaultStartDate->isBefore($recurringStartDate)) {
            $defaultStartDate = $recurringStartDate->copy();
        }
        
        $startDate = $startDate ?? $defaultStartDate;
        $endDate = $endDate ?? $recurringEndDate->copy();
        
        // Ne pas dépasser la fin de la récurrence
        if ($endDate->isAfter($recurringEndDate)) {
            $endDate = $recurringEndDate->copy();
        }

        $stats = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Charger les relations nécessaires
        $recurringSlot->load(['subscriptionInstance', 'student', 'teacher']);

        // Récupérer l'abonnement (peut être inactif, on continue quand même)
        $subscriptionInstance = $recurringSlot->subscriptionInstance;
        $isSubscriptionActive = $subscriptionInstance && $subscriptionInstance->status === 'active';
        
        if (!$subscriptionInstance) {
            Log::warning("Aucun abonnement trouvé pour le créneau récurrent #{$recurringSlot->id}, génération sans consommation d'abonnement");
        } else if (!$isSubscriptionActive) {
            Log::info("L'abonnement #{$subscriptionInstance->id} n'est pas actif pour le créneau récurrent #{$recurringSlot->id}, génération sans consommation d'abonnement");
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
        // On ne filtre plus par la validité de l'abonnement, on génère pour toute la période de la récurrence
        $dates = $this->generateDatesForRecurringSlot(
            $recurringSlot,
            $startDate,
            $endDate,
            null, // Ne plus filtrer par subscriptionStartedAt
            null  // Ne plus filtrer par subscriptionExpiresAt
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
                $lesson = $this->createLessonFromRecurringSlot(
                    $recurringSlot, 
                    $date, 
                    $isSubscriptionActive ? $subscriptionInstance : null
                );
                
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
     * Ne filtre plus par la validité de l'abonnement, seulement par la période de la récurrence
     * Gère l'intervalle de récurrence (1 = chaque semaine, 2 = toutes les 2 semaines, etc.)
     */
    private function generateDatesForRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $startDate,
        Carbon $endDate,
        ?Carbon $subscriptionStartedAt = null,
        ?Carbon $subscriptionExpiresAt = null
    ): array {
        $dates = [];
        
        // Récupérer l'intervalle de récurrence (par défaut 1 = chaque semaine)
        $recurringInterval = $recurringSlot->recurring_interval ?? 1;
        
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

        $recurringEndDate = Carbon::parse($recurringSlot->end_date);

        // Générer les dates jusqu'à endDate (limité par la fin de la récurrence)
        // En utilisant l'intervalle de récurrence (ex: toutes les 2 semaines)
        while ($currentDate->lte($endDate) && $currentDate->lte($recurringEndDate)) {
            // Vérifier que la date est dans la période de validité du créneau récurrent
            if ($currentDate->isBefore($recurringStartDate)) {
                $currentDate->addWeeks($recurringInterval);
                continue;
            }

            $dates[] = $currentDate->copy();
            $currentDate->addWeeks($recurringInterval); // Utiliser l'intervalle au lieu de addWeek()
        }

        return $dates;
    }

    /**
     * Crée une lesson depuis un créneau récurrent legacy
     * @param SubscriptionRecurringSlot $recurringSlot
     * @param Carbon $date
     * @param SubscriptionInstance|null $subscriptionInstance Si null, le cours est créé sans consommer l'abonnement
     */
    private function createLessonFromRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $date,
        ?SubscriptionInstance $subscriptionInstance = null
    ): ?Lesson {
        // Vérifier si une lesson existe déjà pour cette date et ce créneau
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $recurringSlot->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $recurringSlot->end_time);

        // ⚠️ IMPORTANT : Vérifier si une lesson existe déjà pour cette date (passé ou futur)
        // Cela évite de régénérer des cours qui existent déjà
        $existingLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', $startTime)
            ->first();

        if ($existingLesson) {
            Log::info("Lesson déjà existante pour créneau récurrent #{$recurringSlot->id}", [
                'lesson_id' => $existingLesson->id,
                'date' => $date->format('Y-m-d'),
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'is_past' => $startTime->isPast(),
                'note' => 'Cours déjà existant, génération ignorée'
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

        // Lier la lesson à l'abonnement seulement si l'abonnement est actif
        // ⚠️ IMPORTANT : Les cours futurs seront attachés mais ne consommeront l'abonnement qu'après leur date/heure
        if ($subscriptionInstance) {
            try {
                $subscriptionInstance->consumeLesson($lesson);
                $lessonStartTime = \Carbon\Carbon::parse($lesson->start_time);
                $isPastLesson = $lessonStartTime->isPast();
                Log::info("✅ Lesson générée et liée à l'abonnement", [
                    'lesson_id' => $lesson->id,
                    'subscription_instance_id' => $subscriptionInstance->id,
                    'lesson_start_time' => $lesson->start_time,
                    'is_past' => $isPastLesson,
                    'consumed' => $isPastLesson ? 'Oui (cours passé)' : 'Non (cours futur, sera consommé automatiquement)',
                ]);
            } catch (\Exception $e) {
                // Si la consommation échoue (abonnement expiré, etc.), on continue quand même
                Log::warning("Impossible de consommer l'abonnement pour la lesson #{$lesson->id}: " . $e->getMessage());
            }
        } else {
            Log::info("✅ Lesson générée sans consommation d'abonnement (abonnement inactif ou inexistant)", [
                'lesson_id' => $lesson->id,
            ]);
        }

        Log::info("✅ Lesson générée depuis créneau récurrent legacy", [
            'lesson_id' => $lesson->id,
            'recurring_slot_id' => $recurringSlot->id,
            'date' => $date->format('Y-m-d'),
            'subscription_instance_id' => $subscriptionInstance?->id,
            'subscription_consumed' => $subscriptionInstance !== null,
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

        // Récupérer tous les créneaux récurrents (même si l'abonnement n'est plus actif)
        // La récurrence reste active pour le jour et la plage horaire
        $recurringSlots = SubscriptionRecurringSlot::where('start_date', '<=', $endDate)
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

