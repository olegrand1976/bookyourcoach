<?php

namespace App\Services;

use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Prolonge automatiquement les récurrences des abonnements actifs pour maintenir
 * un horizon de 26 semaines (règle des 26 semaines). À exécuter chaque dimanche.
 */
class ProlongRecurringSlotsService
{
    /** Nombre de semaines à garder ouverts devant l'élève (règle métier). */
    public const HORIZON_WEEKS = 26;

    /** Prolongation par exécution : une semaine supplémentaire. */
    public const PROLONG_BY_WEEKS = 1;

    public function __construct(
        protected LegacyRecurringSlotService $legacyRecurringSlotService
    ) {
    }

    /**
     * Parcourt les abonnements actifs et prolonge d'une semaine les récurrences
     * dont la date de fin est avant l'horizon cible (aujourd'hui + 26 semaines).
     * Génère les cours pour la nouvelle semaine.
     *
     * @return array{prolonged: int, lessons_generated: int, lessons_skipped: int, errors: int}
     */
    public function prolongActiveRecurrences(): array
    {
        $today = Carbon::today();
        $horizonEnd = $today->copy()->addWeeks(self::HORIZON_WEEKS);

        $result = [
            'prolonged' => 0,
            'lessons_generated' => 0,
            'lessons_skipped' => 0,
            'errors' => 0,
        ];

        $slots = SubscriptionRecurringSlot::query()
            ->where('status', 'active')
            ->whereHas('subscriptionInstance', function ($q) use ($today) {
                $q->where('status', 'active');
                $q->where(function ($q2) use ($today) {
                    $q2->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', $today);
                });
            })
            ->with('subscriptionInstance')
            ->get();

        foreach ($slots as $slot) {
            $instance = $slot->subscriptionInstance;
            $targetEnd = $horizonEnd->copy();
            if ($instance && $instance->expires_at) {
                $expires = Carbon::parse($instance->expires_at)->endOfDay();
                if ($expires->isBefore($targetEnd)) {
                    $targetEnd = $expires;
                }
            }

            $currentEnd = Carbon::parse($slot->end_date)->startOfDay();
            if ($currentEnd->gte($targetEnd)) {
                continue;
            }

            $newEnd = $currentEnd->copy()->addWeeks(self::PROLONG_BY_WEEKS);
            if ($newEnd->gt($targetEnd)) {
                $newEnd = $targetEnd->copy();
            }

            try {
                $slot->end_date = $newEnd;
                $slot->save();
                $result['prolonged']++;

                Log::info('ProlongRecurringSlotsService: récurrence prolongée', [
                    'subscription_recurring_slot_id' => $slot->id,
                    'subscription_instance_id' => $slot->subscription_instance_id,
                    'previous_end' => $currentEnd->format('Y-m-d'),
                    'new_end' => $newEnd->format('Y-m-d'),
                ]);

                $stats = $this->legacyRecurringSlotService->generateLessonsForSlot($slot, null, null);
                $result['lessons_generated'] += $stats['generated'];
                $result['lessons_skipped'] += $stats['skipped'];
                $result['errors'] += $stats['errors'];
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::error('ProlongRecurringSlotsService: erreur prolongation/génération', [
                    'subscription_recurring_slot_id' => $slot->id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('ProlongRecurringSlotsService: exécution terminée', $result);
        return $result;
    }
}
