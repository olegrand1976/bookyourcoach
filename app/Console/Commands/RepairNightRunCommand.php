<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionInstance;
use App\Models\SubscriptionRecurringSlot;
use App\Services\LessonActionLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remise en état après une génération nocturne erronée (incident du 12/11/2026) :
 * - supprime les cours générés listés, seulement s'ils sont restés intacts depuis leur génération
 *   (aucune action tracée, statut confirmé, jamais modifiés) — même effet qu'une suppression club :
 *   trace « Suppression », date sautée sur la série encore active, détachement de l'abonnement,
 *   suppression définitive, recalcul des compteurs ;
 * - remet la date de fin des séries rouvertes à tort et la verrouille, sans jamais couper
 *   un cours planifié par le club (la fin est repoussée au dernier cours futur restant de la série).
 *
 * Par défaut : simulation. --apply pour écrire.
 *
 * Exemple Cloud Run (séparateur « @ » car les listes contiennent des virgules) :
 *   --args=^@^artisan@recurring-slots:repair-night-run@--lessons=1,2@--slot-ends=10:2026-03-08
 */
class RepairNightRunCommand extends Command
{
    private const GENERATED_NOTE = 'Cours généré automatiquement depuis créneau récurrent';

    protected $signature = 'recurring-slots:repair-night-run
                            {--lessons= : IDs des cours générés à supprimer (séparés par des virgules)}
                            {--slot-ends= : Séries à refermer, « id:AAAA-MM-JJ » séparés par des virgules (ancienne date de fin)}
                            {--reason=Correction de la génération automatique du 12/11/2026 : Raison tracée}
                            {--apply : Écrire en base (sinon simulation)}';

    protected $description = 'Supprime des cours générés à tort et referme les séries rouvertes à tort (simulation par défaut)';

    public function handle(LessonActionLogService $actionLog): int
    {
        $apply = (bool) $this->option('apply');
        $reason = (string) $this->option('reason');
        $lessonIds = $this->parseIds((string) $this->option('lessons'));
        $slotEnds = $this->parseSlotEnds((string) $this->option('slot-ends'));

        $this->info($apply ? '✍️  Mode APPLY : les modifications seront écrites' : '🔎 Simulation (ajouter --apply pour écrire)');

        $lessonRows = [];
        $toDelete = [];
        foreach ($lessonIds as $id) {
            [$verdict, $lesson] = $this->checkLesson($id);
            $lessonRows[] = [$id, $lesson ? Carbon::parse($lesson->start_time)->format('Y-m-d H:i') : '', $lesson?->teacher_id, $lesson?->student_id, $verdict];
            if ($verdict === 'à supprimer') {
                $toDelete[] = $lesson;
            }
        }
        $deleteIds = array_map(fn (Lesson $l) => (int) $l->id, $toDelete);

        $slotRows = [];
        $toClose = [];
        foreach ($slotEnds as $slotId => $oldEnd) {
            [$verdict, $slot, $newEnd] = $this->checkSlot($slotId, $oldEnd, $deleteIds);
            $slotRows[] = [$slotId, $slot?->student_id, $slot?->teacher_id, $slot ? Carbon::parse($slot->end_date)->format('Y-m-d') : '', $newEnd?->format('Y-m-d') ?? '', $verdict];
            if ($slot && $newEnd) {
                $toClose[] = [$slot, $newEnd];
            }
        }

        if ($lessonRows !== []) {
            $this->table(['Cours', 'Début', 'Ens.', 'Élève', 'Verdict'], $lessonRows);
        }
        if ($slotRows !== []) {
            $this->table(['Série', 'Élève', 'Ens.', 'Fin actuelle', 'Nouvelle fin', 'Verdict'], $slotRows);
        }
        $this->info('Cours à supprimer : '.count($toDelete).' / Séries à refermer : '.count($toClose));

        if (! $apply) {
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($toDelete, $toClose, $reason, $actionLog) {
            $instanceIds = [];
            foreach ($toDelete as $lesson) {
                $actionLog->log($lesson, LessonActionLog::ACTION_DELETED, null, 'system', meta: ['reason' => $reason]);
                $ids = $lesson->subscriptionInstances()->pluck('subscription_instances.id')->map(fn ($i) => (int) $i)->all();
                array_push($instanceIds, ...$ids);
                $lesson->subscriptionInstances()->detach();
                $this->skipDateOnActiveSeries($lesson, $reason);
                $lesson->forceDelete();
            }

            foreach (SubscriptionInstance::whereIn('id', array_unique($instanceIds))->get() as $instance) {
                $instance->recalculateLessonsUsed();
                $instance->checkAndUpdateStatus();
            }

            $hasLock = Schema::hasColumn('subscription_recurring_slots', 'end_locked_at');
            foreach ($toClose as [$slot, $newEnd]) {
                $previous = Carbon::parse($slot->end_date)->format('Y-m-d');
                $slot->notes = ($slot->notes ? $slot->notes."\n" : '')
                    ."Fin rétablie au {$newEnd->format('Y-m-d')} (était {$previous}) : {$reason}";
                if ($newEnd->lt(Carbon::parse($slot->start_date)->startOfDay())) {
                    $slot->status = 'cancelled';
                } else {
                    $slot->end_date = $newEnd;
                }
                if ($hasLock) {
                    $slot->end_locked_at = now();
                }
                $slot->save();
            }
        });

        $this->info('✅ Remise en état appliquée');

        return Command::SUCCESS;
    }

    /**
     * Comme une suppression club : la série encore active saute cette date, pour qu'aucune génération
     * ne recrée le cours.
     */
    private function skipDateOnActiveSeries(Lesson $lesson, string $reason): void
    {
        $start = Carbon::parse($lesson->start_time);
        SubscriptionRecurringSlot::query()
            ->where('status', 'active')
            ->where('student_id', $lesson->student_id)
            ->where('teacher_id', $lesson->teacher_id)
            ->where('day_of_week', (int) $start->dayOfWeek)
            ->whereTime('start_time', $start->format('H:i:s'))
            ->whereDate('start_date', '<=', $start->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->each(fn (SubscriptionRecurringSlot $slot) => $slot->skipOccurrenceDate($start->toDateString(), $reason));
    }

    /**
     * @return array{0: string, 1: ?Lesson}
     */
    private function checkLesson(int $id): array
    {
        $lesson = Lesson::withTrashed()->find($id);
        if (! $lesson) {
            return ['déjà supprimé', null];
        }
        if ($lesson->trashed()) {
            return ['déjà archivé', $lesson];
        }
        if ($lesson->notes !== self::GENERATED_NOTE) {
            return ['ignoré : pas un cours généré', $lesson];
        }
        if ($lesson->status !== 'confirmed') {
            return ["ignoré : statut {$lesson->status}", $lesson];
        }
        if (abs(Carbon::parse($lesson->updated_at)->diffInSeconds(Carbon::parse($lesson->created_at))) > 60) {
            return ['ignoré : modifié depuis sa génération', $lesson];
        }
        $touched = LessonActionLog::query()
            ->where('lesson_id', $id)
            ->where('action', '!=', LessonActionLog::ACTION_GENERATED)
            ->exists();
        if ($touched) {
            return ['ignoré : action tracée depuis', $lesson];
        }

        return ['à supprimer', $lesson];
    }

    /**
     * @param  list<int>  $deleteIds
     * @return array{0: string, 1: ?SubscriptionRecurringSlot, 2: ?Carbon}
     */
    private function checkSlot(int $slotId, Carbon $oldEnd, array $deleteIds): array
    {
        $slot = SubscriptionRecurringSlot::find($slotId);
        if (! $slot) {
            return ['introuvable', null, null];
        }
        $currentEnd = Carbon::parse($slot->end_date)->startOfDay();
        if ($currentEnd->lte($oldEnd)) {
            return ['déjà refermée', $slot, null];
        }

        // Jamais couper un cours planifié qui reste : la fin va au moins jusqu'au dernier cours futur de la série.
        $lastKept = Lesson::query()
            ->where('student_id', $slot->student_id)
            ->where('teacher_id', $slot->teacher_id)
            ->where('start_time', '>=', Carbon::today())
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('id', $deleteIds === [] ? [0] : $deleteIds)
            ->get(['id', 'start_time'])
            ->filter(function (Lesson $l) use ($slot) {
                $start = Carbon::parse($l->start_time);
                $slotTime = substr((string) ($slot->start_time instanceof Carbon ? $slot->start_time->format('H:i:s') : $slot->start_time), 0, 8);

                return (int) $start->dayOfWeek === (int) $slot->day_of_week && $start->format('H:i:s') === $slotTime;
            })
            ->map(fn (Lesson $l) => Carbon::parse($l->start_time)->startOfDay())
            ->max();

        $newEnd = $lastKept && $lastKept->gt($oldEnd) ? $lastKept : $oldEnd->copy();
        if ($currentEnd->lte($newEnd)) {
            return ['inchangée (cours planifiés jusqu\'à la fin actuelle)', $slot, null];
        }

        return [$lastKept && $lastKept->gt($oldEnd) ? 'à refermer (après le dernier cours gardé)' : 'à refermer', $slot, $newEnd];
    }

    /**
     * @return list<int>
     */
    private function parseIds(string $raw): array
    {
        return array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', $raw) ?: []))));
    }

    /**
     * @return array<int, Carbon>
     */
    private function parseSlotEnds(string $raw): array
    {
        $out = [];
        foreach (preg_split('/[\s,]+/', $raw) ?: [] as $pair) {
            if (! str_contains($pair, ':')) {
                continue;
            }
            [$id, $date] = explode(':', $pair, 2);
            $out[(int) $id] = Carbon::parse($date)->startOfDay();
        }

        return $out;
    }
}
