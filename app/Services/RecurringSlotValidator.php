<?php

namespace App\Services;

use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringSlotValidator
{
    /**
     * Nombre de semaines à vérifier (6 mois ≈ 26 semaines)
     */
    const VALIDATION_WEEKS = 26;

    /**
     * Valider qu'un créneau récurrent est disponible pour les 6 prochains mois
     *
     * @param int $openSlotId ID du créneau
     * @param int $teacherId ID de l'enseignant
     * @param int $studentId ID de l'élève
     * @param string $startDate Date de début (Y-m-d)
     * @return array ['valid' => bool, 'conflicts' => array, 'message' => string]
     */
    public function validateRecurringAvailability(
        int $openSlotId,
        int $teacherId,
        int $studentId,
        string $startDate
    ): array {
        $openSlot = ClubOpenSlot::find($openSlotId);
        
        if (!$openSlot) {
            return [
                'valid' => false,
                'conflicts' => [],
                'message' => 'Créneau introuvable'
            ];
        }

        $startDate = Carbon::parse($startDate);
        $conflicts = [];

        Log::info("🔍 Validation récurrence", [
            'open_slot_id' => $openSlotId,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'start_date' => $startDate->format('Y-m-d'),
            'weeks_to_check' => self::VALIDATION_WEEKS
        ]);

        // Vérifier chaque occurrence sur les 26 prochaines semaines
        for ($week = 0; $week < self::VALIDATION_WEEKS; $week++) {
            $occurrenceDate = $this->getNextOccurrence($startDate, $openSlot->day_of_week, $week);
            
            // 1. Vérifier la capacité du créneau
            $slotConflict = $this->checkSlotCapacity($openSlot, $occurrenceDate);
            if ($slotConflict) {
                $conflicts[] = [
                    'type' => 'slot_capacity',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => $slotConflict
                ];
            }

            // 2. Vérifier la disponibilité de l'enseignant
            $teacherConflict = $this->checkTeacherAvailability(
                $teacherId,
                $occurrenceDate,
                $openSlot->start_time,
                $openSlot->end_time
            );
            if ($teacherConflict) {
                $conflicts[] = [
                    'type' => 'teacher_unavailable',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => $teacherConflict
                ];
            }
        }

        $valid = empty($conflicts);

        Log::info($valid ? "✅ Récurrence validée" : "❌ Récurrence invalide", [
            'conflicts_count' => count($conflicts),
            'conflicts' => array_slice($conflicts, 0, 5) // Limiter le log aux 5 premiers conflits
        ]);

        return [
            'valid' => $valid,
            'conflicts' => $conflicts,
            'message' => $valid 
                ? 'Créneau disponible pour les 6 prochains mois' 
                : 'Conflits détectés sur ' . count($conflicts) . ' occurrence(s)'
        ];
    }

    /**
     * Calculer la prochaine occurrence d'un jour de la semaine
     *
     * @param Carbon $startDate Date de départ
     * @param int $dayOfWeek Jour de la semaine (0=Dim, 1=Lun, etc.)
     * @param int $weeksToAdd Nombre de semaines à ajouter
     * @return Carbon
     */
    private function getNextOccurrence(Carbon $startDate, int $dayOfWeek, int $weeksToAdd = 0): Carbon
    {
        $date = $startDate->copy();
        
        // Trouver le prochain jour correspondant
        $currentDayOfWeek = $date->dayOfWeek;
        $daysToAdd = ($dayOfWeek - $currentDayOfWeek + 7) % 7;
        
        if ($daysToAdd > 0 || $weeksToAdd > 0) {
            $date->addDays($daysToAdd + ($weeksToAdd * 7));
        }
        
        return $date;
    }

    /**
     * Vérifier si le créneau a atteint sa capacité maximale pour une date donnée
     *
     * @param ClubOpenSlot $openSlot
     * @param Carbon $date
     * @return string|null Message d'erreur ou null si OK
     */
    private function checkSlotCapacity(ClubOpenSlot $openSlot, Carbon $date): ?string
    {
        // Si pas de limite de capacité, toujours OK
        if (!$openSlot->max_capacity && !$openSlot->max_slots) {
            return null;
        }

        // Compter les cours existants ce jour-là dans cette plage horaire
        $existingLessonsCount = Lesson::where('club_id', $openSlot->club_id)
            ->whereDate('start_time', $date->format('Y-m-d'))
            ->whereTime('start_time', '>=', $openSlot->start_time)
            ->whereTime('start_time', '<', $openSlot->end_time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        // Compter les récurrences actives ce jour-là
        $recurringCount = SubscriptionRecurringSlot::active()
            ->byDayOfWeek($date->dayOfWeek)
            ->byTimeRange($openSlot->start_time, $openSlot->end_time)
            ->whereHas('openSlot', function ($query) use ($openSlot) {
                $query->where('club_id', $openSlot->club_id);
            })
            ->count();

        $totalCount = $existingLessonsCount + $recurringCount;
        $maxCapacity = $openSlot->max_capacity ?? $openSlot->max_slots;

        if ($maxCapacity && $totalCount >= $maxCapacity) {
            return "Capacité max atteinte ({$totalCount}/{$maxCapacity})";
        }

        return null;
    }

    /**
     * Vérifier si l'enseignant est disponible pour une date/heure donnée
     *
     * @param int $teacherId
     * @param Carbon $date
     * @param string $startTime
     * @param string $endTime
     * @return string|null Message d'erreur ou null si OK
     */
    private function checkTeacherAvailability(
        int $teacherId,
        Carbon $date,
        string $startTime,
        string $endTime
    ): ?string {
        // Vérifier les cours existants
        $conflictingLesson = Lesson::where('teacher_id', $teacherId)
            ->whereDate('start_time', $date->format('Y-m-d'))
            ->where(function ($query) use ($startTime, $endTime) {
                // Conflit si les plages se chevauchent
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->whereTime('start_time', '<', $endTime)
                      ->whereTime('end_time', '>', $startTime);
                });
            })
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($conflictingLesson) {
            return "Enseignant déjà occupé";
        }

        // Vérifier les récurrences actives
        $conflictingRecurring = SubscriptionRecurringSlot::active()
            ->byTeacher($teacherId)
            ->byDayOfWeek($date->dayOfWeek)
            ->byTimeRange($startTime, $endTime)
            ->exists();

        if ($conflictingRecurring) {
            return "Enseignant déjà réservé (récurrence)";
        }

        return null;
    }

    /**
     * Créer une réservation récurrente pour un abonnement
     *
     * @param SubscriptionInstance $subscriptionInstance
     * @param int $openSlotId
     * @param int $teacherId
     * @param int $studentId
     * @param string $startDate
     * @return SubscriptionRecurringSlot
     */
    public function createRecurringSlot(
        SubscriptionInstance $subscriptionInstance,
        int $openSlotId,
        int $teacherId,
        int $studentId,
        string $startDate
    ): SubscriptionRecurringSlot {
        $openSlot = ClubOpenSlot::findOrFail($openSlotId);
        $startDate = Carbon::parse($startDate);
        
        // Calculer la date d'expiration (la plus proche entre expires_at de l'abonnement et 6 mois)
        $subscriptionExpires = $subscriptionInstance->expires_at 
            ? Carbon::parse($subscriptionInstance->expires_at) 
            : $startDate->copy()->addMonths(6);
        
        $sixMonthsLater = $startDate->copy()->addMonths(6);
        $expiresAt = $subscriptionExpires->lt($sixMonthsLater) ? $subscriptionExpires : $sixMonthsLater;

        $recurringSlot = SubscriptionRecurringSlot::create([
            'subscription_instance_id' => $subscriptionInstance->id,
            'open_slot_id' => $openSlotId,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'day_of_week' => $openSlot->day_of_week,
            'start_time' => $openSlot->start_time,
            'end_time' => $openSlot->end_time,
            'started_at' => $startDate,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        Log::info("✅ Récurrence créée", [
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $subscriptionInstance->id,
            'open_slot_id' => $openSlotId,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'started_at' => $startDate->format('Y-m-d'),
            'expires_at' => $expiresAt->format('Y-m-d'),
        ]);

        return $recurringSlot;
    }

    /**
     * Annuler une récurrence (par exemple, si l'abonnement est annulé)
     *
     * @param SubscriptionRecurringSlot $recurringSlot
     * @param string|null $reason
     * @return void
     */
    public function cancelRecurringSlot(SubscriptionRecurringSlot $recurringSlot, ?string $reason = null): void
    {
        $recurringSlot->cancel($reason);

        Log::info("🚫 Récurrence annulée", [
            'recurring_slot_id' => $recurringSlot->id,
            'reason' => $reason
        ]);
    }
}

