<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonActionLog;
use App\Models\Student;
use App\Models\SubscriptionInstance;
use App\Models\User;
use Illuminate\Support\Collection;

class LessonActionLogService
{
    public function __construct(
        private readonly LessonDeletionService $lessonDeletionService,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(
        Lesson $lesson,
        string $action,
        ?User $performedBy = null,
        ?string $performedByRole = null,
        ?int $subscriptionInstanceId = null,
        ?int $studentId = null,
        array $meta = [],
    ): ?LessonActionLog {
        if ($lesson->club_id === null) {
            return null;
        }

        $lesson->loadMissing(['student.user', 'students.user', 'subscriptionInstances.subscription.template', 'courseType']);

        $studentId = $studentId ?? $this->lessonDeletionService->resolveParticipantStudentId($lesson);
        $subscriptionInstanceId = $subscriptionInstanceId ?? $this->resolvePrimarySubscriptionInstanceId($lesson);

        $snapshot = array_merge($this->lessonSnapshot($lesson), $meta);

        return LessonActionLog::create([
            'club_id' => (int) $lesson->club_id,
            'lesson_id' => $lesson->id,
            'student_id' => $studentId,
            'subscription_instance_id' => $subscriptionInstanceId,
            'performed_by_user_id' => $performedBy?->id,
            'performed_by_role' => $performedByRole ?? $performedBy?->role,
            'action' => $action,
            'meta' => $snapshot !== [] ? $snapshot : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function logForClub(
        int $clubId,
        string $action,
        ?User $performedBy = null,
        ?string $performedByRole = null,
        ?int $lessonId = null,
        ?int $studentId = null,
        ?int $subscriptionInstanceId = null,
        array $meta = [],
    ): LessonActionLog {
        return LessonActionLog::create([
            'club_id' => $clubId,
            'lesson_id' => $lessonId,
            'student_id' => $studentId,
            'subscription_instance_id' => $subscriptionInstanceId,
            'performed_by_user_id' => $performedBy?->id,
            'performed_by_role' => $performedByRole ?? $performedBy?->role,
            'action' => $action,
            'meta' => $meta !== [] ? $meta : null,
        ]);
    }

    public function resolvePrimarySubscriptionInstanceId(Lesson $lesson): ?int
    {
        $instances = $lesson->subscriptionInstances;
        if ($instances->isEmpty()) {
            return null;
        }

        return (int) $instances->first()->id;
    }

    public function studentDisplayName(?Student $student): ?string
    {
        if ($student === null) {
            return null;
        }

        if ($student->relationLoaded('user') && $student->user?->name) {
            return $student->user->name;
        }

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        return $name !== '' ? $name : null;
    }

    public function subscriptionDisplayLabel(?SubscriptionInstance $instance): ?string
    {
        if ($instance === null) {
            return null;
        }

        $instance->loadMissing('subscription.template');
        $templateName = $instance->subscription?->template?->name
            ?? $instance->subscription?->name;

        if ($templateName) {
            return $templateName . ' #' . $instance->id;
        }

        return 'Abonnement #' . $instance->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function lessonSnapshot(Lesson $lesson): array
    {
        $studentNames = $this->collectStudentNames($lesson);

        return [
            'lesson_start_time' => $lesson->start_time?->toIso8601String(),
            'lesson_end_time' => $lesson->end_time?->toIso8601String(),
            'lesson_status' => $lesson->status,
            'course_type_name' => $lesson->courseType?->name,
            'student_names' => $studentNames,
            'subscription_labels' => $lesson->subscriptionInstances
                ->map(fn (SubscriptionInstance $i) => $this->subscriptionDisplayLabel($i))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function collectStudentNames(Lesson $lesson): array
    {
        $names = new Collection;

        if ($lesson->student) {
            $label = $this->studentDisplayName($lesson->student);
            if ($label) {
                $names->push($label);
            }
        }

        foreach ($lesson->students ?? [] as $student) {
            $label = $this->studentDisplayName($student);
            if ($label && ! $names->contains($label)) {
                $names->push($label);
            }
        }

        return $names->values()->all();
    }
}
