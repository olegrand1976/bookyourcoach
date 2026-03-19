<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnosePendingCertificates extends Command
{
    protected $signature = 'diagnose:pending-certificates 
                            {--user-id= : ID user club pour simuler getFirstClub()}
                            {--fix-one= : ID du club pour mettre un cours annulé en pending (test affichage bloc)}';

    protected $description = 'Diagnostic DB et périmètre pour les certificats médicaux en attente (bloc planning)';

    public function handle(): int
    {
        $this->info('=== Diagnostic certificats médicaux (pending) ===');

        $hasCol = Schema::hasColumn('lessons', 'cancellation_certificate_status');
        $this->line('Colonne cancellation_certificate_status : ' . ($hasCol ? 'OUI' : 'NON'));
        if (!$hasCol) {
            $this->warn('Migration 2026_03_20_100000 peut ne pas avoir été exécutée. Lancez: php artisan migrate');
            return self::FAILURE;
        }

        $totalCancelled = Lesson::where('status', 'cancelled')->count();
        $totalPending = Lesson::where('status', 'cancelled')->where('cancellation_certificate_status', 'pending')->count();
        $this->line('Lessons status=cancelled : ' . $totalCancelled);
        $this->line('Lessons cancelled + cancellation_certificate_status=pending : ' . $totalPending);

        if ($totalPending === 0) {
            $this->warn('Aucun cours en base avec status=cancelled ET cancellation_certificate_status=pending.');
            $this->line('Valeurs distinctes de cancellation_certificate_status pour les cours annulés :');
            $values = Lesson::where('status', 'cancelled')
                ->selectRaw('cancellation_certificate_status as s')
                ->distinct()
                ->pluck('s');
            foreach ($values as $v) {
                $this->line('  - ' . (json_encode($v)));
            }
        }

        $userId = $this->option('user-id');
        if (!$userId) {
            $this->line('Option --user-id non fournie : pas de test de périmètre club.');
            $fixClubId = $this->option('fix-one');
            if ($fixClubId !== null && $fixClubId !== '') {
                $clubId = (int) $fixClubId;
                $candidate = Lesson::where('status', 'cancelled')
                    ->where(function ($q) use ($clubId) {
                        $q->where('club_id', $clubId)
                            ->orWhereHas('teacher', fn ($t) => $t->whereHas('clubs', fn ($cq) => $cq->where('clubs.id', $clubId)));
                    })
                    ->first();
                if (!$candidate) {
                    $any = Lesson::where('status', 'cancelled')->first();
                    if (!$any) {
                        $this->warn('Aucun cours annulé en base.');
                        return self::SUCCESS;
                    }
                    $candidate = $any;
                    $candidate->club_id = $clubId;
                    $this->line('Aucun cours annulé pour ce club ; utilisation du lesson id=' . $candidate->id . ' (club_id forcé à ' . $clubId . ').');
                }
                $candidate->cancellation_reason = 'medical';
                $candidate->cancellation_certificate_status = 'pending';
                $candidate->cancellation_certificate_path = $candidate->cancellation_certificate_path ?: 'test/diagnostic-placeholder';
                if (!$candidate->club_id) {
                    $candidate->club_id = $clubId;
                }
                if (Schema::hasColumn('lessons', 'cancellation_certificate_submitted_by_student_id')) {
                    $candidate->cancellation_certificate_submitted_by_student_id = $candidate->student_id;
                }
                $candidate->saveQuietly();
                $this->info('Lesson id=' . $candidate->id . ' mis en pending pour le club ' . $clubId . '. Rechargez le planning.');
            }
            return self::SUCCESS;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error('User id ' . $userId . ' introuvable.');
            return self::FAILURE;
        }
        $club = $user->getFirstClub();
        if (!$club) {
            $this->error('User ' . $userId . ' n\'a pas de club (getFirstClub() null).');
            return self::FAILURE;
        }

        $this->line('Club de l\'utilisateur : id=' . $club->id . ', name=' . ($club->name ?? 'N/A'));

        $withClubId = Lesson::where('status', 'cancelled')
            ->where('cancellation_certificate_status', 'pending')
            ->where('club_id', $club->id)
            ->count();
        $withTeacherInClub = Lesson::where('status', 'cancelled')
            ->where('cancellation_certificate_status', 'pending')
            ->whereHas('teacher', fn ($q) => $q->whereHas('clubs', fn ($cq) => $cq->where('clubs.id', $club->id)))
            ->count();

        $this->line('Pending avec club_id=' . $club->id . ' : ' . $withClubId);
        $this->line('Pending avec enseignant dans le club : ' . $withTeacherInClub);

        $lessons = Lesson::where('status', 'cancelled')
            ->where('cancellation_certificate_status', 'pending')
            ->where(function ($q) use ($club) {
                $q->where('club_id', $club->id)
                    ->orWhereHas('teacher', function ($t) use ($club) {
                        $t->whereHas('clubs', fn ($cq) => $cq->where('clubs.id', $club->id));
                    });
            })
            ->get();
        $this->line('Résultat requête pendingCertificates (count) : ' . $lessons->count());

        if ($lessons->count() > 0) {
            $this->line('Exemple premier lesson : id=' . $lessons->first()->id . ', club_id=' . $lessons->first()->club_id . ', teacher_id=' . $lessons->first()->teacher_id);
        }

        return self::SUCCESS;
    }
}
