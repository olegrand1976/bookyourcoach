<?php

namespace App\Console\Commands;

use App\Models\LessonReplacement;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pending replacement requests: email the substitute at ~48h and ~24h before the lesson (TO remplaçant, CC responsables + demandeur).
 */
class SendLessonReplacementRemindersCommand extends Command
{
    protected $signature = 'lesson-replacements:send-pending-reminders';

    protected $description = 'Envoie les relances automatiques (48 h et 24 h) pour les demandes de remplacement sans réponse';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Relances demandes de remplacement (48 h / 24 h)...');

        $stats = ['48h' => 0, '24h' => 0, 'errors' => 0];

        LessonReplacement::query()
            ->where('status', 'pending')
            ->whereNull('reminder_48h_sent_at')
            ->whereHas('lesson', function ($q) {
                $q->where('start_time', '>', now())
                    ->whereBetween('start_time', [now()->copy()->addHours(47), now()->copy()->addHours(49)]);
            })
            ->with([
                'lesson.club',
                'lesson.courseType',
                'lesson.student.user',
                'originalTeacher.user',
                'replacementTeacher.user',
            ])
            ->orderBy('id')
            ->chunkById(50, function ($replacements) use ($notificationService, &$stats) {
                foreach ($replacements as $replacement) {
                    try {
                        $notificationService->sendLessonReplacementPendingReminder($replacement, '48h');
                        $stats['48h']++;
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        Log::error('❌ Relance 48 h remplacement: '.$e->getMessage(), [
                            'replacement_id' => $replacement->id,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        LessonReplacement::query()
            ->where('status', 'pending')
            ->whereNull('reminder_24h_sent_at')
            ->whereHas('lesson', function ($q) {
                $q->where('start_time', '>', now())
                    ->whereBetween('start_time', [now()->copy()->addHours(23), now()->copy()->addHours(25)]);
            })
            ->with([
                'lesson.club',
                'lesson.courseType',
                'lesson.student.user',
                'originalTeacher.user',
                'replacementTeacher.user',
            ])
            ->orderBy('id')
            ->chunkById(50, function ($replacements) use ($notificationService, &$stats) {
                foreach ($replacements as $replacement) {
                    try {
                        $notificationService->sendLessonReplacementPendingReminder($replacement, '24h');
                        $stats['24h']++;
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        Log::error('❌ Relance 24 h remplacement: '.$e->getMessage(), [
                            'replacement_id' => $replacement->id,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        $this->info("Envoyées — 48 h : {$stats['48h']}, 24 h : {$stats['24h']}, erreurs : {$stats['errors']}");

        return self::SUCCESS;
    }
}
