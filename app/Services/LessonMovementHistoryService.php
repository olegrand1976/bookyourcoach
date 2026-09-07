<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonMovementHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class LessonMovementHistoryService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Lesson $lesson,
        string $event,
        ?User $performedBy = null,
        ?string $performedByRole = null,
        ?int $oldTeacherId = null,
        ?int $newTeacherId = null,
        mixed $oldStartTime = null,
        mixed $newStartTime = null,
        mixed $oldEndTime = null,
        mixed $newEndTime = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $updateScope = null,
        ?int $cascadeFromLessonId = null,
        array $meta = [],
    ): ?LessonMovementHistory {
        if (! Schema::hasTable('lesson_movement_histories') || $lesson->club_id === null) {
            return null;
        }

        $newTeacherId = $newTeacherId ?? ($lesson->teacher_id !== null ? (int) $lesson->teacher_id : null);
        $newStartTime = $newStartTime ?? $lesson->start_time;
        $newEndTime = $newEndTime ?? $lesson->end_time;
        $newStatus = $newStatus ?? $lesson->status;

        return LessonMovementHistory::create([
            'club_id' => (int) $lesson->club_id,
            'lesson_id' => $lesson->id,
            'event' => $event,
            'update_scope' => $updateScope,
            'cascade_from_lesson_id' => $cascadeFromLessonId,
            'old_teacher_id' => $oldTeacherId,
            'new_teacher_id' => $newTeacherId,
            'old_start_time' => $this->toDateTimeString($oldStartTime),
            'new_start_time' => $this->toDateTimeString($newStartTime),
            'old_end_time' => $this->toDateTimeString($oldEndTime),
            'new_end_time' => $this->toDateTimeString($newEndTime),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'student_id' => $lesson->student_id,
            'performed_by_user_id' => $performedBy?->id,
            'performed_by_role' => $performedByRole ?? $performedBy?->role,
            'meta' => $meta !== [] ? $meta : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Enregistre un mouvement à partir d'un snapshot « avant » et de l'état courant.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $meta
     */
    public function recordChange(
        Lesson $lesson,
        array $before,
        string $event,
        ?User $performedBy = null,
        ?string $performedByRole = null,
        ?string $updateScope = null,
        ?int $cascadeFromLessonId = null,
        array $meta = [],
    ): ?LessonMovementHistory {
        $oldTeacherId = isset($before['teacher_id']) ? (int) $before['teacher_id'] : null;
        $newTeacherId = $lesson->teacher_id !== null ? (int) $lesson->teacher_id : null;
        $oldStart = $before['start_time'] ?? null;
        $oldEnd = $before['end_time'] ?? null;
        $oldStatus = isset($before['status']) ? (string) $before['status'] : null;

        $teacherChanged = $oldTeacherId !== $newTeacherId;
        $startChanged = $this->toDateTimeString($oldStart) !== $this->toDateTimeString($lesson->start_time);
        $endChanged = $this->toDateTimeString($oldEnd) !== $this->toDateTimeString($lesson->end_time);
        $statusChanged = $oldStatus !== $lesson->status;

        if (
            ! in_array($event, [
                LessonMovementHistory::EVENT_CREATED,
                LessonMovementHistory::EVENT_DELETED,
                LessonMovementHistory::EVENT_TEACHER_EXCEPTION_SKIPPED,
            ], true)
            && ! $teacherChanged
            && ! $startChanged
            && ! $endChanged
            && ! $statusChanged
        ) {
            return null;
        }

        if ($event === LessonMovementHistory::EVENT_UPDATED) {
            if ($teacherChanged && ($startChanged || $endChanged)) {
                $event = LessonMovementHistory::EVENT_MOVED;
            } elseif ($teacherChanged) {
                $event = LessonMovementHistory::EVENT_TEACHER_REASSIGNED;
            } elseif ($startChanged || $endChanged) {
                $event = LessonMovementHistory::EVENT_MOVED;
            }
        }

        return $this->record(
            $lesson,
            $event,
            $performedBy,
            $performedByRole,
            $oldTeacherId,
            $newTeacherId,
            $oldStart,
            $lesson->start_time,
            $oldEnd,
            $lesson->end_time,
            $oldStatus,
            $lesson->status,
            $updateScope,
            $cascadeFromLessonId,
            $meta,
        );
    }

    /**
     * @return array{teacher_id: int|null, start_time: mixed, end_time: mixed, status: string|null}
     */
    public function snapshot(Lesson $lesson): array
    {
        return [
            'teacher_id' => $lesson->teacher_id !== null ? (int) $lesson->teacher_id : null,
            'start_time' => $lesson->start_time,
            'end_time' => $lesson->end_time,
            'status' => $lesson->status,
        ];
    }

    private function toDateTimeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }
}
