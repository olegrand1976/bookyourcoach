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

    private function shouldLogRecurringConflicts(): bool
    {
        return (bool) config('bookyourcoach.log_recurring_validation_conflicts', true);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logRecurringConflict(string $reason, array $context): void
    {
        if (!$this->shouldLogRecurringConflicts()) {
            return;
        }

        Log::warning('[RecurringAvailability] conflit', array_merge([
            'reason' => $reason,
        ], $context));
    }

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
     * Valider la disponibilité sur 26 semaines sans créneau ouvert (création depuis un cours).
     * Vérifie enseignant et élève sur chaque occurrence (selon la fréquence recurring_interval).
     *
     * @param int $teacherId
     * @param int $studentId
     * @param string $startDate Date de début (Y-m-d)
     * @param int $dayOfWeek 0=Dim, 1=Lun, etc.
     * @param string $startTime H:i ou H:i:s
     * @param string $endTime H:i ou H:i:s
     * @param int $recurringInterval Fréquence en semaines (1=hebdo, 2=bi-hebdo, etc.). Défaut 1.
     * @param int|null $excludeLessonId ID d'un cours à exclure du contrôle (ex: cours déclencheur déjà créé)
     * @return array ['valid' => bool, 'conflicts' => array, 'message' => string]
     */
    public function validateRecurringAvailabilityWithoutOpenSlot(
        int $teacherId,
        int $studentId,
        string $startDate,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $recurringInterval = 1,
        ?int $excludeLessonId = null
    ): array {
        $startDate = Carbon::parse($startDate);
        $conflicts = [];
        $recurringInterval = max(1, min(52, $recurringInterval));

        Log::info("🔍 Validation récurrence (sans open_slot)", [
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'start_date' => $startDate->format('Y-m-d'),
            'day_of_week' => $dayOfWeek,
            'recurring_interval' => $recurringInterval,
            'weeks_to_check' => self::VALIDATION_WEEKS
        ]);

        for ($k = 0; $k * $recurringInterval < self::VALIDATION_WEEKS; $k++) {
            $occurrenceDate = $this->getNextOccurrence($startDate, $dayOfWeek, $k * $recurringInterval);

            $teacherConflict = $this->checkTeacherAvailability(
                $teacherId,
                $occurrenceDate,
                $startTime,
                $endTime,
                $excludeLessonId,
                $k
            );
            if ($teacherConflict) {
                $conflicts[] = [
                    'type' => 'teacher_unavailable',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => $teacherConflict
                ];
            }

            $studentConflict = $this->checkStudentAvailability(
                $studentId,
                $occurrenceDate,
                $startTime,
                $endTime,
                $excludeLessonId,
                $k
            );
            if ($studentConflict) {
                $conflicts[] = [
                    'type' => 'student_unavailable',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => $studentConflict
                ];
            }
        }

        $valid = empty($conflicts);
        Log::info($valid ? "✅ Récurrence validée (sans open_slot)" : "❌ Récurrence invalide (sans open_slot)", [
            'conflicts_count' => count($conflicts),
            'conflicts' => array_slice($conflicts, 0, 5)
        ]);

        if (!$valid && $this->shouldLogRecurringConflicts()) {
            $byType = [];
            foreach ($conflicts as $c) {
                $t = (string) ($c['type'] ?? 'unknown');
                $byType[$t] = ($byType[$t] ?? 0) + 1;
            }
            Log::warning('[RecurringAvailability] résumé échec validation', [
                'teacher_id' => $teacherId,
                'student_id' => $studentId,
                'proposed_local' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'recurring_interval_weeks' => $recurringInterval,
                ],
                'app_timezone' => config('app.timezone'),
                'exclude_lesson_id' => $excludeLessonId,
                'counts_by_conflict_type' => $byType,
                'unique_dates' => array_values(array_unique(array_map(fn (array $c) => $c['date'] ?? '', $conflicts))),
            ]);
        }

        return [
            'valid' => $valid,
            'conflicts' => $conflicts,
            'message' => $valid
                ? 'Créneau disponible pour les 6 prochains mois'
                : 'Conflits détectés sur ' . count($conflicts) . ' occurrence(s)'
        ];
    }

    /**
     * Normalise une heure (TIME SQL, H:i ou H:i:s) pour construction datetime locale.
     */
    private function normalizeTimeToHis(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        $s = trim((string) $time);
        if ($s === '') {
            return '00:00:00';
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $s)) {
            return $s . ':00';
        }

        return $s;
    }

    /**
     * Fenêtre d'une occurrence en fuseau app → bornes UTC pour comparaison SQL sur lessons (datetimes stockés en UTC).
     *
     * @return array{0: string, 1: string} [startUtc, endUtc] format Y-m-d H:i:s
     */
    private function occurrenceUtcBounds(Carbon $occurrenceDate, string $startTime, string $endTime): array
    {
        $tz = config('app.timezone');
        $dateStr = $occurrenceDate->format('Y-m-d');
        $startHms = $this->normalizeTimeToHis($startTime);
        $endHms = $this->normalizeTimeToHis($endTime);

        $localStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $startHms, $tz);
        $localEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $endHms, $tz);
        if ($localEnd->lte($localStart)) {
            $localEnd->addDay();
        }

        return [
            $localStart->utc()->format('Y-m-d H:i:s'),
            $localEnd->utc()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Indique si un SubscriptionRecurringSlot génère réellement une occurrence à la date donnée,
     * en suivant la même logique que LegacyRecurringSlotService::generateDatesForRecurringSlot
     * (ancrage sur start_date + jour de la semaine, puis une occurrence tous les recurring_interval semaines).
     */
    private function subscriptionRecurringSlotFiresOnDate(SubscriptionRecurringSlot $slot, Carbon $occurrenceDate): bool
    {
        $interval = max(1, (int) ($slot->recurring_interval ?? 1));
        $occurrence = $occurrenceDate->copy()->startOfDay();
        $slotEnd = Carbon::parse($slot->end_date)->endOfDay();
        $anchorBase = Carbon::parse($slot->start_date)->startOfDay();

        if ($occurrence->lt($anchorBase) || $occurrence->gt($slotEnd)) {
            return false;
        }
        if ((int) $occurrence->dayOfWeek !== (int) $slot->day_of_week) {
            return false;
        }

        $anchor = $anchorBase->copy();
        while ($anchor->dayOfWeek !== (int) $slot->day_of_week) {
            $anchor->addDay();
        }

        if ($occurrence->lt($anchor)) {
            return false;
        }

        $daysBetween = $anchor->diffInDays($occurrence, false);
        if ($daysBetween < 0 || ($daysBetween % 7) !== 0) {
            return false;
        }

        $weekIndex = (int) ($daysBetween / 7);

        return ($weekIndex % $interval) === 0;
    }

    /**
     * Vérifier si l'élève a déjà un cours ou une récurrence à cette date/heure
     */
    private function checkStudentAvailability(
        int $studentId,
        Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeLessonId = null,
        int $weekIndex = 0
    ): ?string {
        [$startUtc, $endUtc] = $this->occurrenceUtcBounds($date, $startTime, $endTime);

        $lessonQuery = Lesson::where('student_id', $studentId)
            ->where('start_time', '<', $endUtc)
            ->where('end_time', '>', $startUtc)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeLessonId) {
            $lessonQuery->where('id', '!=', $excludeLessonId);
        }

        $conflictLesson = $lessonQuery->first([
            'id', 'start_time', 'end_time', 'teacher_id', 'club_id', 'status', 'course_type_id',
        ]);

        if ($conflictLesson) {
            $this->logRecurringConflict('student_lesson_overlap', [
                'week_loop_index' => $weekIndex,
                'occurrence_date' => $date->format('Y-m-d'),
                'student_id' => $studentId,
                'proposed_utc_window' => ['start' => $startUtc, 'end' => $endUtc],
                'conflicting_lesson_id' => $conflictLesson->id,
                'conflicting_lesson' => [
                    'start_time' => $conflictLesson->start_time?->format('Y-m-d H:i:s'),
                    'end_time' => $conflictLesson->end_time?->format('Y-m-d H:i:s'),
                    'teacher_id' => $conflictLesson->teacher_id,
                    'club_id' => $conflictLesson->club_id,
                    'status' => $conflictLesson->status,
                    'course_type_id' => $conflictLesson->course_type_id,
                ],
            ]);

            return "Élève déjà occupé (cours)";
        }

        $recurringCandidates = SubscriptionRecurringSlot::activeOnDate($date)
            ->where('student_id', $studentId)
            ->byDayOfWeek($date->dayOfWeek)
            ->lessonLikeTimeWindow()
            ->byTimeRange($startTime, $endTime)
            ->get();

        foreach ($recurringCandidates as $slot) {
            if ($this->subscriptionRecurringSlotFiresOnDate($slot, $date)) {
                $this->logRecurringConflict('student_subscription_recurring_overlap', [
                    'week_loop_index' => $weekIndex,
                    'occurrence_date' => $date->format('Y-m-d'),
                    'student_id' => $studentId,
                    'proposed_utc_window' => ['start' => $startUtc, 'end' => $endUtc],
                    'subscription_recurring_slot_id' => $slot->id,
                    'recurring_slot' => [
                        'teacher_id' => $slot->teacher_id,
                        'open_slot_id' => $slot->open_slot_id,
                        'subscription_instance_id' => $slot->subscription_instance_id,
                        'day_of_week' => $slot->day_of_week,
                        'start_time' => (string) $slot->start_time,
                        'end_time' => (string) $slot->end_time,
                        'recurring_interval' => $slot->recurring_interval,
                        'start_date' => $slot->start_date?->format('Y-m-d'),
                        'end_date' => $slot->end_date?->format('Y-m-d'),
                        'status' => $slot->status,
                    ],
                ]);

                return "Élève déjà réservé (récurrence)";
            }
        }

        return null;
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

        // Compter les cours dont l'intervalle chevauche la fenêtre du créneau ouvert (date + heures en fuseau app → UTC)
        $slotStart = $this->normalizeTimeToHis($openSlot->start_time);
        $slotEnd = $this->normalizeTimeToHis($openSlot->end_time);
        [$windowStartUtc, $windowEndUtc] = $this->occurrenceUtcBounds($date, $slotStart, $slotEnd);

        $existingLessonsCount = Lesson::where('club_id', $openSlot->club_id)
            ->where('start_time', '<', $windowEndUtc)
            ->where('end_time', '>', $windowStartUtc)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        // Compter les récurrences actives à cette date d'occurrence (pas seulement "aujourd'hui")
        $recurringSlots = SubscriptionRecurringSlot::activeOnDate($date)
            ->byDayOfWeek($date->dayOfWeek)
            ->lessonLikeTimeWindow()
            ->byTimeRange($openSlot->start_time, $openSlot->end_time)
            ->whereHas('openSlot', function ($query) use ($openSlot) {
                $query->where('club_id', $openSlot->club_id);
            })
            ->get();

        $recurringCount = $recurringSlots
            ->filter(fn (SubscriptionRecurringSlot $s) => $this->subscriptionRecurringSlotFiresOnDate($s, $date))
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
        string $endTime,
        ?int $excludeLessonId = null,
        int $weekIndex = 0
    ): ?string {
        [$startUtc, $endUtc] = $this->occurrenceUtcBounds($date, $startTime, $endTime);

        // Vérifier les cours existants (chevauchement d'intervalles réels en UTC, pas whereDate/whereTime)
        $lessonQuery = Lesson::where('teacher_id', $teacherId)
            ->where('start_time', '<', $endUtc)
            ->where('end_time', '>', $startUtc)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeLessonId) {
            $lessonQuery->where('id', '!=', $excludeLessonId);
        }

        $conflictLesson = $lessonQuery->first([
            'id', 'start_time', 'end_time', 'student_id', 'club_id', 'status', 'course_type_id',
        ]);

        if ($conflictLesson) {
            $this->logRecurringConflict('teacher_lesson_overlap', [
                'week_loop_index' => $weekIndex,
                'occurrence_date' => $date->format('Y-m-d'),
                'teacher_id' => $teacherId,
                'proposed_utc_window' => ['start' => $startUtc, 'end' => $endUtc],
                'conflicting_lesson_id' => $conflictLesson->id,
                'conflicting_lesson' => [
                    'start_time' => $conflictLesson->start_time?->format('Y-m-d H:i:s'),
                    'end_time' => $conflictLesson->end_time?->format('Y-m-d H:i:s'),
                    'student_id' => $conflictLesson->student_id,
                    'club_id' => $conflictLesson->club_id,
                    'status' => $conflictLesson->status,
                    'course_type_id' => $conflictLesson->course_type_id,
                ],
            ]);

            return "Enseignant déjà occupé";
        }

        // Vérifier les récurrences actives à cette date d'occurrence
        $teacherRecurringCandidates = SubscriptionRecurringSlot::activeOnDate($date)
            ->byTeacher($teacherId)
            ->byDayOfWeek($date->dayOfWeek)
            ->lessonLikeTimeWindow()
            ->byTimeRange($startTime, $endTime)
            ->get();

        foreach ($teacherRecurringCandidates as $slot) {
            if ($this->subscriptionRecurringSlotFiresOnDate($slot, $date)) {
                $this->logRecurringConflict('teacher_subscription_recurring_overlap', [
                    'week_loop_index' => $weekIndex,
                    'occurrence_date' => $date->format('Y-m-d'),
                    'teacher_id' => $teacherId,
                    'proposed_utc_window' => ['start' => $startUtc, 'end' => $endUtc],
                    'subscription_recurring_slot_id' => $slot->id,
                    'recurring_slot' => [
                        'student_id' => $slot->student_id,
                        'open_slot_id' => $slot->open_slot_id,
                        'subscription_instance_id' => $slot->subscription_instance_id,
                        'day_of_week' => $slot->day_of_week,
                        'start_time' => (string) $slot->start_time,
                        'end_time' => (string) $slot->end_time,
                        'recurring_interval' => $slot->recurring_interval,
                        'start_date' => $slot->start_date?->format('Y-m-d'),
                        'end_date' => $slot->end_date?->format('Y-m-d'),
                        'status' => $slot->status,
                    ],
                ]);

                return "Enseignant déjà réservé (récurrence)";
            }
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
            'start_date' => $startDate,
            'end_date' => $expiresAt,
            'status' => 'active',
        ]);

        Log::info("✅ Récurrence créée", [
            'recurring_slot_id' => $recurringSlot->id,
            'subscription_instance_id' => $subscriptionInstance->id,
            'open_slot_id' => $openSlotId,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $expiresAt->format('Y-m-d'),
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

