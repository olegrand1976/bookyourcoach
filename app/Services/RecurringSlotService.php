<?php

namespace App\Services;

use App\Models\RecurringSlot;
use App\Models\RecurringSlotSubscription;
use App\Models\Lesson;
use App\Models\LessonRecurringSlot;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use RRule\RRule;

class RecurringSlotService
{
    /**
     * Génère automatiquement les lessons pour un créneau récurrent
     * basé sur la RRULE et la validité de l'abonnement
     * 
     * @param RecurringSlot $recurringSlot Le créneau récurrent
     * @param Carbon|null $startDate Date de début (par défaut: maintenant)
     * @param Carbon|null $endDate Date de fin (par défaut: +3 mois)
     * @return array ['generated' => int, 'skipped' => int, 'errors' => int]
     */
    public function generateLessonsForSlot(
        RecurringSlot $recurringSlot,
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

        // Vérifier que le créneau est actif
        if (!$recurringSlot->isActive()) {
            Log::info("Créneau récurrent #{$recurringSlot->id} n'est pas actif, génération ignorée");
            return $stats;
        }

        // Récupérer l'abonnement actif pour ce créneau
        $activeSubscription = $recurringSlot->activeSubscription;
        if (!$activeSubscription) {
            Log::info("Aucun abonnement actif pour le créneau récurrent #{$recurringSlot->id}");
            return $stats;
        }

        // Vérifier que l'abonnement est valide
        $subscriptionInstance = $activeSubscription->subscriptionInstance;
        if (!$subscriptionInstance || $subscriptionInstance->status !== 'active') {
            Log::info("L'abonnement #{$subscriptionInstance->id} n'est pas actif pour le créneau #{$recurringSlot->id}");
            return $stats;
        }

        // Générer les dates basées sur la RRULE
        $dates = $recurringSlot->generateDates($startDate, $endDate);

        // Filtrer les dates selon la validité de l'abonnement
        $validDates = $this->filterDatesBySubscriptionValidity(
            $dates,
            $recurringSlot,
            $activeSubscription,
            $subscriptionInstance
        );

        Log::info("Génération de lessons pour créneau #{$recurringSlot->id}", [
            'total_dates' => count($dates),
            'valid_dates' => count($validDates),
            'subscription_instance_id' => $subscriptionInstance->id,
        ]);

        // Générer les lessons pour chaque date valide
        foreach ($validDates as $date) {
            try {
                $lesson = $this->createLessonFromRecurringSlot($recurringSlot, $date, $subscriptionInstance);
                
                if ($lesson) {
                    $stats['generated']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Erreur lors de la génération d'une lesson pour créneau #{$recurringSlot->id}", [
                    'date' => $date->format('Y-m-d H:i'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Filtre les dates selon la validité de l'abonnement
     * 
     * @param array $dates Tableau de dates Carbon
     * @param RecurringSlot $recurringSlot Le créneau récurrent
     * @param RecurringSlotSubscription $subscriptionLink Liaison créneau-abonnement
     * @param SubscriptionInstance $subscriptionInstance Instance d'abonnement
     * @return array Dates filtrées
     */
    private function filterDatesBySubscriptionValidity(
        array $dates,
        RecurringSlot $recurringSlot,
        RecurringSlotSubscription $subscriptionLink,
        SubscriptionInstance $subscriptionInstance
    ): array {
        $validDates = [];
        $maxLessons = $subscriptionInstance->subscription->total_lessons ?? PHP_INT_MAX;

        foreach ($dates as $date) {
            // Vérifier que la date est dans la période de validité de l'abonnement
            if ($date->lt($subscriptionLink->start_date) || $date->gt($subscriptionLink->end_date)) {
                continue;
            }

            // Vérifier que l'abonnement n'a pas expiré
            if ($subscriptionInstance->expires_at && $date->gt($subscriptionInstance->expires_at)) {
                continue;
            }

            // Vérifier qu'on n'a pas atteint le nombre maximum de cours
            // On compte les lessons déjà générées pour cet abonnement
            $existingLessonsCount = $this->countGeneratedLessonsForSubscription($subscriptionInstance);
            if ($existingLessonsCount >= $maxLessons) {
                Log::info("Limite de cours atteinte pour abonnement #{$subscriptionInstance->id}", [
                    'existing' => $existingLessonsCount,
                    'max' => $maxLessons,
                ]);
                break;
            }

            // Vérifier qu'une lesson n'existe pas déjà pour cette date/heure
            if ($this->lessonExistsForSlotAndDate($recurringSlot, $date)) {
                continue;
            }

            $validDates[] = $date;
        }

        return $validDates;
    }

    /**
     * Crée une lesson à partir d'un créneau récurrent
     * 
     * @param RecurringSlot $recurringSlot
     * @param Carbon $date
     * @param SubscriptionInstance $subscriptionInstance
     * @return Lesson|null
     */
    private function createLessonFromRecurringSlot(
        RecurringSlot $recurringSlot,
        Carbon $date,
        SubscriptionInstance $subscriptionInstance
    ): ?Lesson {
        // Vérifier qu'une lesson n'existe pas déjà
        if ($this->lessonExistsForSlotAndDate($recurringSlot, $date)) {
            return null;
        }

        // Calculer l'heure de début et de fin
        $startTime = $date->copy()->setTimeFromTimeString(
            $recurringSlot->reference_start_time->format('H:i:s')
        );
        $endTime = $startTime->copy()->addMinutes($recurringSlot->duration_minutes);

        // Récupérer un lieu par défaut
        $locationId = $this->getDefaultLocationId($recurringSlot);

        // Créer la lesson
        $lesson = Lesson::create([
            'club_id' => $recurringSlot->club_id,
            'teacher_id' => $recurringSlot->teacher_id,
            'student_id' => $recurringSlot->student_id,
            'course_type_id' => $recurringSlot->course_type_id,
            'location_id' => $locationId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'confirmed', // Lessons générées automatiquement sont confirmées
            'price' => $this->calculateLessonPrice($recurringSlot, $subscriptionInstance),
            'notes' => "Cours généré automatiquement depuis créneau récurrent #{$recurringSlot->id}",
        ]);

        // Lier la lesson à l'abonnement
        $subscriptionInstance->lessons()->attach($lesson->id);

        // Créer la liaison avec le créneau récurrent
        LessonRecurringSlot::create([
            'lesson_id' => $lesson->id,
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $subscriptionInstance->id,
            'generated_at' => Carbon::now(),
            'generated_by' => 'auto',
        ]);

        // Consommer l'abonnement
        $subscriptionInstance->consumeLesson($lesson);

        Log::info("Lesson #{$lesson->id} générée automatiquement pour créneau #{$recurringSlot->id}", [
            'date' => $startTime->format('Y-m-d H:i'),
            'subscription_instance_id' => $subscriptionInstance->id,
        ]);

        return $lesson;
    }

    /**
     * Vérifie si une lesson existe déjà pour ce créneau et cette date
     */
    private function lessonExistsForSlotAndDate(RecurringSlot $recurringSlot, Carbon $date): bool
    {
        $startTime = $date->copy()->setTimeFromTimeString(
            $recurringSlot->reference_start_time->format('H:i:s')
        );
        $endTime = $startTime->copy()->addMinutes($recurringSlot->duration_minutes);

        return Lesson::where('teacher_id', $recurringSlot->teacher_id)
            ->where('student_id', $recurringSlot->student_id)
            ->where('start_time', '>=', $startTime)
            ->where('start_time', '<', $endTime)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * Compte les lessons déjà générées pour un abonnement
     */
    private function countGeneratedLessonsForSubscription(SubscriptionInstance $subscriptionInstance): int
    {
        return LessonRecurringSlot::where('subscription_instance_id', $subscriptionInstance->id)
            ->where('generated_by', 'auto')
            ->whereHas('lesson', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->count();
    }

    /**
     * Calcule le prix d'une lesson
     */
    private function calculateLessonPrice(RecurringSlot $recurringSlot, SubscriptionInstance $subscriptionInstance): float
    {
        // Utiliser le prix du type de cours si disponible
        if ($recurringSlot->courseType && $recurringSlot->courseType->price) {
            return $recurringSlot->courseType->price;
        }

        // Sinon, utiliser le prix de l'abonnement divisé par le nombre de cours
        $subscription = $subscriptionInstance->subscription;
        if ($subscription && $subscription->total_lessons > 0) {
            return $subscription->price / $subscription->total_lessons;
        }

        // Par défaut : 0 (gratuit)
        return 0.0;
    }

    /**
     * Récupère un lieu par défaut pour le créneau récurrent
     */
    private function getDefaultLocationId(RecurringSlot $recurringSlot): ?int
    {
        // Essayer de récupérer un lieu depuis le club
        if ($recurringSlot->club) {
            $clubLocation = \App\Models\Location::where('club_id', $recurringSlot->club_id)
                ->first();
            
            if ($clubLocation) {
                return $clubLocation->id;
            }
        }

        // Essayer de récupérer un lieu depuis le type de cours
        if ($recurringSlot->courseType) {
            // Si le type de cours a un lieu par défaut
            // (à implémenter selon votre modèle de données)
        }

        // Récupérer le premier lieu disponible
        $defaultLocation = \App\Models\Location::first();
        
        if (!$defaultLocation) {
            Log::warning("Aucun lieu disponible pour créer la lesson du créneau récurrent #{$recurringSlot->id}");
            throw new \Exception("Aucun lieu disponible pour créer la lesson");
        }

        return $defaultLocation->id;
    }

    /**
     * Génère les lessons pour tous les créneaux récurrents actifs
     * 
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array Statistiques globales
     */
    public function generateLessonsForAllActiveSlots(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now();
        $endDate = $endDate ?? Carbon::now()->addMonths(3);

        $recurringSlots = RecurringSlot::active()
            ->with(['activeSubscription.subscriptionInstance.subscription'])
            ->get();

        $globalStats = [
            'slots_processed' => 0,
            'lessons_generated' => 0,
            'lessons_skipped' => 0,
            'errors' => 0,
        ];

        foreach ($recurringSlots as $slot) {
            try {
                $stats = $this->generateLessonsForSlot($slot, $startDate, $endDate);
                
                $globalStats['slots_processed']++;
                $globalStats['lessons_generated'] += $stats['generated'];
                $globalStats['lessons_skipped'] += $stats['skipped'];
                $globalStats['errors'] += $stats['errors'];
            } catch (\Exception $e) {
                $globalStats['errors']++;
                Log::error("Erreur lors de la génération pour créneau #{$slot->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $globalStats;
    }

    /**
     * Expire les liaisons abonnement-créneau qui ont dépassé leur date de fin
     */
    public function expireSubscriptionLinks(): int
    {
        $expiredLinks = RecurringSlotSubscription::where('status', 'active')
            ->where('end_date', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredLinks as $link) {
            $link->expire();
            $count++;
        }

        Log::info("{$count} liaisons abonnement-créneau expirées");

        return $count;
    }
}

