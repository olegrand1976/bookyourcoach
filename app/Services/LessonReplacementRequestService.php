<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonReplacement;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LessonReplacementRequestService
{
    /**
     * Validate a single lesson for a replacement request. Returns a French error message or null if OK.
     */
    public function validateLessonForReplacement(
        Teacher $requestingTeacher,
        Lesson $lesson,
        Teacher $replacementTeacher
    ): ?string {
        if ($replacementTeacher->id === $requestingTeacher->id) {
            return 'Vous ne pouvez pas vous sélectionner comme remplaçant';
        }

        if ($lesson->teacher_id !== $requestingTeacher->id) {
            return 'Ce cours ne vous appartient pas';
        }

        if (Carbon::parse($lesson->start_time)->isPast()) {
            return 'Impossible de demander un remplacement pour un cours passé';
        }

        $existingReplacement = LessonReplacement::where('lesson_id', $lesson->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReplacement) {
            return 'Une demande de remplacement est déjà en attente pour ce cours';
        }

        if ($this->replacementTeacherHasConflict($replacementTeacher, $lesson)) {
            return 'Le professeur de remplacement n\'est pas disponible à cet horaire';
        }

        return null;
    }

    public function replacementTeacherHasConflict(Teacher $replacementTeacher, Lesson $lesson): bool
    {
        return Lesson::where('teacher_id', $replacementTeacher->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($lesson) {
                $query->whereBetween('start_time', [$lesson->start_time, $lesson->end_time])
                    ->orWhereBetween('end_time', [$lesson->start_time, $lesson->end_time])
                    ->orWhere(function ($q) use ($lesson) {
                        $q->where('start_time', '<=', $lesson->start_time)
                            ->where('end_time', '>=', $lesson->end_time);
                    });
            })
            ->exists();
    }

    /**
     * @param  array<int>  $lessonIds
     * @return array{error: string|null, lessons: Collection<int, Lesson>|null, clubId: int|null, replacementTeacher: Teacher|null}
     */
    public function validateBulkLessons(
        Teacher $requestingTeacher,
        array $lessonIds,
        int $replacementTeacherId
    ): array {
        $lessonIds = array_values(array_unique(array_map('intval', $lessonIds)));

        if ($replacementTeacherId === $requestingTeacher->id) {
            return ['error' => 'Vous ne pouvez pas vous sélectionner comme remplaçant', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        $replacementTeacher = Teacher::find($replacementTeacherId);
        if (!$replacementTeacher) {
            return ['error' => 'Enseignant remplaçant introuvable', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        $lessons = Lesson::whereIn('id', $lessonIds)
            ->orderBy('start_time')
            ->get();

        if ($lessons->count() !== count($lessonIds)) {
            return ['error' => 'Un ou plusieurs cours sont introuvables', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        $clubIds = $lessons->pluck('club_id')->filter()->unique()->values();

        if ($clubIds->isEmpty()) {
            return ['error' => 'Les cours sélectionnés doivent être rattachés à un club pour une demande groupée.', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        if ($clubIds->count() > 1) {
            return ['error' => 'Tous les cours doivent appartenir au même club.', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        $clubId = (int) $clubIds->first();

        if (!$replacementTeacher->clubs()->where('clubs.id', $clubId)->exists()) {
            return ['error' => 'L\'enseignant remplaçant doit être affilié au même club que les cours sélectionnés.', 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
        }

        foreach ($lessons as $lesson) {
            $msg = $this->validateLessonForReplacement($requestingTeacher, $lesson, $replacementTeacher);
            if ($msg !== null) {
                return ['error' => $msg, 'lessons' => null, 'clubId' => null, 'replacementTeacher' => null];
            }
        }

        return ['error' => null, 'lessons' => $lessons, 'clubId' => $clubId, 'replacementTeacher' => $replacementTeacher];
    }
}
