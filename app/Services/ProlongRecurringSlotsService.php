<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Planification de l'avenir : les séries récurrentes restent ouvertes jusqu'à l'horizon glissant
 * (26 semaines, prolongé chaque jour), sauf quand l'élève est sur son « dernier abonnement » : la
 * série s'arrête alors à son expiration. Les cours ne sont générés que dans la limite des places
 * des carnets ; au-delà, les occurrences restent une prévisualisation.
 *
 * Exécuté chaque jour par le planificateur, et à la demande depuis la fiche élève.
 */
class ProlongRecurringSlotsService
{
    /** Nombre de semaines à garder ouverts devant l'élève (règle métier). */
    public const HORIZON_WEEKS = 26;

    /**
     * Une série déjà terminée n'est rouverte que si elle s'est arrêtée il y a au plus ce nombre de semaines
     * (série coupée par l'ancien horizon ou l'expiration d'un carnet alors que l'élève continue).
     * Au-delà, l'élève a arrêté ou changé de cours : la rouvrir ressusciterait une série morte
     * (incident du 12/11/2026 : séries finies en janvier–mars rouvertes jusqu'en 2027).
     */
    public const REOPEN_MAX_WEEKS_AFTER_END = 8;

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
     * Série déjà terminée : rouverte seulement si sa fin est récente (REOPEN_MAX_WEEKS_AFTER_END) et
     * que l'élève n'a pas, depuis, une autre série active (changement d'enseignant ou d'horaire).
     * Jamais rouverte : une série dont la fin a été voulue (end_locked_at : tronquée depuis le planning,
     * suppression « toutes les suivantes »).
     *
     * @return array{prolonged: int, trimmed: int, lessons_generated: int, lessons_skipped: int, lessons_conflicts: int, errors: int, details: list<array<string, mixed>>, conflicts: list<array<string, mixed>>}
     */
    public function prolongActiveRecurrences(?int $studentId = null, ?int $clubId = null, bool $dryRun = false): array
    {
        $today = Carbon::today();
        $horizonEnd = $today->copy()->addWeeks(self::HORIZON_WEEKS)->endOfDay();

        $result = [
            'prolonged' => 0,
            'trimmed' => 0,
            'lessons_generated' => 0,
            'lessons_skipped' => 0,
            'lessons_conflicts' => 0,
            'errors' => 0,
            'details' => [],
            'conflicts' => [],
        ];

        $slots = SubscriptionRecurringSlot::query()
            ->where('status', 'active')
            ->whereNotNull('student_id')
            ->whereDate('end_date', '>=', $today->copy()->subWeeks(self::REOPEN_MAX_WEEKS_AFTER_END)->toDateString())
            ->whereNull('end_locked_at')
            ->when($studentId !== null, fn ($q) => $q->where('student_id', $studentId))
            ->when($clubId !== null, fn ($q) => $q->whereHas('subscriptionInstance.subscription', fn ($s) => $s->forClub($clubId)))
            ->with('subscriptionInstance.subscription')
            ->orderBy('start_date')
            ->get();

        foreach ($slots as $slot) {
            if ($this->hasSuccessor($slot, $slots)) {
                continue;
            }

            if (Carbon::parse($slot->end_date)->lt($today) && $this->wasReplacedAfterEnd($slot)) {
                continue;
            }

            $targetEnd = $this->targetEndFor($slot, $horizonEnd, $today);
            if ($targetEnd === null) {
                continue;
            }

            $currentEnd = Carbon::parse($slot->end_date)->endOfDay();
            $needsProlong = $currentEnd->lt($targetEnd->copy()->startOfDay());
            // « Dernier abonnement » : une série déjà ouverte plus loin s'arrête à sa fin (aucun cours supprimé).
            $needsTrim = $targetEnd->lt($horizonEnd->copy()->startOfDay())
                && $currentEnd->gt($targetEnd)
                && $targetEnd->gte(Carbon::parse($slot->start_date)->startOfDay());

            if ($needsProlong || $needsTrim) {
                $result['details'][] = [
                    'subscription_recurring_slot_id' => (int) $slot->id,
                    'student_id' => (int) $slot->student_id,
                    'teacher_id' => $slot->teacher_id !== null ? (int) $slot->teacher_id : null,
                    'action' => $needsTrim ? 'trim' : 'prolong',
                    'previous_end' => $currentEnd->format('Y-m-d'),
                    'new_end' => $targetEnd->format('Y-m-d'),
                ];
            }

            if ($dryRun) {
                $result['prolonged'] += $needsProlong ? 1 : 0;
                $result['trimmed'] += $needsTrim ? 1 : 0;

                continue;
            }

            try {
                if ($needsTrim) {
                    $previous = Carbon::parse($slot->end_date)->format('Y-m-d');
                    $slot->end_date = $targetEnd->copy()->startOfDay();
                    $slot->save();
                    $result['trimmed']++;

                    Log::info('ProlongRecurringSlotsService: récurrence arrêtée à la fin du dernier abonnement', [
                        'subscription_recurring_slot_id' => $slot->id,
                        'previous_end' => $previous,
                        'new_end' => Carbon::parse($slot->end_date)->format('Y-m-d'),
                    ]);
                }

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
                // (carnet plein au moment de la génération, nouveau carnet depuis). Jamais dans le passé.
                $stats = $this->legacyRecurringSlotService->generateLessonsForSlot($slot, null, null, 0, $today);
                $result['lessons_generated'] += $stats['generated'];
                $result['lessons_skipped'] += $stats['skipped'];
                $result['lessons_conflicts'] += count($stats['conflicts'] ?? []);
                array_push($result['conflicts'], ...($stats['conflicts'] ?? []));
                $result['errors'] += $stats['errors'];
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::error('ProlongRecurringSlotsService: erreur prolongation/génération', [
                    'subscription_recurring_slot_id' => $slot->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProlongRecurringSlotsService: exécution terminée', array_diff_key($result, ['details' => true, 'conflicts' => true]) + ['student_id' => $studentId, 'club_id' => $clubId]);

        return $result;
    }

    /**
     * Fin cible de la série (carnets de l'élève dans le club de la série) :
     * - un carnet actif sans mention « dernier abonnement » → horizon glissant (récurrence sans fin) ;
     * - tous les carnets actifs sont des « derniers abonnements » → leur plus lointaine expiration ;
     * - aucun carnet actif → horizon si le plus récent n'est pas un « dernier abonnement »
     *   (prévisualisation en attendant un renouvellement), sinon null (la série s'arrête).
     *
     * Ouvrir la série ne crée aucun cours non couvert : la génération s'arrête faute de carnet.
     */
    private function targetEndFor(SubscriptionRecurringSlot $slot, Carbon $horizonEnd, Carbon $today): ?Carbon
    {
        $clubId = $slot->subscriptionInstance?->subscription?->club_id;

        $instances = SubscriptionInstance::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $slot->student_id))
            ->when($clubId, fn ($q) => $q->whereHas('subscription', fn ($s) => $s->forClub($clubId)))
            ->get(['id', 'status', 'expires_at', 'is_last_subscription']);

        if ($instances->isEmpty()) {
            return null;
        }

        $active = $instances->filter(fn ($i) => $i->status === 'active'
            && ($i->expires_at === null || Carbon::parse($i->expires_at)->endOfDay()->gte($today)));

        if ($active->isEmpty()) {
            return $instances->sortByDesc('id')->first()->is_last_subscription ? null : $horizonEnd->copy();
        }

        if ($active->contains(fn ($i) => ! $i->is_last_subscription || $i->expires_at === null)) {
            return $horizonEnd->copy();
        }

        $latest = $active->map(fn ($i) => Carbon::parse($i->expires_at)->endOfDay())->max();

        return $latest->lt($horizonEnd) ? $latest : $horizonEnd->copy();
    }

    /**
     * Série terminée remplacée : l'élève a, depuis sa fin, une autre série active
     * (autre enseignant, jour ou horaire). Ne pas la rouvrir.
     */
    private function wasReplacedAfterEnd(SubscriptionRecurringSlot $slot): bool
    {
        return SubscriptionRecurringSlot::query()
            ->where('student_id', $slot->student_id)
            ->where('id', '!=', $slot->id)
            ->where('status', 'active')
            ->whereDate('start_date', '>', Carbon::parse($slot->end_date)->subWeek()->toDateString())
            ->exists();
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
