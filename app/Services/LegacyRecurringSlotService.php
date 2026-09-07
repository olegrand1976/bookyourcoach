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
     * @param  int  $reserveAttachmentSlots  Places à réserver (ex. cours déclencheur pas encore attaché)
     * @return array{generated: int, skipped: int, errors: int}
     */
    public function generateLessonsForSlot(
        SubscriptionRecurringSlot $recurringSlot,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $reserveAttachmentSlots = 0
    ): array {
        $t0 = microtime(true);
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
        $endDate = $endDate ?? $recurringEndDate->copy();

        if ($endDate->isAfter($recurringEndDate)) {
            $endDate = $recurringEndDate->copy();
        }

        $stats = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
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

        $clubId = (int) $templateLesson->club_id;
        $remainingSlots = PHP_INT_MAX;
        if ($isSubscriptionActive && $subscriptionInstance) {
            $remainingSlots = $subscriptionInstance->resolveRemainingAttachmentSlotsForPlanning();
            $remainingSlots = max(0, $remainingSlots - max(0, $reserveAttachmentSlots));
        }

        $closureSet = $this->prefetchClosureDates($clubId, $dates);
        $existingStartKeys = $this->prefetchExistingLessonStartKeys($recurringSlot, $dates);

        $createdLessons = [];

        try {
            DB::transaction(function () use (
                $dates,
                &$remainingSlots,
                &$stats,
                &$createdLessons,
                $existingStartKeys,
                $closureSet,
                $recurringSlot,
                $templateLesson,
                $isSubscriptionActive,
                $subscriptionInstance
            ) {
                $keys = $existingStartKeys;

                foreach ($dates as $date) {
                    if ($remainingSlots <= 0) {
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
                        'notes' => 'Cours généré automatiquement depuis créneau récurrent',
                    ]);

                    $createdLessons[] = $lesson;
                    $keys[$startKey] = true;
                    $stats['generated']++;
                    if ($remainingSlots !== PHP_INT_MAX) {
                        $remainingSlots--;
                    }
                }

                if ($isSubscriptionActive && $subscriptionInstance && $createdLessons !== []) {
                    $this->attachGeneratedLessonsBatch($subscriptionInstance, $createdLessons);
                }
            });
        } catch (\Exception $e) {
            $stats['errors']++;
            $stats['generated'] = 0;
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
            'notes' => 'Cours généré automatiquement depuis créneau récurrent',
        ]);

        if ($subscriptionInstance) {
            try {
                $subscriptionInstance->consumeLesson($lesson);
            } catch (\Exception $e) {
                Log::warning("Impossible de consommer l'abonnement pour la lesson #{$lesson->id}: ".$e->getMessage());
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
        ];

        $recurringSlots = SubscriptionRecurringSlot::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        foreach ($recurringSlots as $slot) {
            try {
                $stats = $this->generateLessonsForSlot($slot, $startDate, $endDate);
                $totalStats['generated'] += $stats['generated'];
                $totalStats['skipped'] += $stats['skipped'];
                $totalStats['errors'] += $stats['errors'];
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
        if ($clubId > 0 && ClubClosureDay::clubIsClosedOn($clubId, LessonCalendarDate::toYmd($dayStart) ?? '')) {
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

        if ($isSubscriptionActive && $subscriptionInstance && ! $this->canPlanAnotherLesson($subscriptionInstance)) {
            return [
                'success' => false,
                'lesson' => null,
                'already_existed' => false,
                'message' => 'Aucune place restante sur l\'abonnement pour ce cours.',
            ];
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

        $recurringSlot->forceFill(['last_generated_at' => now()])->save();

        return [
            'success' => true,
            'lesson' => $lesson,
            'already_existed' => false,
            'message' => null,
        ];
    }

    private function canPlanAnotherLesson(SubscriptionInstance $subscriptionInstance): bool
    {
        return $subscriptionInstance->fresh()->resolveRemainingAttachmentSlotsForPlanning() > 0;
    }
}
