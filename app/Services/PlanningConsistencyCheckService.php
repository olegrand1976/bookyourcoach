<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\ClubOpenSlot;
use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\SubscriptionRecurringSlot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle de cohérence du planning d'un club pour un jour donné (veille envoyée la veille aux responsables).
 *
 * Critique : créneau au-delà de sa capacité, enseignant ou élève à deux endroits en même temps,
 * enseignant inactif dans le club, cours généré sans série active, cours recréé après suppression.
 * À vérifier : horaire hors des créneaux ou hors grille, plusieurs cours le même jour pour un élève
 * dont l'un généré automatiquement, cours un jour de congé.
 */
class PlanningConsistencyCheckService
{
    public const GENERATED_NOTE = 'Cours généré automatiquement depuis créneau récurrent';

    /**
     * @return array{date: string, lessons_count: int, counts: array{critical: int, warning: int}, issues: list<array{level: string, type: string, time: string, title: string, detail: string, lesson_ids: list<int>}>}
     */
    public function checkDay(Club $club, CarbonInterface $day): array
    {
        $ymd = $day->format('Y-m-d');

        /** @var Collection<int, Lesson> $lessons */
        $lessons = Lesson::query()
            ->where('club_id', $club->id)
            ->whereDate('start_time', $ymd)
            ->whereNotIn('status', ['cancelled'])
            ->with(['teacher.user:id,name', 'student:id,first_name,last_name'])
            ->orderBy('start_time')
            ->get();

        $issues = [];
        if ($lessons->isEmpty()) {
            return $this->report($ymd, 0, $issues);
        }

        $items = $lessons->map(fn (Lesson $l) => [
            'lesson' => $l,
            'start' => Carbon::parse($l->start_time),
            'end' => $l->end_time ? Carbon::parse($l->end_time) : Carbon::parse($l->start_time)->addMinutes(20),
        ])->values()->all();

        $openSlots = ClubOpenSlot::query()
            ->where('club_id', $club->id)
            ->where('day_of_week', (int) Carbon::parse($ymd)->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        $this->checkCapacity($items, $openSlots, $issues);
        $this->checkDoubleBookings($items, 'teacher_id', $issues);
        $this->checkDoubleBookings($items, 'student_id', $issues);
        $this->checkStudentSameDay($items, $issues);
        $this->checkOpenSlotGrid($items, $openSlots, $issues);
        $this->checkInactiveTeachers($club, $items, $issues);
        $this->checkGeneratedWithoutSeries($items, $ymd, $issues);
        $this->checkRecreatedAfterDeletion($club, $items, $issues);

        if (ClubClosureDay::query()->where('club_id', $club->id)->whereDate('closed_on', $ymd)->exists()) {
            $issues[] = $this->issue('warning', 'closure_day', '', 'Jour de congé du club',
                count($items).' cours restent planifiés un jour de congé.', array_map(fn ($i) => (int) $i['lesson']->id, $items));
        }

        usort($issues, fn ($a, $b) => [$a['level'] === 'critical' ? 0 : 1, $a['time']] <=> [$b['level'] === 'critical' ? 0 : 1, $b['time']]);

        return $this->report($ymd, count($items), $issues);
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkCapacity(array $items, Collection $openSlots, array &$issues): void
    {
        $reported = [];
        foreach ($items as $item) {
            $openSlot = $this->openSlotFor($item['start'], $openSlots);
            $max = $openSlot ? (int) $openSlot->max_slots : 0;
            if ($max <= 0) {
                continue;
            }
            $at = $item['start'];
            $concurrent = array_values(array_filter($items, fn ($o) => $o['start']->lte($at) && $o['end']->gt($at)));
            $key = $at->format('H:i');
            if (count($concurrent) > $max && ! isset($reported[$key])) {
                $reported[$key] = true;
                $issues[] = $this->issue('critical', 'capacity_exceeded', $key,
                    'Créneau surchargé : '.count($concurrent).' cours pour '.$max.' place(s)',
                    $this->describe($concurrent), array_map(fn ($o) => (int) $o['lesson']->id, $concurrent));
            }
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkDoubleBookings(array $items, string $field, array &$issues): void
    {
        $seen = [];
        foreach ($items as $i => $a) {
            $id = $a['lesson']->{$field};
            if (! $id) {
                continue;
            }
            $group = [$a];
            foreach ($items as $j => $b) {
                if ($j !== $i && (int) $b['lesson']->{$field} === (int) $id
                    && $a['start']->lt($b['end']) && $b['start']->lt($a['end'])) {
                    $group[] = $b;
                }
            }
            if (count($group) < 2) {
                continue;
            }
            $ids = array_map(fn ($g) => (int) $g['lesson']->id, $group);
            sort($ids);
            $key = implode(',', $ids);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $who = $field === 'teacher_id' ? $this->teacherName($a['lesson']) : $this->studentName($a['lesson']);
            $issues[] = $this->issue('critical', $field === 'teacher_id' ? 'teacher_double_booking' : 'student_double_booking',
                $a['start']->format('H:i'),
                ($field === 'teacher_id' ? 'Enseignant' : 'Élève')." à deux cours en même temps : {$who}",
                $this->describe($group), $ids);
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkStudentSameDay(array $items, array &$issues): void
    {
        $byStudent = [];
        foreach ($items as $item) {
            if ($item['lesson']->student_id) {
                $byStudent[(int) $item['lesson']->student_id][] = $item;
            }
        }
        foreach ($byStudent as $group) {
            if (count($group) < 2) {
                continue;
            }
            // Les chevauchements sont déjà signalés en critique.
            $overlap = false;
            foreach ($group as $i => $a) {
                foreach ($group as $j => $b) {
                    if ($i < $j && $a['start']->lt($b['end']) && $b['start']->lt($a['end'])) {
                        $overlap = true;
                    }
                }
            }
            // Une double séance planifiée à la main est voulue ; on ne signale que si la génération automatique y est mêlée.
            $hasGenerated = array_filter($group, fn ($g) => $g['lesson']->notes === self::GENERATED_NOTE) !== [];
            if ($overlap || ! $hasGenerated) {
                continue;
            }
            $issues[] = $this->issue('warning', 'student_several_lessons', $group[0]['start']->format('H:i'),
                'Plusieurs cours le même jour : '.$this->studentName($group[0]['lesson']),
                $this->describe($group), array_map(fn ($g) => (int) $g['lesson']->id, $group));
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkOpenSlotGrid(array $items, Collection $openSlots, array &$issues): void
    {
        if ($openSlots->isEmpty()) {
            return;
        }
        foreach ($items as $item) {
            $openSlot = $this->openSlotFor($item['start'], $openSlots);
            if (! $openSlot) {
                $issues[] = $this->issue('warning', 'outside_open_slot', $item['start']->format('H:i'),
                    'Cours en dehors des créneaux ouverts du club', $this->describe([$item]), [(int) $item['lesson']->id]);

                continue;
            }
            $duration = (int) ($openSlot->duration ?? 0);
            if ($duration <= 0) {
                continue;
            }
            $slotStart = Carbon::parse($item['start']->format('Y-m-d').' '.$this->his($openSlot->start_time));
            $offset = (int) $slotStart->diffInMinutes($item['start'], true);
            if ($offset % $duration !== 0) {
                $issues[] = $this->issue('warning', 'off_grid', $item['start']->format('H:i'),
                    "Horaire hors grille du créneau (pas de {$duration} min depuis ".substr($this->his($openSlot->start_time), 0, 5).')',
                    $this->describe([$item]), [(int) $item['lesson']->id]);
            }
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkInactiveTeachers(Club $club, array $items, array &$issues): void
    {
        $teacherIds = array_values(array_unique(array_filter(array_map(fn ($i) => (int) $i['lesson']->teacher_id, $items))));
        if ($teacherIds === []) {
            return;
        }
        $active = DB::table('club_teachers')
            ->where('club_id', $club->id)
            ->whereIn('teacher_id', $teacherIds)
            ->where('is_active', true)
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        foreach (array_diff($teacherIds, $active) as $teacherId) {
            $group = array_values(array_filter($items, fn ($i) => (int) $i['lesson']->teacher_id === $teacherId));
            $issues[] = $this->issue('critical', 'inactive_teacher', $group[0]['start']->format('H:i'),
                'Enseignant inactif dans le club : '.$this->teacherName($group[0]['lesson']),
                $this->describe($group), array_map(fn ($g) => (int) $g['lesson']->id, $group));
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkGeneratedWithoutSeries(array $items, string $ymd, array &$issues): void
    {
        foreach ($items as $item) {
            $lesson = $item['lesson'];
            if (! $this->isUntouchedGenerated($lesson)) {
                continue;
            }
            $hasSeries = SubscriptionRecurringSlot::query()
                ->where('status', 'active')
                ->where('student_id', $lesson->student_id)
                ->where('teacher_id', $lesson->teacher_id)
                ->where('day_of_week', (int) $item['start']->dayOfWeek)
                ->whereTime('start_time', $item['start']->format('H:i:s'))
                ->whereDate('start_date', '<=', $ymd)
                ->whereDate('end_date', '>=', $ymd)
                ->exists();
            if (! $hasSeries) {
                $issues[] = $this->issue('critical', 'generated_without_series', $item['start']->format('H:i'),
                    'Cours généré automatiquement sans série active correspondante',
                    $this->describe([$item]), [(int) $lesson->id]);
            }
        }
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $items
     */
    private function checkRecreatedAfterDeletion(Club $club, array $items, array &$issues): void
    {
        $studentIds = array_values(array_unique(array_filter(array_map(fn ($i) => (int) $i['lesson']->student_id, $items))));
        if ($studentIds === []) {
            return;
        }
        $deletions = LessonActionLog::query()
            ->where('club_id', $club->id)
            ->whereIn('action', [LessonActionLog::ACTION_DELETED, LessonActionLog::ACTION_DELETED_CASCADE])
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'teacher_id', 'meta', 'created_at']);

        foreach ($items as $item) {
            $lesson = $item['lesson'];
            // Un cours replanifié à la main au même horaire est voulu : seule la génération est suspecte.
            if ($lesson->notes !== self::GENERATED_NOTE) {
                continue;
            }
            $startKey = $item['start']->format('Y-m-d\TH:i');
            $deletedBefore = $deletions->first(function (LessonActionLog $log) use ($lesson, $startKey) {
                $meta = is_array($log->meta) ? $log->meta : (json_decode((string) $log->meta, true) ?: []);

                // Même élève, même enseignant, même horaire : un déplacement vers un autre enseignant est voulu.
                $teacherId = $log->teacher_id ?? ($meta['teacher_id'] ?? null);

                return (int) $log->student_id === (int) $lesson->student_id
                    && (int) $teacherId === (int) $lesson->teacher_id
                    && substr((string) ($meta['lesson_start_time'] ?? ''), 0, 16) === $startKey
                    && Carbon::parse($log->created_at)->lt(Carbon::parse($lesson->created_at));
            });
            // Série recréée par le club après la suppression : la réapparition est voulue.
            if ($deletedBefore && SubscriptionRecurringSlot::query()
                ->where('status', 'active')
                ->where('student_id', $lesson->student_id)
                ->where('teacher_id', $lesson->teacher_id)
                ->where('day_of_week', (int) $item['start']->dayOfWeek)
                ->whereTime('start_time', $item['start']->format('H:i:s'))
                ->where('created_at', '>', $deletedBefore->created_at)
                ->exists()) {
                $deletedBefore = null;
            }
            if ($deletedBefore) {
                $issues[] = $this->issue('critical', 'recreated_after_deletion', $item['start']->format('H:i'),
                    'Cours réapparu après une suppression par le club',
                    $this->describe([$item]).' — supprimé le '.Carbon::parse($deletedBefore->created_at)->format('d/m/Y H:i'),
                    [(int) $lesson->id]);
            }
        }
    }

    /**
     * Cours généré et resté tel quel : un cours généré puis déplacé à la main garde la note
     * de génération mais n'a plus à correspondre à sa série.
     */
    private function isUntouchedGenerated(Lesson $lesson): bool
    {
        if ($lesson->notes !== self::GENERATED_NOTE || ! $lesson->student_id || ! $lesson->teacher_id) {
            return false;
        }
        if (abs(Carbon::parse($lesson->updated_at)->diffInSeconds(Carbon::parse($lesson->created_at))) > 60) {
            return false;
        }

        return ! DB::table('lesson_movement_histories')->where('lesson_id', $lesson->id)->exists();
    }

    private function openSlotFor(Carbon $start, Collection $openSlots): ?ClubOpenSlot
    {
        $time = $start->format('H:i:s');

        return $openSlots->first(fn (ClubOpenSlot $os) => $this->his($os->start_time) <= $time && $this->his($os->end_time) > $time);
    }

    private function his(mixed $time): string
    {
        $t = $time instanceof CarbonInterface ? $time->format('H:i:s') : (string) $time;

        return strlen($t) === 5 ? $t.':00' : substr($t, 0, 8);
    }

    /**
     * @param  list<array{lesson: Lesson, start: Carbon, end: Carbon}>  $group
     */
    private function describe(array $group): string
    {
        return implode(' · ', array_map(
            fn ($g) => $g['start']->format('H:i').' '.$this->studentName($g['lesson']).' avec '.$this->teacherName($g['lesson']).' (#'.$g['lesson']->id.')',
            $group
        ));
    }

    private function studentName(Lesson $lesson): string
    {
        $name = trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? ''));

        return $name !== '' ? $name : 'élève #'.$lesson->student_id;
    }

    private function teacherName(Lesson $lesson): string
    {
        return $lesson->teacher?->user?->name ?? 'enseignant #'.$lesson->teacher_id;
    }

    /**
     * @param  list<int>  $lessonIds
     * @return array{level: string, type: string, time: string, title: string, detail: string, lesson_ids: list<int>}
     */
    private function issue(string $level, string $type, string $time, string $title, string $detail, array $lessonIds): array
    {
        return compact('level', 'type', 'time', 'title', 'detail') + ['lesson_ids' => array_values($lessonIds)];
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    private function report(string $ymd, int $count, array $issues): array
    {
        return [
            'date' => $ymd,
            'lessons_count' => $count,
            'counts' => [
                'critical' => count(array_filter($issues, fn ($i) => $i['level'] === 'critical')),
                'warning' => count(array_filter($issues, fn ($i) => $i['level'] === 'warning')),
            ],
            'issues' => $issues,
        ];
    }
}
