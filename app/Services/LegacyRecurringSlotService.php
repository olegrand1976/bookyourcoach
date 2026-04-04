<?php

namespace App\Services;

use App\Models\ClubClosureDay;
use App\Models\SubscriptionRecurringSlot;
use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        
        // Dernier cours pour ce créneau récurrent uniquement (même jour de semaine + même horaire)
        // pour éviter les décalages quand un élève a plusieurs récurrences avec le même prof
        $lastLesson = $this->findLastLessonForRecurringSlot($recurringSlot);

        if ($lastLesson) {
            $recurringInterval = $recurringSlot->recurring_interval ?? 1;
            $defaultStartDate = Carbon::parse($lastLesson->start_time)->addWeeks($recurringInterval);
            Log::info("📅 Utilisation du dernier cours pour ce créneau (jour/heure)", [
                'last_lesson_date' => $lastLesson->start_time,
                'calculated_start_date' => $defaultStartDate->format('Y-m-d'),
                'recurring_slot_id' => $recurringSlot->id,
                'day_of_week' => $recurringSlot->day_of_week,
                'recurring_interval' => $recurringInterval
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
            'subscription_instance_id' => $subscriptionInstance?->id,
            'day_of_week' => $recurringSlot->day_of_week,
            'start_time' => $recurringSlot->start_time,
            'recurring_interval' => $recurringSlot->recurring_interval ?? 1,
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
     * Dernière lesson pour ce créneau récurrent (même student, teacher, jour de semaine, horaire).
     * Évite d'utiliser un cours d'un autre créneau (ex. lundi 10h vs mercredi 14h).
     */
    private function findLastLessonForRecurringSlot(SubscriptionRecurringSlot $recurringSlot): ?Lesson
    {
        $driver = DB::connection()->getDriverName();
        $timeStr = $recurringSlot->start_time instanceof \Carbon\Carbon
            ? $recurringSlot->start_time->format('H:i:s')
            : (string) $recurringSlot->start_time;

        $query = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->orderBy('start_time', 'desc');

        if ($driver === 'mysql') {
            $dayOfWeekSql = ($recurringSlot->day_of_week % 7) + 1; // Carbon 0=Sun -> MySQL DAYOFWEEK 1=Sun
            $query->whereRaw('DAYOFWEEK(start_time) = ?', [$dayOfWeekSql])
                ->whereRaw('TIME(start_time) = ?', [$timeStr]);
        } else {
            // SQLite : strftime('%w', start_time) 0=Sun..6=Sat ; strftime('%H:%M:%S', start_time)
            $query->whereRaw("strftime('%w', start_time) = ?", [(string) ($recurringSlot->day_of_week % 7)])
                ->whereRaw("strftime('%H:%M:%S', start_time) = ?", [substr($timeStr, 0, 8)]);
        }

        return $query->first();
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

        // Utiliser le dernier cours de ce créneau (même jour/heure) comme modèle, sinon n'importe quel cours student+teacher
        $lastLesson = $this->findLastLessonForRecurringSlot($recurringSlot)
            ?? Lesson::where('student_id', $recurringSlot->student_id)
                ->where('teacher_id', $recurringSlot->teacher_id)
                ->orderBy('start_time', 'desc')
                ->first();

        if (!$lastLesson) {
            Log::warning("Aucun cours précédent trouvé pour créneau récurrent #{$recurringSlot->id}");
            return null;
        }

        $closureDateYmd = $startTime->copy()->timezone(config('app.timezone'))->format('Y-m-d');
        if (\App\Models\ClubClosureDay::clubIsClosedOn((int) $lastLesson->club_id, $closureDateYmd)) {
            Log::info('Lesson non générée : jour de fermeture club (legacy récurrence)', [
                'recurring_slot_id' => $recurringSlot->id,
                'club_id' => $lastLesson->club_id,
                'date' => $closureDateYmd,
            ]);

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

    /**
     * Crée (ou retourne) la lesson pour une occurrence précise d’un SubscriptionRecurringSlot (planning club).
     *
     * @return array{success: bool, lesson: ?Lesson, already_existed: bool, message: ?string}
     */
    public function materializeLessonForSingleDate(SubscriptionRecurringSlot $recurringSlot, Carbon $occurrenceDate): array
    {
        $recurringSlot->loadMissing(['subscriptionInstance.subscription', 'student', 'teacher']);

        if ($recurringSlot->status !== 'active') {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Ce créneau récurrent n’est pas actif.',
            ];
        }

        $validator = new RecurringSlotValidator;
        if (! $validator->subscriptionRecurringSlotFiresOnDate($recurringSlot, $occurrenceDate)) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Cette date ne correspond pas à une occurrence de la série.',
            ];
        }

        $dayStart = $occurrenceDate->copy()->startOfDay();
        $timeRaw = $recurringSlot->start_time instanceof Carbon
            ? $recurringSlot->start_time->format('H:i:s')
            : (string) $recurringSlot->start_time;
        $startTime = Carbon::parse($dayStart->format('Y-m-d').' '.substr($timeRaw, 0, 8), config('app.timezone'));

        $existingLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', $startTime)
            ->first();

        if ($existingLesson) {
            return [
                'success' => true,
                'lesson' => $existingLesson,
                'already_existed' => true,
                'message' => null,
            ];
        }

        $clubId = (int) ($recurringSlot->subscriptionInstance?->subscription?->club_id ?? 0);
        if ($clubId > 0 && ClubClosureDay::clubIsClosedOn($clubId, $dayStart->format('Y-m-d'))) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Impossible de créer un cours un jour de fermeture du club.',
            ];
        }

        $reference = $this->findLastLessonForRecurringSlot($recurringSlot)
            ?? Lesson::where('student_id', $recurringSlot->student_id)
                ->where('teacher_id', $recurringSlot->teacher_id)
                ->orderBy('start_time', 'desc')
                ->first();

        if (! $reference) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Aucun cours de référence pour cette série. Utilisez « Ajouter un cours ici » ou créez un cours une première fois.',
            ];
        }

        $subscriptionInstance = $recurringSlot->subscriptionInstance;
        $isSubscriptionActive = $subscriptionInstance && $subscriptionInstance->status === 'active';

        $lesson = $this->createLessonFromRecurringSlot(
            $recurringSlot,
            $dayStart,
            $isSubscriptionActive ? $subscriptionInstance : null
        );

        if (! $lesson) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Impossible de générer le cours (conflit ou contrainte). Réessayez ou créez le cours manuellement.',
            ];
        }

        return [
            'success' => true,
            'lesson' => $lesson,
            'already_existed' => false,
            'message' => null,
        ];
    }
}

