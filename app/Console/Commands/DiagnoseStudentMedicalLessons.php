<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseStudentMedicalLessons extends Command
{
    protected $signature = 'diagnose:student-medical-lessons 
                            {student_id : ID de l’élève (ex. 167 pour Lala Murgo)}';

    protected $description = 'Liste les cours annulés pour raison médicale d’un élève (certificat à valider ou non) et vérifie s’ils entrent dans la limite des 100 cours de l’historique';

    public function handle(): int
    {
        $studentId = (int) $this->argument('student_id');
        $student = Student::with('user')->find($studentId);
        if (!$student) {
            $this->error("Élève id {$studentId} introuvable.");
            return self::FAILURE;
        }

        $name = $student->user->name ?? $student->user->first_name . ' ' . $student->user->last_name ?? "ID {$studentId}";
        $this->info("=== Élève : {$name} (id={$studentId}) ===\n");

        $hasCol = Schema::hasColumn('lessons', 'cancellation_certificate_status');
        if (!$hasCol) {
            $this->warn('Colonne cancellation_certificate_status absente. Migrations à exécuter.');
            return self::FAILURE;
        }

        // Tous les cours de l’élève (student_id ou pivot lesson_student)
        $baseQuery = Lesson::where(function ($q) use ($studentId) {
            $q->where('student_id', $studentId)
                ->orWhereHas('students', fn ($sq) => $sq->where('students.id', $studentId));
        });

        $totalLessons = (clone $baseQuery)->count();
        $this->line("Total cours (student_id ou lesson_student) : {$totalLessons}");

        // Cours annulés pour raison médicale
        $medicalCancelled = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->where('cancellation_reason', 'medical')
            ->orderBy('start_time', 'desc')
            ->get();

        $this->line("Cours annulés raison médicale : " . $medicalCancelled->count());
        if ($medicalCancelled->isEmpty()) {
            $this->warn('Aucun cours annulé pour raison médicale pour cet élève.');
            return self::SUCCESS;
        }

        $this->table(
            ['id', 'start_time', 'certificate_status', 'certificate_path', 'dans les 100 ?'],
            $medicalCancelled->map(function (Lesson $l) use ($studentId, $totalLessons) {
                $inTop100 = $this->isInTop100($l->id, $studentId, $totalLessons);
                return [
                    $l->id,
                    $l->start_time?->format('Y-m-d H:i'),
                    $l->cancellation_certificate_status ?? '—',
                    $l->cancellation_certificate_path ? 'oui' : 'non',
                    $inTop100 ? 'oui' : 'NON (exclu par limit 100)',
                ];
            })->toArray()
        );

        $pending = $medicalCancelled->where('cancellation_certificate_status', 'pending');
        $withPath = $medicalCancelled->whereNotNull('cancellation_certificate_path');
        $this->newLine();
        $this->line('À valider par le club (status=pending) : ' . $pending->count());
        $this->line('Avec fichier certificat (path renseigné) : ' . $withPath->count());

        return self::SUCCESS;
    }

    private function isInTop100(int $lessonId, int $studentId, int $totalLessons): bool
    {
        if ($totalLessons <= 100) {
            return true;
        }
        $top100Ids = Lesson::where(function ($q) use ($studentId) {
            $q->where('student_id', $studentId)
                ->orWhereHas('students', fn ($sq) => $sq->where('students.id', $studentId));
        })
            ->orderBy('start_time', 'desc')
            ->limit(100)
            ->pluck('id');

        return $top100Ids->contains($lessonId);
    }
}
