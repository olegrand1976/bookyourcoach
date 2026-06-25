<?php

namespace App\Console\Commands;

use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairSubscriptionCountersCommand extends Command
{
    protected $signature = 'subscriptions:repair-counters
                            {--dry-run : Simuler sans modifier les données}
                            {--club-id= : Limiter à un club}';

    protected $description = 'Répare les compteurs lessons_used : backfill manual_lessons_used, détache les futurs excédentaires, recalcule';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $clubId = $this->option('club-id') ? (int) $this->option('club-id') : null;

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune modification en base.');
        }

        $stats = [
            'backfilled_manual' => 0,
            'future_detached' => 0,
            'past_overflow' => 0,
            'recalculated' => 0,
            'errors' => 0,
        ];

        $query = SubscriptionInstance::query()->with('subscription.template');

        if ($clubId !== null) {
            $query->whereHas('subscription', function ($q) use ($clubId) {
                $q->where('club_id', $clubId);
            });
        }

        $instances = $query->get();
        $this->info("Traitement de {$instances->count()} instance(s)...");

        foreach ($instances as $instance) {
            try {
                if ($instance->manual_lessons_used === null) {
                    $consumed = $instance->getConsumedLessonsCount();
                    $derivedManual = max(0, (int) $instance->lessons_used - $consumed);

                    if (! $dryRun) {
                        $instance->manual_lessons_used = $derivedManual;
                        $instance->saveQuietly();
                    }

                    $stats['backfilled_manual']++;
                    $this->line("  Instance #{$instance->id} : manual_lessons_used ← {$derivedManual}");
                }

                $this->detachExcessFutureLessons($instance, $dryRun, $stats);

                $overflow = $instance->fresh()->getPastOverflowInfo();
                if ($overflow['past_exceeds_capacity']) {
                    $stats['past_overflow']++;
                    $this->warn("  Instance #{$instance->id} : passés seuls dépassent le plafond ({$overflow['consumed']}/{$overflow['capacity']}) — arbitrage manuel requis");
                }

                if (! $dryRun) {
                    $instance->refresh();
                    $instance->recalculateLessonsUsed();
                    $instance->checkAndUpdateStatus();
                }

                $stats['recalculated']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('subscriptions:repair-counters erreur', [
                    'subscription_instance_id' => $instance->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Instance #{$instance->id} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['manual_lessons_used backfillés', $stats['backfilled_manual']],
                ['Cours futurs détachés', $stats['future_detached']],
                ['Instances passés > plafond', $stats['past_overflow']],
                ['Instances recalculées', $stats['recalculated']],
                ['Erreurs', $stats['errors']],
            ]
        );

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array{consumed: int, capacity: int, past_exceeds_capacity: bool}
     */
    private function detachExcessFutureLessons(SubscriptionInstance $instance, bool $dryRun, array &$stats): array
    {
        $instance->loadMissing('subscription.template');
        $capacity = (int) ($instance->subscription->total_available_lessons ?? 0);
        $manual = max(0, (int) ($instance->manual_lessons_used ?? 0));
        $maxAttached = max(0, $capacity - $manual);

        $consumed = $instance->getConsumedLessonsCount();
        $attached = $instance->getAttachedCountableLessonsCount();

        if ($attached <= $maxAttached) {
            return [
                'consumed' => $consumed,
                'capacity' => $capacity,
                'past_exceeds_capacity' => $consumed > $maxAttached,
            ];
        }

        $excess = $attached - $maxAttached;

        $futureLessonIds = DB::table('subscription_lessons')
            ->join('lessons', 'subscription_lessons.lesson_id', '=', 'lessons.id')
            ->where('subscription_lessons.subscription_instance_id', $instance->id)
            ->whereIn('lessons.status', ['pending', 'confirmed', 'completed'])
            ->where('lessons.start_time', '>', Carbon::now())
            ->orderByDesc('lessons.start_time')
            ->pluck('lessons.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($futureLessonIds as $lessonId) {
            if ($excess <= 0) {
                break;
            }

            if (! $dryRun) {
                $instance->lessons()->detach($lessonId);
            }

            $stats['future_detached']++;
            $excess--;
            $this->line("  Instance #{$instance->id} : cours futur #{$lessonId} détaché (excédent)");
        }

        $instance->refresh();

        return [
            'consumed' => $instance->getConsumedLessonsCount(),
            'capacity' => $capacity,
            'past_exceeds_capacity' => $instance->getConsumedLessonsCount() > $maxAttached,
        ];
    }
}
