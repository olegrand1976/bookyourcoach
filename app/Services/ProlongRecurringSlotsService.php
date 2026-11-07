<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Planification de l'avenir : tant que l'élève a un abonnement actif, ses séries récurrentes
 * restent ouvertes jusqu'à l'horizon (26 semaines), borné à l'expiration de ses abonnements,
 * et les cours correspondants sont générés (dans la limite des places des carnets).
 *
 * Exécuté chaque jour par le planificateur, et à la demande depuis la fiche élève.
 */
class ProlongRecurringSlotsService
{
    /** Nombre de semaines à garder ouverts devant l'élève (règle métier). */
    public const HORIZON_WEEKS = 26;

    public function __construct(
        protected LegacyRecurringSlotService $legacyRecurringSlotService
    ) {}

    /**
     * Prolonge les séries actives jusqu'à l'horizon et génère les cours manquants.
     *
     * Une série reste prolongeable quand son carnet est terminé/expiré si l'élève a un autre
     * carnet actif : la génération bascule alors sur ce carnet (cf. LegacyRecurringSlotService).
     * Une série remplacée par une série plus récente (même élève, enseignant, jour et heure)
     * n'est pas prolongée.
     *
     * @return array{prolonged: int, lessons_generated: int, lessons_skipped: int, errors: int}
     */
    public function prolongActiveRecurrences(?int $studentId = null, ?int $clubId = null, bool $dryRun = false): array
    {
        $today = Carbon::today();
        $horizonEnd = $today->copy()->addWeeks(self::HORIZON_WEEKS)->endOfDay();

        $result = [
            'prolonged' => 0,
            'lessons_generated' => 0,
            'lessons_skipped' => 0,
            'errors' => 0,
        ];

        $slots = SubscriptionRecurringSlot::query()
            ->where('status', 'active')
            ->whereNotNull('student_id')
            ->when($studentId !== null, fn ($q) => $q->where('student_id', $studentId))
            ->when($clubId !== null, fn ($q) => $q->whereHas('subscriptionInstance.subscription', fn ($s) => $s->forClub($clubId)))
            ->with('subscriptionInstance.subscription')
            ->orderBy('start_date')
            ->get();

        foreach ($slots as $slot) {
            if ($this->hasSuccessor($slot, $slots)) {
                continue;
            }

            $targetEnd = $this->targetEndFor($slot, $horizonEnd, $today);
            if ($targetEnd === null) {
                continue;
            }

            $currentEnd = Carbon::parse($slot->end_date)->endOfDay();
            $needsProlong = $currentEnd->lt($targetEnd->copy()->startOfDay());

            if ($dryRun) {
                $result['prolonged'] += $needsProlong ? 1 : 0;

                continue;
            }

            try {
                if ($needsProlong) {
                    $previous = Carbon::parse($slot->end_date)->format('Y-m-d');
                    $slot->end_date = $targetEnd->copy()->startOfDay();
                    $slot->save();
                    $result['prolonged']++;

                    Log::info('ProlongRecurringSlotsService: récurrence prolongée', [
                        'subscription_recurring_slot_id' => $slot->id,
                        'subscription_instance_id' => $slot->subscription_instance_id,
                        'previous_end' => $previous,
                        'new_end' => Carbon::parse($slot->end_date)->format('Y-m-d'),
                    ]);
                }

                // Toujours (re)générer : une série déjà ouverte peut avoir des semaines sans cours
                // (carnet plein au moment de la génération, nouveau carnet depuis).
                // (carnet plein au moment de la génération, nouveau carnet depuis). Jamais dans le passé.
                $stats = $this->legacyRecurringSlotService->generateLessonsForSlot($slot, null, null, 0, $today);
                $result['lessons_generated'] += $stats['generated'];
                $result['lessons_skipped'] += $stats['skipped'];
                $result['errors'] += $stats['errors'];
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::error('ProlongRecurringSlotsService: erreur prolongation/génération', [
                    'subscription_recurring_slot_id' => $slot->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProlongRecurringSlotsService: exécution terminée', $result + ['student_id' => $studentId, 'club_id' => $clubId]);

        return $result;
    }

    /**
     * Fin cible : horizon, bornée à la plus lointaine expiration des carnets actifs de l'élève
     * (dans le club de la série). Null si l'élève n'a plus aucun carnet actif.
     */
    private function targetEndFor(SubscriptionRecurringSlot $slot, Carbon $horizonEnd, Carbon $today): ?Carbon
    {
        $clubId = $slot->subscriptionInstance?->subscription?->club_id;

        $active = SubscriptionInstance::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $today))
            ->whereHas('students', fn ($q) => $q->where('students.id', $slot->student_id))
            ->when($clubId, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->forClub($clubId)))
            ->get(['id', 'expires_at']);

        if ($active->isEmpty()) {
            return null;
        }
        if ($active->contains(fn ($i) => $i->expires_at === null)) {
            return $horizonEnd->copy();
        }

        $latest = $active->map(fn ($i) => Carbon::parse($i->expires_at)->endOfDay())->max();

        return $latest->lt($horizonEnd) ? $latest : $horizonEnd->copy();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SubscriptionRecurringSlot>  $slots
     */
    private function hasSuccessor(SubscriptionRecurringSlot $slot, $slots): bool
    {
        $time = substr((string) ($slot->start_time instanceof Carbon ? $slot->start_time->format('H:i:s') : $slot->start_time), 0, 5);

        return $slots->contains(function (SubscriptionRecurringSlot $other) use ($slot, $time) {
            if ((int) $other->id === (int) $slot->id) {
                return false;
            }
            $otherTime = substr((string) ($other->start_time instanceof Carbon ? $other->start_time->format('H:i:s') : $other->start_time), 0, 5);

            return (int) $other->student_id === (int) $slot->student_id
                && (int) $other->teacher_id === (int) $slot->teacher_id
                && (int) $other->day_of_week === (int) $slot->day_of_week
                && $otherTime === $time
                && Carbon::parse($other->start_date)->gt(Carbon::parse($slot->start_date));
        });
    }

    /** Dernier cours planifié de l'élève (retour de « Planifier l'avenir »). */
    public function lastPlannedLessonFor(int $studentId): ?Lesson
    {
        return Lesson::query()
            ->where('student_id', $studentId)
            ->where('start_time', '>=', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderByDesc('start_time')
            ->first();
    }
}
