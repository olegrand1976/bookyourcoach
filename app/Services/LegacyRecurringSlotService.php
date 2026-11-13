<?php

namespace App\Services;

use App\Models\ClubClosureDay;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyRecurringSlotService
{
    /**
     * Ne crée jamais un cours :
     * - pour une série non active (annulée, libérée…) ;
     * - à une date sautée (skipped_dates) ou déjà occupée par un cours de la série ;
     * - en conflit : élève ou enseignant déjà pris, créneau du club complet (max_slots).
     * Les conflits sont renvoyés dans `conflicts` (type, date, message) au lieu de bloquer la série.
     *
     * @param  int  $reserveAttachmentSlots  Places à réserver (ex. cours déclencheur pas encore attaché)
     * @return array{generated: int, skipped: int, errors: int, conflicts: list<array<string, mixed>>}
     */
    public function generateLessonsForSlot(
        SubscriptionRecurringSlot $recurringSlot,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $reserveAttachmentSlots = 0,
        ?Carbon $notBefore = null
    ): array {
        $t0 = microtime(true);

        if ($recurringSlot->status !== 'active') {
            return ['generated' => 0, 'skipped' => 0, 'errors' => 0, 'conflicts' => []];
        }

        $recurringEndDate = Carbon::parse($recurringSlot->end_date);
        $recurringStartDate = Carbon::parse($recurringSlot->start_date);

        $lastLesson = $this->findLastLessonForRecurringSlot($recurringSlot);

        if ($lastLesson) {
            $recurringInterval = $recurringSlot->recurring_interval ?? 1;
            $defaultStartDate = Carbon::parse($lastLesson->start_time)->addWeeks($recurringInterval);
        } else {
            $defaultStartDate = $recurringStartDate->copy();
        }

        if ($defaultStartDate->isBefore($recurringStartDate)) {
            $defaultStartDate = $recurringStartDate->copy();
        }

        $startDate = $startDate ?? $defaultStartDate;
        // Planification de l'avenir : ne jamais recréer de cours dans le passé (semaines manquées).
        if ($notBefore !== null && $startDate->lt($notBefore)) {
            $startDate = $notBefore->copy();
        }
        $endDate = $endDate ?? $recurringEndDate->copy();

        if ($endDate->isAfter($recurringEndDate)) {
            $endDate = $recurringEndDate->copy();
        }

        $stats = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'conflicts' => [],
        ];

        $recurringSlot->load(['subscriptionInstance', 'student', 'teacher']);

        $subscriptionInstance = $recurringSlot->subscriptionInstance;
        $isSubscriptionActive = $subscriptionInstance && $subscriptionInstance->status === 'active';

        if ($recurringStartDate->isAfter($startDate)) {
            $startDate = $recurringStartDate->copy();
        }
        if ($recurringEndDate->isBefore($endDate)) {
            $endDate = $recurringEndDate->copy();
        }

        $dates = $this->generateDatesForRecurringSlot(
            $recurringSlot,
            $startDate,
            $endDate,
            null,
            null
        );

        $templateLesson = $lastLesson
            ?? Lesson::where('student_id', $recurringSlot->student_id)
                ->where('teacher_id', $recurringSlot->teacher_id)
                ->orderBy('start_time', 'desc')
                ->first();

        if (! $templateLesson) {
            Log::warning("Aucun cours précédent trouvé pour créneau récurrent #{$recurringSlot->id}");

            return $stats;
        }

        // Une série hérite du régime de son cours source : une séance libre reste libre.
        // Sans ce report, les cours générés reprennent le défaut `true` de la colonne et
        // sont rattachés à l'abonnement alors que le cours d'origine ne l'est pas.
        $deductFromSubscription = $templateLesson->deduct_from_subscription ?? true;

        $clubId = (int) $templateLesson->club_id;
        $originalInstanceId = $subscriptionInstance?->id;
        $usedInstanceIds = array_filter([$originalInstanceId]);
        $rolledOver = false;

        // Carnet de la série expiré/terminé : l'élève a pu en prendre un nouveau.
        if ($deductFromSubscription && ! $isSubscriptionActive && $dates !== []) {
            $next = $this->findRolloverInstance($recurringSlot, $templateLesson, $dates[0], $usedInstanceIds);
            if ($next) {
                $subscriptionInstance = $next;
                $isSubscriptionActive = true;
                $usedInstanceIds[] = $next->id;
                $rolledOver = true;
            }
        }

        // Aucun carnet pour couvrir ces cours : les occurrences restent une prévisualisation.
        // (Une série est ouverte sans fin hors « dernier abonnement » : générer ici créerait
        // indéfiniment des cours non rattachés.)
        if ($deductFromSubscription && ! $isSubscriptionActive) {
            $recurringSlot->forceFill(['last_generated_at' => now()])->save();

            return $stats;
        }

        $closureSet = $this->prefetchClosureDates($clubId, $dates);
        $existingStartKeys = $this->prefetchExistingLessonStartKeys($recurringSlot, $dates);
        $occupancy = $this->prefetchOccupancy($clubId, $recurringSlot, $dates);
        $openSlots = ClubOpenSlot::query()
            ->where('club_id', $clubId)
            ->orderBy('id')
            ->get(['id', 'club_id', 'day_of_week', 'start_time', 'end_time', 'max_slots']);
        /** @var list<Lesson> $createdLessons */
        $createdLessons = [];

        /** @var array<int, array{instance: SubscriptionInstance, lessons: list<Lesson>}> $batches */
        $batches = [];

        try {
            DB::transaction(function () use (
                $dates,
                &$stats,
                &$batches,
                &$subscriptionInstance,
                &$usedInstanceIds,
                &$rolledOver,
                $existingStartKeys,
                $closureSet,
                &$occupancy,
                $openSlots,
                &$createdLessons,
                $recurringSlot,
                $templateLesson,
                $isSubscriptionActive,
                $deductFromSubscription,
                $originalInstanceId,
                $reserveAttachmentSlots
            ) {
                $keys = $existingStartKeys;
                $attaching = $deductFromSubscription && $isSubscriptionActive && $subscriptionInstance;

                foreach ($dates as $date) {
                    if ($attaching && ! $this->ensureSubscriptionInstanceHasSlotForDate(
                        $recurringSlot,
                        $templateLesson,
                        $date,
                        $subscriptionInstance,
                        $usedInstanceIds,
                        $originalInstanceId,
                        $reserveAttachmentSlots,
                        $rolledOver,
                        $batches
                    )) {
                        break;
                    }

                    $startTime = Carbon::parse($date->format('Y-m-d').' '.$recurringSlot->start_time);
                    $endTime = Carbon::parse($date->format('Y-m-d').' '.$recurringSlot->end_time);
                    $startKey = $startTime->format('Y-m-d H:i:s');
                    $closureYmd = LessonCalendarDate::toYmd($startTime);

                    if (isset($keys[$startKey])) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($closureYmd !== null && isset($closureSet[$closureYmd])) {
                        $stats['skipped']++;

                        continue;
                    }

                    // Occurrence sautée par le club (suppression d'un seul cours, carte blanche…).
                    if ($recurringSlot->isOccurrenceSkipped($startTime->format('Y-m-d'))) {
                        $stats['skipped']++;

                        continue;
                    }

                    $conflict = $this->detectOccurrenceConflict($recurringSlot, $startTime, $endTime, $occupancy, $openSlots);
                    if ($conflict !== null) {
                        $stats['skipped']++;
                        $stats['conflicts'][] = $conflict;
                        Log::warning('LegacyRecurringSlotService: occurrence non générée (conflit)', $conflict);

                        continue;
                    }

                    $lesson = Lesson::create([
                        'club_id' => $templateLesson->club_id,
                        'teacher_id' => $recurringSlot->teacher_id,
                        'student_id' => $recurringSlot->student_id,
                        'course_type_id' => $templateLesson->course_type_id,
                        'location_id' => $templateLesson->location_id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'confirmed',
                        'price' => $templateLesson->price,
                        'deduct_from_subscription' => $deductFromSubscription,
                        'notes' => 'Cours généré automatiquement depuis créneau récurrent',
                    ]);

                    if ($attaching) {
                        $batches[$subscriptionInstance->id] ??= ['instance' => $subscriptionInstance, 'lessons' => []];
                        $batches[$subscriptionInstance->id]['lessons'][] = $lesson;
                    }
                    $keys[$startKey] = true;
                    $occupancy[] = [
                        'club_id' => (int) $lesson->club_id,
                        'teacher_id' => (int) $lesson->teacher_id,
                        'student_id' => (int) $lesson->student_id,
                        'start' => $startTime->copy(),
                        'end' => $endTime->copy(),
                    ];
                    $createdLessons[] = $lesson;
                    $stats['generated']++;
                }

                foreach ($batches as $batch) {
                    $this->attachGeneratedLessonsBatch($batch['instance'], $batch['lessons']);
                }

                foreach ($createdLessons as $created) {
                    $this->logGeneratedLesson($created, $recurringSlot);
                }

                if ($attaching && $subscriptionInstance->id !== $originalInstanceId && isset($batches[$subscriptionInstance->id])) {
                    $this->switchRecurringSlotInstance($recurringSlot, $subscriptionInstance);
                }
            });
        } catch (\Exception $e) {
            $stats['errors']++;
            $stats['generated'] = 0;
            $stats['conflicts'] = [];
            Log::error("Erreur transaction génération créneau récurrent #{$recurringSlot->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $recurringSlot->forceFill(['last_generated_at' => now()])->save();

        Log::info('[perf] LegacyRecurringSlotService::generateLessonsForSlot', [
            'recurring_slot_id' => $recurringSlot->id,
            'dates' => count($dates),
            'generated' => $stats['generated'],
            'skipped' => $stats['skipped'],
            'conflicts' => count($stats['conflicts']),
            'errors' => $stats['errors'],
            'reserve_slots' => $reserveAttachmentSlots,
            'ms' => (int) round((microtime(true) - $t0) * 1000),
        ]);

        return $stats;
    }

    /**
     * @param  list<Carbon>  $dates
     * @return array<string, true>
     */
    private function prefetchClosureDates(int $clubId, array $dates): array
    {
        if ($clubId <= 0 || $dates === []) {
            return [];
        }

        $ymds = [];
        foreach ($dates as $date) {
            $ymd = LessonCalendarDate::toYmd($date);
            if ($ymd) {
                $ymds[$ymd] = true;
            }
        }
        if ($ymds === []) {
            return [];
        }

        $keys = array_keys($ymds);
        sort($keys);

        return ClubClosureDay::query()
            ->where('club_id', $clubId)
            ->whereDate('closed_on', '>=', $keys[0])
            ->whereDate('closed_on', '<=', $keys[array_key_last($keys)])
            ->get(['closed_on'])
            ->mapWithKeys(function (ClubClosureDay $row) {
                $ymd = $row->closed_on instanceof Carbon
                    ? $row->closed_on->format('Y-m-d')
                    : substr((string) $row->closed_on, 0, 10);

                return [$ymd => true];
            })
            ->all();
    }

    /**
     * @param  list<Carbon>  $dates
     * @return array<string, true>
     */
    private function prefetchExistingLessonStartKeys(SubscriptionRecurringSlot $recurringSlot, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $min = $dates[0]->copy()->startOfDay();
        $max = $dates[array_key_last($dates)]->copy()->endOfDay();

        // Cours supprimés ignorés : le changement d'intervalle archive puis régénère la série.
        // Une suppression club est protégée par skipped_dates / la troncature de la série.
        return Lesson::query()
            ->where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', '>=', $min)
            ->where('start_time', '<=', $max)
            ->pluck('start_time')
            ->mapWithKeys(function ($start) {
                $key = $start instanceof Carbon
                    ? $start->format('Y-m-d H:i:s')
                    : (string) $start;

                return [$key => true];
            })
            ->all();
    }

    /**
     * Cours actifs qui peuvent entrer en conflit avec les occurrences : ceux du club (capacité),
     * de l'enseignant et de l'élève (quel que soit le club).
     *
     * @param  list<Carbon>  $dates
     * @return list<array{club_id: int, teacher_id: int, student_id: int, start: Carbon, end: Carbon}>
     */
    private function prefetchOccupancy(int $clubId, SubscriptionRecurringSlot $recurringSlot, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $min = $dates[0]->copy()->startOfDay();
        $max = $dates[array_key_last($dates)]->copy()->endOfDay();

        return Lesson::query()
            ->where('start_time', '>=', $min)
            ->where('start_time', '<=', $max)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) use ($clubId, $recurringSlot) {
                $q->where('club_id', $clubId)
                    ->orWhere('teacher_id', $recurringSlot->teacher_id)
                    ->orWhere('student_id', $recurringSlot->student_id);
            })
            ->get(['id', 'club_id', 'teacher_id', 'student_id', 'start_time', 'end_time'])
            ->map(function (Lesson $l) {
                $start = Carbon::parse($l->start_time);

                return [
                    'club_id' => (int) $l->club_id,
                    'teacher_id' => (int) $l->teacher_id,
                    'student_id' => (int) $l->student_id,
                    'start' => $start,
                    'end' => $l->end_time ? Carbon::parse($l->end_time) : $start->copy()->addMinutes(20),
                ];
            })
            ->all();
    }

    /**
     * @param  list<array{club_id: int, teacher_id: int, student_id: int, start: Carbon, end: Carbon}>  $occupancy
     * @param  \Illuminate\Support\Collection<int, ClubOpenSlot>  $openSlots
     * @return array{type: string, date: string, time: string, message: string, recurring_slot_id: int, student_id: int, teacher_id: int}|null
     */
    private function detectOccurrenceConflict(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $start,
        Carbon $end,
        array $occupancy,
        $openSlots
    ): ?array {
        $overlapping = array_values(array_filter(
            $occupancy,
            static fn (array $o) => $o['start']->lt($end) && $o['end']->gt($start)
        ));

        $conflict = static fn (string $type, string $message) => [
            'type' => $type,
            'date' => $start->format('Y-m-d'),
            'time' => $start->format('H:i'),
            'message' => $message,
            'recurring_slot_id' => (int) $recurringSlot->id,
            'student_id' => (int) $recurringSlot->student_id,
            'teacher_id' => (int) $recurringSlot->teacher_id,
        ];

        foreach ($overlapping as $o) {
            if ($o['student_id'] === (int) $recurringSlot->student_id) {
                return $conflict('student_busy', "L'élève a déjà un cours à cette heure.");
            }
        }

        foreach ($overlapping as $o) {
            if ($o['teacher_id'] === (int) $recurringSlot->teacher_id) {
                return $conflict('teacher_busy', "L'enseignant a déjà un cours à cette heure.");
            }
        }

        $time = $start->format('H:i:s');
        $openSlot = $recurringSlot->open_slot_id
            ? $openSlots->firstWhere('id', (int) $recurringSlot->open_slot_id)
            : null;
        $openSlot ??= $openSlots->first(function (ClubOpenSlot $os) use ($start, $time) {
            $osStart = strlen((string) $os->start_time) === 5 ? $os->start_time.':00' : (string) $os->start_time;
            $osEnd = strlen((string) $os->end_time) === 5 ? $os->end_time.':00' : (string) $os->end_time;

            return (int) $os->day_of_week === (int) $start->dayOfWeek
                && $osStart <= $time
                && $osEnd > $time;
        });

        // max_slots = cours simultanés (même règle que la création manuelle). On compte le pic de
        // cours en même temps sur la durée du nouveau cours, pas tous les cours qui la touchent.
        if ($openSlot && (int) $openSlot->max_slots > 0) {
            $clubId = (int) $openSlot->club_id;
            $inClub = array_values(array_filter($overlapping, static fn (array $o) => $o['club_id'] === $clubId));
            $points = [$start];
            foreach ($inClub as $o) {
                if ($o['start']->gt($start)) {
                    $points[] = $o['start'];
                }
            }
            $peak = 0;
            foreach ($points as $p) {
                $peak = max($peak, count(array_filter($inClub, static fn (array $o) => $o['start']->lte($p) && $o['end']->gt($p))));
            }
            if ($peak >= (int) $openSlot->max_slots) {
                return $conflict(
                    'capacity_full',
                    'Créneau complet ('.$peak.'/'.(int) $openSlot->max_slots.' cours simultanés).'
                );
            }
        }

        return null;
    }

    /**
     * Trace le cours généré dans l'historique (sans bloquer la génération en cas d'échec).
     */
    private function logGeneratedLesson(Lesson $lesson, SubscriptionRecurringSlot $recurringSlot): void
    {
        try {
            app(LessonActionLogService::class)->log(
                $lesson,
                LessonActionLog::ACTION_GENERATED,
                null,
                'system',
                meta: ['recurring_slot_id' => (int) $recurringSlot->id, 'source' => 'recurring_slot'],
            );
        } catch (\Throwable $e) {
            Log::warning('LegacyRecurringSlotService: trace de génération impossible', [
                'lesson_id' => $lesson->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Attach batch + un seul recalcul (évite consumeLesson × N).
     *
     * @param  list<Lesson>  $lessons
     */
    private function attachGeneratedLessonsBatch(SubscriptionInstance $subscriptionInstance, array $lessons): void
    {
        $ids = array_map(static fn (Lesson $l) => (int) $l->id, $lessons);
        $existing = $subscriptionInstance->lessons()
            ->whereIn('lessons.id', $ids)
            ->pluck('lessons.id')
            ->map(static fn ($id) => (int) $id)
            ->all();
        $existingSet = array_fill_keys($existing, true);
        $toAttach = [];
        foreach ($ids as $id) {
            if (! isset($existingSet[$id])) {
                $toAttach[] = $id;
            }
        }

        if ($toAttach !== []) {
            $subscriptionInstance->lessons()->attach($toAttach);
        }

        if ($subscriptionInstance->est_legacy !== null) {
            Lesson::query()
                ->whereIn('id', $ids)
                ->update(['est_legacy' => $subscriptionInstance->est_legacy]);
        }

        $subscriptionInstance->recalculateLessonsUsed();
        $subscriptionInstance->checkAndUpdateStatus();
    }

    /**
     * Dernière lesson pour ce créneau récurrent (même student, teacher, jour de semaine, horaire).
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
            $dayOfWeekSql = ($recurringSlot->day_of_week % 7) + 1;
            $query->whereRaw('DAYOFWEEK(start_time) = ?', [$dayOfWeekSql])
                ->whereRaw('TIME(start_time) = ?', [$timeStr]);
        } else {
            $query->whereRaw("strftime('%w', start_time) = ?", [(string) ($recurringSlot->day_of_week % 7)])
                ->whereRaw("strftime('%H:%M:%S', start_time) = ?", [substr($timeStr, 0, 8)]);
        }

        return $query->first();
    }

    /**
     * @return list<Carbon>
     */
    private function generateDatesForRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $startDate,
        Carbon $endDate,
        ?Carbon $subscriptionStartedAt = null,
        ?Carbon $subscriptionExpiresAt = null
    ): array {
        $dates = [];
        $recurringInterval = $recurringSlot->recurring_interval ?? 1;
        $currentDate = $startDate->copy();

        while ($currentDate->dayOfWeek != $recurringSlot->day_of_week) {
            $currentDate->addDay();
        }

        $recurringStartDate = Carbon::parse($recurringSlot->start_date);
        if ($currentDate->isBefore($recurringStartDate)) {
            $currentDate = $recurringStartDate->copy();
            while ($currentDate->dayOfWeek != $recurringSlot->day_of_week) {
                $currentDate->addDay();
            }
        }

        $recurringEndDate = Carbon::parse($recurringSlot->end_date);

        while ($currentDate->lte($endDate) && $currentDate->lte($recurringEndDate)) {
            if ($currentDate->isBefore($recurringStartDate)) {
                $currentDate->addWeeks($recurringInterval);

                continue;
            }

            $dates[] = $currentDate->copy();
            $currentDate->addWeeks($recurringInterval);
        }

        return $dates;
    }

    /**
     * Crée une lesson depuis un créneau récurrent legacy (chemin materialize unitaire).
     */
    private function createLessonFromRecurringSlot(
        SubscriptionRecurringSlot $recurringSlot,
        Carbon $date,
        ?SubscriptionInstance $subscriptionInstance = null,
        ?Lesson $templateLesson = null
    ): ?Lesson {
        $startTime = Carbon::parse($date->format('Y-m-d').' '.$recurringSlot->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d').' '.$recurringSlot->end_time);

        $existingLesson = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', $startTime)
            ->first();

        if ($existingLesson) {
            return null;
        }

        $lastLesson = $templateLesson
            ?? $this->findLastLessonForRecurringSlot($recurringSlot)
            ?? Lesson::where('student_id', $recurringSlot->student_id)
                ->where('teacher_id', $recurringSlot->teacher_id)
                ->orderBy('start_time', 'desc')
                ->first();

        if (! $lastLesson) {
            Log::warning("Aucun cours précédent trouvé pour créneau récurrent #{$recurringSlot->id}");

            return null;
        }

        $closureDateYmd = LessonCalendarDate::toYmd($startTime);
        if (ClubClosureDay::clubIsClosedOn((int) $lastLesson->club_id, $closureDateYmd ?? '')) {
            return null;
        }

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
            'deduct_from_subscription' => $lastLesson->deduct_from_subscription ?? true,
            'notes' => 'Cours généré automatiquement depuis créneau récurrent',
        ]);

        if ($subscriptionInstance && $lesson->deduct_from_subscription !== false) {
            if (! $this->attachLessonToSubscriptionInstance($subscriptionInstance, $lesson)) {
                $lesson->delete();

                return null;
            }
        }

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
            'conflicts' => [],
        ];

        $recurringSlots = SubscriptionRecurringSlot::where('status', 'active')
            ->whereNotNull('student_id')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        foreach ($recurringSlots as $slot) {
            try {
                $stats = $this->generateLessonsForSlot($slot, $startDate, $endDate);
                $totalStats['generated'] += $stats['generated'];
                $totalStats['skipped'] += $stats['skipped'];
                $totalStats['errors'] += $stats['errors'];
                array_push($totalStats['conflicts'], ...($stats['conflicts'] ?? []));
            } catch (\Exception $e) {
                $totalStats['errors']++;
                Log::error("Erreur lors de la génération pour créneau #{$slot->id}: ".$e->getMessage());
            }
        }

        return $totalStats;
    }

    /**
     * Crée (ou retourne) la lesson pour une occurrence précise d’un SubscriptionRecurringSlot (planning club).
     *
     * Un cours annulé à cette place est réactivé (historique et rattachement abonnement conservés)
     * au lieu d'être renvoyé comme « déjà présent ».
     *
     * @return array{success: bool, lesson: ?Lesson, already_existed: bool, reactivated?: bool, message: ?string, conflicts?: array, requested_recurring_slot_id?: int, resolved_recurring_slot_id?: int, suggested_recurring_slot_id?: int}
     */
    public function materializeLessonForSingleDate(SubscriptionRecurringSlot $recurringSlot, Carbon $occurrenceDate, ?User $actor = null): array
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
            $successor = $this->findSuccessorRecurringSlotForDate($recurringSlot, $occurrenceDate, $validator);
            if ($successor) {
                $requestedId = (int) $recurringSlot->id;
                $result = $this->materializeLessonForSingleDate($successor, $occurrenceDate, $actor);
                if ($result['success'] ?? false) {
                    $result['requested_recurring_slot_id'] = $requestedId;
                    $result['resolved_recurring_slot_id'] = (int) $successor->id;
                    if (! ($result['already_existed'] ?? false) && ! ($result['reactivated'] ?? false)) {
                        $result['message'] = "Cours généré pour cette séance (série n°{$successor->id}, suite de la série n°{$requestedId}).";
                    }
                }

                return $result;
            }

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

        $clubId = (int) ($recurringSlot->subscriptionInstance?->subscription?->club_id ?? 0);

        $existingLessons = Lesson::where('student_id', $recurringSlot->student_id)
            ->where('teacher_id', $recurringSlot->teacher_id)
            ->where('start_time', $startTime)
            // lessons.club_id est nullable : des cours anciens n'en portent pas.
            // Les exclure de cette recherche créerait un doublon sur le même créneau,
            // alors que le triplet élève + enseignant + horaire exact les identifie déjà.
            ->when($clubId > 0, fn ($q) => $q->where(
                fn ($w) => $w->where('club_id', $clubId)->orWhereNull('club_id')
            ))
            ->get();

        $activeLesson = $existingLessons->first(fn (Lesson $l) => $l->status !== 'cancelled');
        if ($activeLesson) {
            return [
                'success' => true,
                'lesson' => $activeLesson,
                'already_existed' => true,
                'message' => null,
            ];
        }

        if ($clubId > 0 && ClubClosureDay::clubIsClosedOn($clubId, LessonCalendarDate::toYmd($dayStart) ?? '')) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Impossible de créer un cours un jour de fermeture du club.',
            ];
        }

        $cancelledLesson = $existingLessons->first();
        if ($cancelledLesson && $actor) {
            $result = app(LessonReactivationService::class)->reactivate($cancelledLesson, $actor, [
                'reactivate_scope' => 'single',
                'restore_recurring_slot' => false,
                'reattach_subscription' => ! (bool) $cancelledLesson->cancellation_count_in_subscription,
                'reason' => 'Réactivé depuis le planning (cours prévu de la série)',
            ]);

            if (! ($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'lesson' => null,
                    'already_existed' => false,
                    'message' => $result['message'] ?? 'Réactivation impossible.',
                    'conflicts' => $result['conflicts'] ?? [],
                ];
            }

            return [
                'success' => true,
                'lesson' => $cancelledLesson->fresh(),
                'already_existed' => false,
                'reactivated' => true,
                'message' => null,
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
        $rollover = null;
        $seriesInstanceFresh = $subscriptionInstance?->fresh();

        if (($reference->deduct_from_subscription ?? true)
            && ! ($isSubscriptionActive
                && $seriesInstanceFresh
                && $seriesInstanceFresh->resolveRemainingAttachmentSlotsForPlanning() > 0)) {
            // Carnet de la série épuisé (réservations futures comprises) ou inactif : basculer sur un
            // autre carnet actif de l'élève ayant une place à cette date.
            $rollover = $this->findRolloverInstance(
                $recurringSlot,
                $reference,
                $dayStart,
                array_filter([$subscriptionInstance?->id])
            );

            if ($rollover) {
                $subscriptionInstance = $rollover;
                $isSubscriptionActive = true;
            } elseif ($isSubscriptionActive) {
                $number = $subscriptionInstance->subscription?->subscription_number;

                return [
                    'success' => false,
                    'lesson' => null,
                    'already_existed' => false,
                    'message' => 'Aucune place restante sur l\'abonnement'.($number ? " {$number}" : '')
                        .' et aucun autre abonnement actif de l\'élève ne couvre ce cours.',
                ];
            }
        }

        $lesson = $this->createLessonFromRecurringSlot(
            $recurringSlot,
            $dayStart,
            $isSubscriptionActive ? $subscriptionInstance : null,
            $reference
        );

        if (! $lesson) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Impossible de générer le cours (conflit ou contrainte). Réessayez ou créez le cours manuellement.',
            ];
        }

        if ($rollover) {
            $this->switchRecurringSlotInstance($recurringSlot, $rollover);
        }

        $recurringSlot->forceFill(['last_generated_at' => now()])->save();

        return [
            'success' => true,
            'lesson' => $lesson,
            'already_existed' => false,
            'message' => null,
        ];
    }

    /**
     * @param  list<int>  $usedInstanceIds
     */
    private function ensureSubscriptionInstanceHasSlotForDate(
        SubscriptionRecurringSlot $recurringSlot,
        Lesson $templateLesson,
        Carbon $date,
        ?SubscriptionInstance &$subscriptionInstance,
        array &$usedInstanceIds,
        ?int $originalInstanceId,
        int $reserveAttachmentSlots,
        bool &$rolledOver,
        array $pendingBatches = []
    ): bool {
        if (! $subscriptionInstance) {
            return false;
        }

        $reserve = (int) $subscriptionInstance->id === (int) $originalInstanceId
            ? max(0, $reserveAttachmentSlots)
            : 0;

        while ($this->subscriptionInstanceExpiredBefore($subscriptionInstance, $date)
            || $this->effectiveAttachmentSlotsAvailableAt(
                $subscriptionInstance,
                $date,
                $reserve,
                $pendingBatches
            ) <= 0) {
            $excludeIds = array_values(array_unique([
                ...$usedInstanceIds,
                (int) $subscriptionInstance->id,
            ]));
            $next = $this->findRolloverInstance(
                $recurringSlot,
                $templateLesson,
                $date,
                $excludeIds
            );
            if (! $next) {
                return false;
            }

            $subscriptionInstance = $next;
            $usedInstanceIds[] = $next->id;
            $rolledOver = true;
            $reserve = 0;
        }

        return true;
    }

    private function subscriptionInstanceExpiredBefore(SubscriptionInstance $instance, Carbon $date): bool
    {
        if (! $instance->expires_at) {
            return false;
        }

        return Carbon::parse($instance->expires_at)->endOfDay()->lt($date);
    }

    private function attachmentSlotsAvailableAt(
        SubscriptionInstance $instance,
        Carbon $date,
        int $reserveAttachmentSlots = 0
    ): int {
        // Places du carnet réservations futures comprises : la génération ne surbooke pas un carnet
        // ($date ne sert qu'aux carnets de repli, choisis « à la date »). Capacité indéterminée
        // (legacy sans template) = illimitée : sans ce repli, aucune série legacy ne générait plus
        // de cours (régression du 12/10).
        $slots = $instance->fresh()->resolveRemainingAttachmentSlotsForPlanning();
        if ($slots === PHP_INT_MAX) {
            return $slots;
        }

        return max(0, $slots - max(0, $reserveAttachmentSlots));
    }

    /**
     * @param  array<int, array{instance: SubscriptionInstance, lessons: list<Lesson>}>  $pendingBatches
     */
    private function effectiveAttachmentSlotsAvailableAt(
        SubscriptionInstance $instance,
        Carbon $date,
        int $reserveAttachmentSlots,
        array $pendingBatches
    ): int {
        $available = $this->attachmentSlotsAvailableAt($instance, $date, $reserveAttachmentSlots);
        $pending = $this->pendingBatchAttachmentSlots($pendingBatches, (int) $instance->id, $date);

        return max(0, $available - $pending);
    }

    /**
     * Cours générés dans la transaction mais pas encore attachés au carnet.
     *
     * @param  array<int, array{instance: SubscriptionInstance, lessons: list<Lesson>}>  $pendingBatches
     */
    private function pendingBatchAttachmentSlots(array $pendingBatches, int $instanceId, Carbon $date): int
    {
        if (! isset($pendingBatches[$instanceId])) {
            return 0;
        }

        $pending = 0;
        foreach ($pendingBatches[$instanceId]['lessons'] as $lesson) {
            if (Carbon::parse($lesson->start_time)->lte($date)) {
                $pending++;
            }
        }

        return $pending;
    }

    private function attachLessonToSubscriptionInstance(SubscriptionInstance $instance, Lesson $lesson): bool
    {
        try {
            // Capacité indéterminée (legacy) : rattacher sans plafond, comme avant le 12/10.
            $instance->consumeLesson($lesson->fresh(), ! $instance->hasDeterminedCapacity());

            return true;
        } catch (\Exception $e) {
            Log::warning('Rattachement abonnement impossible', [
                'lesson_id' => $lesson->id,
                'subscription_instance_id' => $instance->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Autre carnet actif de l'élève, valide à la date du cours, couvrant le type de cours
     * et ayant encore une place à cette date (décompte borné comme {@see consumeLesson}).
     *
     * @param  list<int>  $excludeInstanceIds
     */
    private function findRolloverInstance(
        SubscriptionRecurringSlot $recurringSlot,
        Lesson $templateLesson,
        Carbon $date,
        array $excludeInstanceIds
    ): ?SubscriptionInstance {
        $courseTypeId = $this->resolveCourseTypeIdForRecurringMaterialization($recurringSlot, $templateLesson);
        if ($courseTypeId <= 0 || ! $recurringSlot->student_id) {
            return null;
        }

        // lessons.club_id est nullable : sans club, la recherche porterait sur les carnets de tous les clubs.
        $clubId = (int) ($templateLesson->club_id ?: $recurringSlot->subscriptionInstance?->subscription?->club_id);
        if ($clubId <= 0) {
            return null;
        }

        return SubscriptionInstance::findActiveSubscriptionForLesson(
            (int) $recurringSlot->student_id,
            $courseTypeId,
            $clubId,
            $date,
            false,
            $excludeInstanceIds,
            true
        );
    }

    private function resolveCourseTypeIdForRecurringMaterialization(
        SubscriptionRecurringSlot $recurringSlot,
        Lesson $templateLesson
    ): int {
        if ($templateLesson->course_type_id) {
            return (int) $templateLesson->course_type_id;
        }

        $recurringSlot->loadMissing('subscriptionInstance.subscription.template.courseTypes');
        $fromTemplate = $recurringSlot->subscriptionInstance?->subscription?->template?->courseTypes?->first()?->id;

        return $fromTemplate ? (int) $fromTemplate : 0;
    }

    private function switchRecurringSlotInstance(SubscriptionRecurringSlot $recurringSlot, SubscriptionInstance $instance): void
    {
        Log::info('Créneau récurrent rattaché au carnet suivant', [
            'recurring_slot_id' => $recurringSlot->id,
            'from_subscription_instance_id' => $recurringSlot->subscription_instance_id,
            'to_subscription_instance_id' => $instance->id,
        ]);

        $recurringSlot->forceFill(['subscription_instance_id' => $instance->id])->save();
        $recurringSlot->setRelation('subscriptionInstance', $instance);
    }

    /**
     * Autre série active même élève / enseignant / jour / horaire, valide à la date (ex. changement de carnet).
     */
    private function findSuccessorRecurringSlotForDate(
        SubscriptionRecurringSlot $requested,
        Carbon $occurrenceDate,
        RecurringSlotValidator $validator
    ): ?SubscriptionRecurringSlot {
        $clubId = (int) ($requested->subscriptionInstance?->subscription?->club_id ?? 0);
        if ($clubId <= 0 || ! $requested->student_id || ! $requested->teacher_id) {
            return null;
        }

        $startTime = $requested->start_time instanceof Carbon
            ? $requested->start_time->format('H:i:s')
            : substr((string) $requested->start_time, 0, 8);

        $candidates = SubscriptionRecurringSlot::query()
            ->where('id', '!=', $requested->id)
            ->where('status', 'active')
            ->where('student_id', $requested->student_id)
            ->where('teacher_id', $requested->teacher_id)
            ->where('day_of_week', $requested->day_of_week)
            ->where('start_time', $startTime)
            ->whereHas('subscriptionInstance.subscription', fn ($q) => $q->where('club_id', $clubId))
            ->with(['subscriptionInstance.subscription'])
            ->orderByDesc('start_date')
            ->get();

        foreach ($candidates as $slot) {
            if ($validator->subscriptionRecurringSlotFiresOnDate($slot, $occurrenceDate)) {
                return $slot;
            }
        }

        return null;
    }
}
