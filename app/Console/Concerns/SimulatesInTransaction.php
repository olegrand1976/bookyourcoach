<?php

namespace App\Console\Concerns;

use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

/**
 * --dry-run fidèle : exécute le vrai traitement dans une transaction annulée à la fin,
 * puis affiche les cours qu'il aurait créés. Rien n'est écrit en base.
 *
 * À lancer hors des heures d'usage : la transaction garde des verrous le temps du calcul.
 */
trait SimulatesInTransaction
{
    /**
     * @template T
     *
     * @param  callable(): T  $run
     * @return array{0: T, 1: list<array<string, mixed>>}
     */
    protected function simulate(callable $run): array
    {
        $maxLessonId = (int) Lesson::withTrashed()->max('id');

        DB::beginTransaction();
        try {
            $result = $run();
            $created = Lesson::query()
                ->where('id', '>', $maxLessonId)
                ->with(['teacher.user:id,name', 'student:id,first_name,last_name'])
                ->orderBy('start_time')
                ->get()
                ->map(fn (Lesson $l) => [
                    'date' => \Carbon\Carbon::parse($l->start_time)->format('Y-m-d H:i'),
                    'teacher' => $l->teacher?->user?->name ?? ('#'.$l->teacher_id),
                    'student' => trim(($l->student?->first_name ?? '').' '.($l->student?->last_name ?? '')) ?: ('#'.$l->student_id),
                ])
                ->all();
        } finally {
            DB::rollBack();
        }

        return [$result, $created];
    }

    /**
     * @param  list<array<string, mixed>>  $created
     */
    protected function printSimulatedLessons(array $created): void
    {
        $this->info('📋 Cours qui seraient créés : '.count($created));
        if ($created !== []) {
            $this->table(['Début', 'Enseignant', 'Élève'], array_map('array_values', $created));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $conflicts
     */
    protected function printConflicts(array $conflicts): void
    {
        if ($conflicts === []) {
            return;
        }
        $this->warn('⚠️  Occurrences non générées pour conflit : '.count($conflicts));
        $this->table(
            ['Série', 'Date', 'Heure', 'Type', 'Message'],
            array_map(fn (array $c) => [
                $c['recurring_slot_id'] ?? '',
                $c['date'] ?? '',
                $c['time'] ?? '',
                $c['type'] ?? '',
                $c['message'] ?? '',
            ], $conflicts)
        );
    }
}
