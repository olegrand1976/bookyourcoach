<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LessonDeletionService
{
    /**
     * Résout l'élève participant cible pour un cours (suppression / cascade).
     */
    public function resolveParticipantStudentId(Lesson $lesson): ?int
    {
        $lesson->loadMissing(['students', 'subscriptionInstances.students']);

        if ($lesson->student_id !== null) {
            return (int) $lesson->student_id;
        }

        $pivotStudents = $lesson->students;
        if ($pivotStudents->count() === 1) {
            return (int) $pivotStudents->first()->id;
        }

        if ($pivotStudents->count() > 1) {
            return null;
        }

        $instances = $lesson->subscriptionInstances;
        if ($instances->count() === 1) {
            $instanceStudents = $instances->first()->students;
            if ($instanceStudents->count() === 1) {
                return (int) $instanceStudents->first()->id;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     target_lesson: array<string, mixed>,
     *     affected_lessons: array<int, array<string, mixed>>,
     *     sibling_warnings: array<int, array<string, mixed>>,
     *     target_student_id: int|null,
     *     requires_student_selection: bool
     * }
     */
    public function previewDeletion(Lesson $lesson, string $cancelScope, string $action = 'delete'): array
    {
        $lesson->loadMissing([
            'student.user',
            'students.user',
            'teacher.user',
            'courseType',
            'subscriptionInstances.students.user',
        ]);

        $targetStudentId = $this->resolveParticipantStudentId($lesson);
        $lessons = $this->resolveLessonsToProcess($lesson, $cancelScope, $action, null, $targetStudentId);

        $affected = $lessons->map(fn (Lesson $l) => $this->serializeLessonSummary($l))->values()->all();

        return [
            'target_lesson' => $this->serializeLessonSummary($lesson),
            'affected_lessons' => $affected,
            'sibling_warnings' => $this->collectSiblingWarnings($lesson, $targetStudentId),
            'target_student_id' => $targetStudentId,
            'requires_student_selection' => $targetStudentId === null && $lesson->subscriptionInstances->isNotEmpty(),
        ];
    }

    /**
     * Cours à traiter pour cancel/delete (sans mutation).
     *
     * @param  array<int>|null  $explicitLessonIds
     * @return Collection<int, Lesson>
     */
    public function resolveLessonsToProcess(
        Lesson $lesson,
        string $cancelScope,
        string $action,
        ?array $explicitLessonIds = null,
        ?int $targetStudentId = null
    ): Collection {
        $targetStudentId = $targetStudentId ?? $this->resolveParticipantStudentId($lesson);

        if ($explicitLessonIds !== null && $explicitLessonIds !== []) {
            $ids = array_values(array_unique(array_map('intval', $explicitLessonIds)));

            return Lesson::query()
                ->where('club_id', $lesson->club_id)
                ->whereIn('id', $ids)
                ->with(['student.user', 'students.user', 'courseType'])
                ->orderBy('start_time')
                ->get();
        }

        if ($cancelScope === 'single') {
            return collect([$lesson]);
        }

        if ($targetStudentId === null) {
            return collect([$lesson]);
        }

        $lesson->loadMissing('subscriptionInstances');
        $collection = collect([$lesson]);

        if ($lesson->subscriptionInstances->isEmpty()) {
            return $collection;
        }

        $subscriptionInstance = $lesson->subscriptionInstances->first();
        $futureLessons = $this->queryFutureLessonsForSameSlot($lesson, $subscriptionInstance, $targetStudentId, $action);

        return $collection->merge($futureLessons)->unique('id')->values();
    }

    /**
     * Filtre les cours futurs d'une instance pour le même créneau et le même élève.
     *
     * @return Collection<int, Lesson>
     */
    public function queryFutureLessonsForSameSlot(
        Lesson $referenceLesson,
        SubscriptionInstance $subscriptionInstance,
        int $targetStudentId,
        string $action = 'delete'
    ): Collection {
        $lessonStartDateTime = Carbon::parse($referenceLesson->start_time);
        $lessonEndDateTime = Carbon::parse($referenceLesson->end_time ?? $referenceLesson->start_time);
        $lessonDayOfWeekCarbon = $lessonStartDateTime->dayOfWeek;
        $lessonDayOfWeekMySQL = $lessonDayOfWeekCarbon === 0 ? 1 : ($lessonDayOfWeekCarbon + 1);
        $lessonStartTime = $lessonStartDateTime->format('H:i:s');
        $lessonEndTime = $lessonEndDateTime->format('H:i:s');

        $query = $subscriptionInstance->lessons()
            ->where('lessons.start_time', '>', $lessonStartDateTime)
            ->where('lessons.id', '!=', $referenceLesson->id)
            ->when(
                DB::connection()->getDriverName() === 'sqlite',
                fn ($q) => $q->whereRaw(
                    "CAST(strftime('%w', lessons.start_time) AS INTEGER) = ?",
                    [$lessonDayOfWeekCarbon]
                ),
                fn ($q) => $q->whereRaw('DAYOFWEEK(lessons.start_time) = ?', [$lessonDayOfWeekMySQL])
            )
            ->whereRaw('TIME(lessons.start_time) = ?', [$lessonStartTime])
            ->whereRaw('TIME(lessons.end_time) = ?', [$lessonEndTime])
            ->where('lessons.club_id', $referenceLesson->club_id);

        $this->applyParticipantStudentFilter($query, $targetStudentId);

        if ($action === 'cancel') {
            if ($referenceLesson->status === 'cancelled') {
                $query->where('lessons.status', '=', 'cancelled');
            } else {
                $query->where('lessons.status', '!=', 'cancelled');
            }
        }

        return $query->with(['student.user', 'students.user', 'courseType'])->get();
    }

    /**
     * Applique le filtre élève (colonne ou pivot lesson_student).
     *
     * @param  Builder<Lesson>|Relation<Lesson, mixed>  $query
     */
    public function applyParticipantStudentFilter(Builder|Relation $query, int $targetStudentId): void
    {
        $query->where(function ($q) use ($targetStudentId) {
            $q->where('lessons.student_id', $targetStudentId)
                ->orWhereHas('students', function (Builder $sq) use ($targetStudentId) {
                    $sq->where('students.id', $targetStudentId);
                });
        });
    }

    /**
     * Autres cours même instance + même date calendaire, autre élève.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collectSiblingWarnings(Lesson $lesson, ?int $targetStudentId): array
    {
        $lesson->loadMissing(['subscriptionInstances.students.user', 'student.user', 'students.user']);

        if ($lesson->subscriptionInstances->isEmpty()) {
            return [];
        }

        $instance = $lesson->subscriptionInstances->first();
        $calendarDate = Carbon::parse($lesson->start_time)->toDateString();

        $siblings = $instance->lessons()
            ->where('lessons.id', '!=', $lesson->id)
            ->whereDate('lessons.start_time', $calendarDate)
            ->with(['student.user', 'students.user', 'courseType'])
            ->get()
            ->filter(function (Lesson $other) use ($targetStudentId) {
                if ($targetStudentId === null) {
                    return true;
                }

                $otherStudentId = $this->resolveParticipantStudentId($other);

                return $otherStudentId === null || $otherStudentId !== $targetStudentId;
            });

        return $siblings->map(fn (Lesson $l) => $this->serializeLessonSummary($l))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeLessonSummary(Lesson $lesson): array
    {
        $lesson->loadMissing(['student.user', 'students.user', 'teacher.user', 'courseType']);

        $studentName = null;
        $studentId = $lesson->student_id;

        if ($lesson->student) {
            $studentName = $lesson->student->user?->name
                ?? trim(($lesson->student->first_name ?? '') . ' ' . ($lesson->student->last_name ?? ''));
        } elseif ($lesson->students->isNotEmpty()) {
            $studentName = $lesson->students->map(function ($s) {
                return $s->user?->name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
            })->filter()->join(', ');
            $studentId = $studentId ?? $lesson->students->first()?->id;
        }

        return [
            'id' => $lesson->id,
            'start_time' => $lesson->start_time?->toIso8601String(),
            'end_time' => $lesson->end_time?->toIso8601String(),
            'status' => $lesson->status,
            'student_id' => $studentId,
            'student_name' => $studentName ?: 'Élève non défini',
            'teacher_name' => $lesson->teacher?->user?->name ?? 'Non assigné',
            'course_type_name' => $lesson->courseType?->name ?? 'Cours',
        ];
    }
}
