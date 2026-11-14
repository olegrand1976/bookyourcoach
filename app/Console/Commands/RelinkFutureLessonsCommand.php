<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionInstance;
use App\Services\LessonActionLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cours à venir d'un élève restés rattachés à un abonnement terminé, expiré ou dont l'échéance
 * précède le cours (séries créées avant le contrôle de validité/capacité) : détachés puis
 * rattachés à l'abonnement valide à leur date s'il reste une place, sinon laissés sans abonnement.
 * Les cours passés et les corrections manuelles des compteurs ne sont pas touchés.
 */
class RelinkFutureLessonsCommand extends Command
{
    protected $signature = 'subscriptions:relink-future-lessons
                            {--student= : ID de l\'élève}
                            {--apply : Écrire (simulation par défaut, transaction annulée)}';

    protected $description = 'Rattache à un abonnement valide les cours à venir d\'un élève liés à un abonnement terminé ou dépassé';

    public function handle(LessonActionLogService $actionLog): int
    {
        $studentId = (int) $this->option('student');
        if ($studentId <= 0) {
            $this->error('--student est obligatoire.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $rows = [];

        DB::beginTransaction();
        try {
            $links = DB::table('subscription_lessons as sl')
                ->join('lessons as l', 'l.id', '=', 'sl.lesson_id')
                ->join('subscription_instances as si', 'si.id', '=', 'sl.subscription_instance_id')
                ->join('subscription_instance_students as sis', 'sis.subscription_instance_id', '=', 'si.id')
                ->where('sis.student_id', $studentId)
                ->where(function ($q) use ($studentId) {
                    $q->where('l.student_id', $studentId)
                        ->orWhereExists(fn ($e) => $e->select(DB::raw(1))->from('lesson_student as ls')
                            ->whereColumn('ls.lesson_id', 'l.id')->where('ls.student_id', $studentId));
                })
                ->whereNull('l.deleted_at')
                ->whereIn('l.status', ['pending', 'confirmed'])
                ->where('l.start_time', '>', now())
                ->where(function ($q) {
                    $q->where('si.status', '!=', 'active')
                        ->orWhereRaw('DATE(l.start_time) > DATE(si.expires_at)');
                })
                ->orderBy('l.start_time')
                ->select('sl.lesson_id', 'sl.subscription_instance_id')
                ->get();

            foreach ($links as $link) {
                $lesson = Lesson::findOrFail($link->lesson_id);
                $old = SubscriptionInstance::with('subscription')->findOrFail($link->subscription_instance_id);

                $old->lessons()->detach($lesson->id);
                $actionLog->log($lesson, LessonActionLog::ACTION_SUBSCRIPTION_UNLINKED, null, 'system',
                    subscriptionInstanceId: $old->id, studentId: $studentId,
                    meta: ['reason' => 'subscriptions:relink-future-lessons']);

                $target = SubscriptionInstance::findActiveSubscriptionForLesson(
                    $studentId, (int) $lesson->course_type_id, (int) $lesson->club_id, $lesson->start_time
                );
                if ($target !== null) {
                    $target->consumeLesson($lesson);
                    if ($target->lessons()->where('lesson_id', $lesson->id)->exists()) {
                        $actionLog->log($lesson, LessonActionLog::ACTION_SUBSCRIPTION_LINKED, null, 'system',
                            subscriptionInstanceId: $target->id, studentId: $studentId,
                            meta: ['reason' => 'subscriptions:relink-future-lessons']);
                    } else {
                        $target = null;
                    }
                }

                $rows[] = [
                    $lesson->id,
                    $lesson->start_time->format('Y-m-d H:i'),
                    $old->subscription?->subscription_number." ({$old->status})",
                    $target?->subscription?->subscription_number ?? '— sans abonnement',
                ];
            }

            $apply ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Cours', 'Date', 'Ancien abonnement', 'Nouvel abonnement'], $rows);
        $this->info(($apply ? 'Cours traités : ' : 'Cours à traiter (simulation) : ').count($rows));

        return self::SUCCESS;
    }
}
