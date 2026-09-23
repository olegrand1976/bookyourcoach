<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionInstance;
use App\Models\User;
use App\Services\LessonActionLogService;
use App\Services\LessonCalendarDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remédiation one-shot de l'incident du 2026-09-23 (club 11).
 *
 * Une fermeture de journée accidentelle a détaché 22 cours de leur carnet
 * d'abonnement, avec restitution des crédits. La réouverture de la journée ne
 * rattache rien : ces liens sont restés perdus. Les couples ci-dessous ont été
 * relevés dans les journaux de production ("Club closure: lesson detached from
 * subscription instance").
 */
class RemediateClosure20260923Command extends Command
{
    protected $signature = 'subscriptions:remediate-closure-2026-09-23
                            {--apply : Écrire réellement (sans ce drapeau : simulation)}
                            {--force : Ne pas demander de confirmation (obligatoire sans TTY)}';

    protected $description = 'Rattache les 22 cours détachés par la fermeture accidentelle du 2026-09-23 (club 11)';

    private const CLUB_ID = 11;

    private const CLOSURE_DATE = '2026-09-23';

    /** @var array<int, int> lesson_id => subscription_instance_id */
    private const LINKS = [
        1914 => 111,
        2700 => 120,
        2726 => 121,
        2753 => 113,
        2780 => 58,
        2806 => 114,
        3121 => 135,
        3145 => 137,
        3207 => 194,
        3231 => 115,
        3250 => 173,
        3340 => 209,
        3387 => 140,
        3408 => 212,
        3424 => 181,
        3436 => 178,
        3453 => 161,
        3534 => 152,
        3543 => 145,
        3551 => 197,
        3590 => 215,
        3601 => 216,
    ];

    public function handle(LessonActionLogService $actionLog): int
    {
        $apply = (bool) $this->option('apply');

        $this->info(($apply ? '' : '[SIMULATION] ')
            .'Remédiation de la fermeture du '.self::CLOSURE_DATE." — club ".self::CLUB_ID
            .' ('.count(self::LINKS).' liens attendus)');

        if (! $apply) {
            $this->warn('Aucune écriture : relancer avec --apply pour appliquer.');
        }

        if ($apply && ! $this->option('force') && ! $this->confirm('Rattacher les cours à leur carnet ?')) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        $stats = [
            'linked' => 0,
            'already_linked' => 0,
            'conflict' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        $rows = [];
        $instanceIdsToRecalculate = [];

        $actor = User::query()->where('role', User::ROLE_CLUB)->orderBy('id')->first();

        DB::transaction(function () use ($apply, $actionLog, $actor, &$stats, &$rows, &$instanceIdsToRecalculate) {
            foreach (self::LINKS as $lessonId => $instanceId) {
                try {
                    $verdict = $this->inspect($lessonId, $instanceId);
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $rows[] = [$lessonId, $instanceId, 'erreur', $e->getMessage()];
                    Log::error('Remédiation 2026-09-23 : inspection en échec', [
                        'lesson_id' => $lessonId,
                        'subscription_instance_id' => $instanceId,
                        'exception' => $e->getMessage(),
                    ]);

                    continue;
                }

                $rows[] = [$lessonId, $instanceId, $verdict['status'], $verdict['detail']];

                if ($verdict['status'] === 'conflit') {
                    $stats['conflict']++;

                    continue;
                }

                if ($verdict['status'] === 'déjà lié') {
                    $stats['already_linked']++;

                    continue;
                }

                if ($verdict['status'] !== 'à rattacher') {
                    $stats['skipped']++;

                    continue;
                }

                $stats['linked']++;
                $instanceIdsToRecalculate[$instanceId] = $instanceId;

                if (! $apply) {
                    continue;
                }

                $verdict['instance']->lessons()->syncWithoutDetaching([$lessonId]);

                $actionLog->logForClub(
                    self::CLUB_ID,
                    LessonActionLog::ACTION_SUBSCRIPTION_LINKED,
                    $actor,
                    $actor?->role,
                    $lessonId,
                    null,
                    $instanceId,
                    ['reason' => 'remediation_closure_'.self::CLOSURE_DATE],
                );
            }

            if ($apply) {
                $this->recalculate($instanceIdsToRecalculate);
            }
        });

        $this->table(['Cours', 'Carnet', 'État', 'Détail'], $rows);
        $this->line('');
        foreach ($stats as $label => $count) {
            $this->line(str_pad($label, 16).': '.$count);
        }

        if (! $apply) {
            $this->warn('Simulation terminée : rien n’a été écrit.');
        } else {
            $this->info('Remédiation appliquée.');
            Log::info('Remédiation 2026-09-23 appliquée', $stats);
        }

        if ($stats['conflict'] > 0 || $stats['errors'] > 0) {
            $this->error('Des couples demandent un arbitrage manuel (conflit ou erreur) — voir le tableau.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Vérifie un couple avant toute écriture.
     *
     * @return array{status: string, detail: string, instance: ?SubscriptionInstance}
     */
    private function inspect(int $lessonId, int $instanceId): array
    {
        $lesson = Lesson::withTrashed()->find($lessonId);

        if (! $lesson) {
            return $this->verdict('ignoré', 'cours introuvable');
        }

        if ($lesson->trashed()) {
            return $this->verdict('ignoré', 'cours supprimé depuis l’incident');
        }

        if ((int) $lesson->club_id !== self::CLUB_ID) {
            return $this->verdict('ignoré', "cours d’un autre club (#{$lesson->club_id})");
        }

        $ymd = LessonCalendarDate::toYmd($lesson->start_time);
        if ($ymd !== self::CLOSURE_DATE) {
            return $this->verdict('ignoré', "cours daté du {$ymd}, pas du ".self::CLOSURE_DATE);
        }

        $instance = SubscriptionInstance::with('subscription')->find($instanceId);

        if (! $instance) {
            return $this->verdict('ignoré', 'carnet introuvable');
        }

        if ((int) ($instance->subscription?->club_id) !== self::CLUB_ID) {
            return $this->verdict('ignoré', 'carnet rattaché à un autre club');
        }

        $currentInstanceIds = DB::table('subscription_lessons')
            ->where('lesson_id', $lessonId)
            ->pluck('subscription_instance_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (in_array($instanceId, $currentInstanceIds, true)) {
            return $this->verdict('déjà lié', 'rien à faire', $instance);
        }

        if ($currentInstanceIds !== []) {
            return $this->verdict(
                'conflit',
                'déjà rattaché au carnet #'.implode(', #', $currentInstanceIds),
                $instance,
            );
        }

        return $this->verdict('à rattacher', 'lien perdu à restaurer', $instance);
    }

    /**
     * @return array{status: string, detail: string, instance: ?SubscriptionInstance}
     */
    private function verdict(string $status, string $detail, ?SubscriptionInstance $instance = null): array
    {
        return ['status' => $status, 'detail' => $detail, 'instance' => $instance];
    }

    /**
     * Un seul recalcul par carnet, après tous les rattachements : recalculateLessonsUsed()
     * écrit et journalise à chaque appel.
     *
     * @param  array<int, int>  $instanceIds
     */
    private function recalculate(array $instanceIds): void
    {
        foreach ($instanceIds as $instanceId) {
            $instance = SubscriptionInstance::find($instanceId);
            if (! $instance) {
                continue;
            }

            $instance->recalculateLessonsUsed();
            $instance->checkAndUpdateStatus();

            $this->line("  Carnet #{$instanceId} recalculé : lessons_used = {$instance->fresh()->lessons_used}");
        }
    }
}
