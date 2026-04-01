<?php

namespace App\Services;

use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\SubscriptionRecurringSlot;
use App\Models\SubscriptionInstance;
use Illuminate\Database\Eloquent\Builder;
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
                $openSlot->end_time,
                null,
                $week,
                null,
                $studentId
            );
            if ($teacherConflict !== null) {
                $conflicts[] = [
                    'type' => 'teacher_unavailable',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => $teacherConflict['message'],
                    'lesson_id' => $teacherConflict['lesson_id'] ?? null,
                    'recurring_slot_id' => $teacherConflict['recurring_slot_id'] ?? null,
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
     * @param int|null $clubId Si défini, ne considère que les cours et récurrences de cet abonnement/club (évite faux conflits inter-clubs).
     * @return array{valid: bool, conflicts: array, message: string, hint: ?string}
     */
    public function validateRecurringAvailabilityWithoutOpenSlot(
        int $teacherId,
        int $studentId,
        string $startDate,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $recurringInterval = 1,
        ?int $excludeLessonId = null,
        ?int $clubId = null
    ): array {
        $startDate = Carbon::parse($startDate);
        $conflicts = [];
        $recurringInterval = max(1, min(52, $recurringInterval));

        Log::info("🔍 Validation récurrence (sans open_slot)", [
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'club_id' => $clubId,
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
                $k,
                $clubId,
                $studentId
            );

            $studentConflict = $this->checkStudentAvailability(
                $studentId,
                $occurrenceDate,
                $startTime,
                $endTime,
                $excludeLessonId,
                $k,
                $clubId
            );

            // Même cours (lesson_id) bloque enseignant et élève : un seul message (évite doublon bruyant le 1er jour)
            if ($teacherConflict !== null && $studentConflict !== null) {
                $tLid = $teacherConflict['lesson_id'] ?? null;
                $sLid = $studentConflict['lesson_id'] ?? null;
                if ($tLid !== null && $sLid !== null && (int) $tLid === (int) $sLid) {
                    $conflicts[] = [
                        'type' => 'lesson_overlap',
                        'date' => $occurrenceDate->format('Y-m-d'),
                        'message' => 'Un cours existe déjà à ce créneau pour cet enseignant et cet élève.',
                        'lesson_id' => (int) $tLid,
                        'recurring_slot_id' => null,
                        'lesson_teacher_id' => $teacherConflict['lesson_teacher_id'] ?? null,
                        'lesson_student_id' => $studentConflict['lesson_student_id'] ?? null,
                    ];
                    continue;
                }
            }

            // Cours déjà planifié (Lesson) côté prof + récurrence côté élève (ou l’inverse) : même réservation, un seul message
            $mergedLessonRecurring = $this->mergeLessonConflictWithRecurringConflict(
                $teacherConflict,
                $studentConflict,
                $teacherId,
                $studentId
            );
            if ($mergedLessonRecurring !== null) {
                $conflicts[] = array_merge($mergedLessonRecurring, [
                    'date' => $occurrenceDate->format('Y-m-d'),
                ]);
                continue;
            }

            $teacherRid = $teacherConflict !== null ? ($teacherConflict['recurring_slot_id'] ?? null) : null;
            $studentRid = $studentConflict !== null ? ($studentConflict['recurring_slot_id'] ?? null) : null;
            // Même id : doublon seulement si la ligne en base correspond exactement au couple (enseignant, élève) demandé
            // (évite un faux « recurring_duplicate » si deux branches renvoient le même id par incohérence / données).
            $sameRecurringSlot = false;
            $duplicateSlotRow = null;
            if ($teacherRid !== null && $studentRid !== null && (int) $teacherRid === (int) $studentRid) {
                $duplicateSlotRow = SubscriptionRecurringSlot::query()->find((int) $teacherRid);
                if ($duplicateSlotRow !== null
                    && (int) $duplicateSlotRow->teacher_id === (int) $teacherId
                    && (int) $duplicateSlotRow->student_id === (int) $studentId) {
                    $sameRecurringSlot = true;
                }
            }

            if ($sameRecurringSlot && $duplicateSlotRow !== null) {
                $conflicts[] = [
                    'type' => 'recurring_duplicate',
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'message' => 'Créneau récurrent déjà enregistré pour cet élève et cet enseignant à cet horaire.',
                    'recurring_slot_id' => (int) $teacherRid,
                    'lesson_id' => null,
                    'slot_student_id' => (int) $duplicateSlotRow->student_id,
                    'slot_teacher_id' => (int) $duplicateSlotRow->teacher_id,
                    'subscription_instance_id' => (int) $duplicateSlotRow->subscription_instance_id,
                    'recurring_slot' => $this->recurringSlotDiagnostic($duplicateSlotRow),
                ];
                continue;
            }

            if ($teacherConflict !== null) {
                $conflicts[] = $this->conflictPayloadFromCheck(
                    'teacher_unavailable',
                    $occurrenceDate->format('Y-m-d'),
                    $teacherConflict
                );
            }

            if ($studentConflict !== null) {
                $conflicts[] = $this->conflictPayloadFromCheck(
                    'student_unavailable',
                    $occurrenceDate->format('Y-m-d'),
                    $studentConflict
                );
            }
        }

        $valid = empty($conflicts);

        $hint = null;
        if (! $valid) {
            $hasDuplicateRecurring = false;
            foreach ($conflicts as $c) {
                if (($c['type'] ?? '') === 'recurring_duplicate') {
                    $hasDuplicateRecurring = true;
                    break;
                }
            }
            if ($hasDuplicateRecurring) {
                $hint = 'Une réservation récurrente identique (même élève, même enseignant et même horaire) existe déjà pour ce club — voir « recurring_slot » dans la réponse (noms et horaires réels en base). Ce blocage vise l’entrée « créneau récurrent », pas seulement une carte sur le planning : la ligne peut exister sans cours encore généré pour cette date. Deux élèves différents sur le même abonnement peuvent avoir cours au même moment avec des enseignants différents. Libérez ou modifiez le créneau récurrent (Menu Club → Créneaux récurrents), ou « Une seule séance » / sans déduction d’abonnement pour éviter une deuxième série.';
            } else {
                $hint = 'Cette vérification teste chaque occurrence sur 26 semaines : le planning ou « Créneaux récurrents » peut ne pas montrer le même périmètre. Un refus « déjà réservé (récurrence) » peut venir d’une ligne abonnement avec un autre élève sur le même enseignant et la même plage, ou d’un cours déjà généré sur une autre semaine. Les cours consécutifs (fin = début exact) ne sont pas traités comme chevauchement. Pour une seule date sans validation 26 sem. : « Une seule séance » dans la modale.';
            }
        }

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
            'conflicts' => array_map(fn (array $c) => $this->enrichConflictForApi($c), $conflicts),
            'message' => $valid
                ? 'Créneau disponible pour les 6 prochains mois'
                : 'Conflits détectés sur ' . count($conflicts) . ' occurrence(s)',
            'hint' => $hint,
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
    /**
     * Limite les SubscriptionRecurringSlot au club du cours (via subscription → club).
     */
    private function scopeRecurringSlotsToClub(Builder $query, ?int $clubId): Builder
    {
        if ($clubId === null) {
            return $query;
        }

        return $query->whereHas('subscriptionInstance.subscription', function ($q) use ($clubId) {
            if (Subscription::hasClubIdColumn()) {
                $q->where('club_id', $clubId);
            } else {
                $q->whereHas('template', function ($tq) use ($clubId) {
                    $tq->where('club_id', $clubId);
                });
            }
        });
    }

    /**
     * Quand la validation trouve un cours (Lesson) pour le prof et une récurrence pour l’élève (ou l’inverse)
     * pour le même couple demandé, c’est la même réservation (ex. cours généré depuis subscription_recurring_slots) :
     * on fusionne en un seul recurring_duplicate au lieu de « enseignant occupé » + « élève réservé ».
     *
     * @param  array<string, mixed>|null  $teacherConflict
     * @param  array<string, mixed>|null  $studentConflict
     * @return array<string, mixed>|null  payload sans clé date
     */
    private function mergeLessonConflictWithRecurringConflict(
        ?array $teacherConflict,
        ?array $studentConflict,
        int $teacherId,
        int $studentId
    ): ?array {
        if ($teacherConflict === null || $studentConflict === null) {
            return null;
        }

        $pairs = [
            [$teacherConflict, $studentConflict],
            [$studentConflict, $teacherConflict],
        ];

        foreach ($pairs as [$lessonSide, $recurringSide]) {
            $lessonId = $lessonSide['lesson_id'] ?? null;
            $recurringSlotId = $recurringSide['recurring_slot_id'] ?? null;
            if ($lessonId === null || $recurringSlotId === null) {
                continue;
            }

            $lTeach = $lessonSide['lesson_teacher_id'] ?? null;
            if ((int) $lTeach !== $teacherId) {
                continue;
            }

            // Même élève (colonne ou pivot), ou cours matérialisé sans aucun lien élève (legacy / génération incomplète)
            // — dans ce dernier cas on ne fusionne que si la récurrence côté autre branche correspond au couple demandé.
            $involvesStudent = $this->lessonInvolvesStudent((int) $lessonId, $studentId);
            $orphanNoStudentLinkage = $this->lessonHasNoStudentLinkage((int) $lessonId);
            if (! $involvesStudent && ! $orphanNoStudentLinkage) {
                continue;
            }

            $slot = SubscriptionRecurringSlot::query()->find((int) $recurringSlotId);
            if ($slot === null
                || (int) $slot->teacher_id !== $teacherId
                || (int) $slot->student_id !== $studentId) {
                continue;
            }

            return [
                'type' => 'recurring_duplicate',
                'message' => 'Créneau récurrent déjà enregistré pour cet élève et cet enseignant à cet horaire (cours déjà planifié pour cette date).',
                'recurring_slot_id' => (int) $recurringSlotId,
                'lesson_id' => (int) $lessonId,
                'slot_student_id' => (int) $slot->student_id,
                'slot_teacher_id' => (int) $slot->teacher_id,
                'subscription_instance_id' => (int) $slot->subscription_instance_id,
                'recurring_slot' => $this->recurringSlotDiagnostic($slot),
            ];
        }

        return null;
    }

    /**
     * L’élève est-il bien sur ce cours (colonne ou pivot) ?
     */
    private function lessonInvolvesStudent(int $lessonId, int $studentId): bool
    {
        $lesson = Lesson::query()->select(['id', 'student_id'])->find($lessonId);
        if ($lesson === null) {
            return false;
        }
        if ($lesson->student_id !== null) {
            return (int) $lesson->student_id === $studentId;
        }

        return $lesson->students()->where('students.id', $studentId)->exists();
    }

    /**
     * Cours sans student_id et sans ligne pivot lesson_student (données incomplètes ou ancien flux).
     */
    private function lessonHasNoStudentLinkage(int $lessonId): bool
    {
        $lesson = Lesson::query()->select(['id', 'student_id'])->find($lessonId);
        if ($lesson === null || $lesson->student_id !== null) {
            return false;
        }

        return ! $lesson->students()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function recurringSlotDiagnostic(SubscriptionRecurringSlot $slot): array
    {
        $slot->loadMissing(['teacher.user', 'student.user']);

        return [
            'id' => (int) $slot->id,
            'teacher_id' => (int) $slot->teacher_id,
            'student_id' => (int) $slot->student_id,
            'teacher_name' => $slot->teacher?->user?->name,
            'student_name' => $slot->student?->user?->name,
            'day_of_week' => (int) $slot->day_of_week,
            'start_time' => (string) $slot->start_time,
            'end_time' => (string) $slot->end_time,
            'recurring_interval_weeks' => max(1, (int) ($slot->recurring_interval ?? 1)),
            'start_date' => $slot->start_date?->format('Y-m-d'),
            'end_date' => $slot->end_date?->format('Y-m-d'),
            'status' => (string) $slot->status,
            'subscription_instance_id' => (int) $slot->subscription_instance_id,
        ];
    }

    /**
     * Détails d’un cours bloquant pour l’API / le frontend (fuseau app).
     *
     * @return array<string, mixed>
     */
    private function lessonDiagnostic(Lesson $lesson): array
    {
        $lesson->loadMissing(['teacher.user', 'student.user', 'students.user']);

        $studentName = $lesson->student?->user?->name;
        $primaryStudentId = $lesson->student_id !== null ? (int) $lesson->student_id : null;
        if ($studentName === null && $lesson->relationLoaded('students') && $lesson->students->isNotEmpty()) {
            $first = $lesson->students->first();
            $studentName = $first?->user?->name;
            if ($primaryStudentId === null && $first !== null) {
                $primaryStudentId = (int) $first->id;
            }
        }

        $tz = config('app.timezone');

        return [
            'id' => (int) $lesson->id,
            'start_time' => $lesson->start_time?->copy()->timezone($tz)->format('Y-m-d H:i'),
            'end_time' => $lesson->end_time?->copy()->timezone($tz)->format('Y-m-d H:i'),
            'teacher_id' => (int) $lesson->teacher_id,
            'student_id' => $primaryStudentId,
            'teacher_name' => $lesson->teacher?->user?->name,
            'student_name' => $studentName,
            'status' => (string) $lesson->status,
        ];
    }

    /**
     * Ajoute blocking_lesson / blocking_recurring_slot pour affichage et actions côté client.
     *
     * @param  array<string, mixed>  $conflict
     * @return array<string, mixed>
     */
    private function enrichConflictForApi(array $conflict): array
    {
        if (! empty($conflict['lesson_id'])) {
            $lesson = Lesson::query()->find((int) $conflict['lesson_id']);
            if ($lesson !== null) {
                $conflict['blocking_lesson'] = $this->lessonDiagnostic($lesson);
            }
        }

        if (! empty($conflict['recurring_slot']) && is_array($conflict['recurring_slot'])) {
            $conflict['blocking_recurring_slot'] = $conflict['recurring_slot'];
        } elseif (! empty($conflict['recurring_slot_id'])) {
            $slot = SubscriptionRecurringSlot::query()->find((int) $conflict['recurring_slot_id']);
            if ($slot !== null) {
                $conflict['blocking_recurring_slot'] = $this->recurringSlotDiagnostic($slot);
            }
        }

        return $conflict;
    }

    /**
     * Résout l’élève « principal » d’un cours pour les payloads (colonne ou pivot).
     */
    private function resolveLessonStudentIdForPayload(Lesson $lesson, ?int $preferStudentId = null): ?int
    {
        if ($lesson->student_id !== null) {
            return (int) $lesson->student_id;
        }
        $ids = $lesson->students()->pluck('students.id')->map(fn ($v) => (int) $v)->values()->all();
        if ($preferStudentId !== null && in_array($preferStudentId, $ids, true)) {
            return $preferStudentId;
        }
        if (count($ids) === 1) {
            return $ids[0];
        }

        return null;
    }

    /**
     * Enrichit la réponse API 422 (diagnostic : qui bloque réellement).
     *
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function conflictPayloadFromCheck(string $type, string $dateYmd, array $check): array
    {
        $base = [
            'type' => $type,
            'date' => $dateYmd,
            'message' => $check['message'],
            'lesson_id' => $check['lesson_id'] ?? null,
            'recurring_slot_id' => $check['recurring_slot_id'] ?? null,
        ];
        foreach (['slot_student_id', 'slot_teacher_id', 'lesson_teacher_id', 'lesson_student_id', 'subscription_instance_id'] as $key) {
            if (! array_key_exists($key, $check)) {
                continue;
            }
            if ($check[$key] === null) {
                continue;
            }
            $base[$key] = $check[$key];
        }

        return $base;
    }

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
     * @return array{message: string, lesson_id: ?int, recurring_slot_id: ?int}|null
     */
    private function checkStudentAvailability(
        int $studentId,
        Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeLessonId = null,
        int $weekIndex = 0,
        ?int $clubId = null
    ): ?array {
        [$startUtc, $endUtc] = $this->occurrenceUtcBounds($date, $startTime, $endTime);

        $lessonQuery = Lesson::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '<', $endUtc)
            ->where('end_time', '>', $startUtc)
            ->where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->orWhereHas('students', function ($sq) use ($studentId) {
                        $sq->where('students.id', $studentId);
                    });
            });

        if ($clubId !== null) {
            $lessonQuery->where('club_id', $clubId);
        }

        if ($excludeLessonId) {
            $lessonQuery->where('id', '!=', $excludeLessonId);
        }

        $conflictLesson = $lessonQuery->first([
            'id', 'start_time', 'end_time', 'student_id', 'teacher_id', 'club_id', 'status', 'course_type_id',
        ]);

        if ($conflictLesson) {
            $resolvedLessonStudentId = $this->resolveLessonStudentIdForPayload($conflictLesson, $studentId);
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
                    'lesson_student_id_resolved' => $resolvedLessonStudentId,
                    'club_id' => $conflictLesson->club_id,
                    'status' => $conflictLesson->status,
                    'course_type_id' => $conflictLesson->course_type_id,
                ],
            ]);

            return [
                'message' => 'Élève déjà occupé (cours)',
                'lesson_id' => (int) $conflictLesson->id,
                'recurring_slot_id' => null,
                'lesson_teacher_id' => (int) $conflictLesson->teacher_id,
                'lesson_student_id' => $resolvedLessonStudentId,
            ];
        }

        $recurringCandidatesQuery = SubscriptionRecurringSlot::activeOnDate($date)
            ->where('student_id', $studentId)
            ->byDayOfWeek($date->dayOfWeek)
            ->lessonLikeTimeWindow()
            ->byTimeRange($startTime, $endTime);

        $this->scopeRecurringSlotsToClub($recurringCandidatesQuery, $clubId);

        $recurringCandidates = $recurringCandidatesQuery->get();

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

                return [
                    'message' => 'Élève déjà réservé (récurrence)',
                    'lesson_id' => null,
                    'recurring_slot_id' => (int) $slot->id,
                    'slot_student_id' => (int) $slot->student_id,
                    'slot_teacher_id' => (int) $slot->teacher_id,
                    'subscription_instance_id' => (int) $slot->subscription_instance_id,
                ];
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
     * @return array{message: string, lesson_id: ?int, recurring_slot_id: ?int}|null
     */
    private function checkTeacherAvailability(
        int $teacherId,
        Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeLessonId = null,
        int $weekIndex = 0,
        ?int $clubId = null,
        ?int $contextStudentIdForLesson = null
    ): ?array {
        [$startUtc, $endUtc] = $this->occurrenceUtcBounds($date, $startTime, $endTime);

        // Vérifier les cours existants (chevauchement d'intervalles réels en UTC, pas whereDate/whereTime)
        $lessonQuery = Lesson::where('teacher_id', $teacherId)
            ->where('start_time', '<', $endUtc)
            ->where('end_time', '>', $startUtc)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($clubId !== null) {
            $lessonQuery->where('club_id', $clubId);
        }

        if ($excludeLessonId) {
            $lessonQuery->where('id', '!=', $excludeLessonId);
        }

        $conflictLesson = $lessonQuery->first([
            'id', 'start_time', 'end_time', 'student_id', 'teacher_id', 'club_id', 'status', 'course_type_id',
        ]);

        if ($conflictLesson) {
            $resolvedLessonStudentId = $this->resolveLessonStudentIdForPayload($conflictLesson, $contextStudentIdForLesson);
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
                    'lesson_student_id_resolved' => $resolvedLessonStudentId,
                    'club_id' => $conflictLesson->club_id,
                    'status' => $conflictLesson->status,
                    'course_type_id' => $conflictLesson->course_type_id,
                ],
            ]);

            return [
                'message' => 'Enseignant déjà occupé',
                'lesson_id' => (int) $conflictLesson->id,
                'recurring_slot_id' => null,
                'lesson_teacher_id' => (int) $conflictLesson->teacher_id,
                'lesson_student_id' => $resolvedLessonStudentId,
            ];
        }

        // Vérifier les récurrences actives à cette date d'occurrence
        $teacherRecurringQuery = SubscriptionRecurringSlot::activeOnDate($date)
            ->byTeacher($teacherId)
            ->byDayOfWeek($date->dayOfWeek)
            ->lessonLikeTimeWindow()
            ->byTimeRange($startTime, $endTime);

        $this->scopeRecurringSlotsToClub($teacherRecurringQuery, $clubId);

        $teacherRecurringCandidates = $teacherRecurringQuery->get();

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

                return [
                    'message' => 'Enseignant déjà réservé (récurrence)',
                    'lesson_id' => null,
                    'recurring_slot_id' => (int) $slot->id,
                    'slot_student_id' => (int) $slot->student_id,
                    'slot_teacher_id' => (int) $slot->teacher_id,
                    'subscription_instance_id' => (int) $slot->subscription_instance_id,
                ];
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

