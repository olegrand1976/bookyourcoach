<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload léger pour la grille planning club/enseignant (pas de remaining_* ni nested templates).
 */
class PlanningLessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Lesson $lesson */
        $lesson = $this->resource;

        $mapSubscriptionIds = static function ($student): array {
            if (! $student || ! $student->relationLoaded('subscriptionInstances')) {
                return [];
            }

            return $student->subscriptionInstances
                ->map(static fn ($instance) => ['id' => $instance->id])
                ->values()
                ->all();
        };

        $mapStudent = static function ($student) use ($mapSubscriptionIds): ?array {
            if (! $student) {
                return null;
            }

            return [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'phone' => $student->phone,
                'user' => ($student->relationLoaded('user') && $student->user)
                    ? [
                        'id' => $student->user->id,
                        'name' => $student->user->name,
                        'phone' => $student->user->phone,
                    ]
                    : null,
                // IDs seuls pour badge 📋 (parité avec ancien hasActiveSubscription)
                'subscription_instances' => $mapSubscriptionIds($student),
            ];
        };

        $subscriptionInstances = [];
        if ($lesson->relationLoaded('subscriptionInstances')) {
            foreach ($lesson->subscriptionInstances as $instance) {
                $subscriptionInstances[] = ['id' => $instance->id];
            }
        }

        $teacher = null;
        if ($lesson->relationLoaded('teacher') && $lesson->teacher) {
            $teacher = [
                'id' => $lesson->teacher->id,
                'user_id' => $lesson->teacher->user_id,
                'color' => $lesson->teacher->color ?? null,
                'user' => ($lesson->teacher->relationLoaded('user') && $lesson->teacher->user)
                    ? [
                        'id' => $lesson->teacher->user->id,
                        'name' => $lesson->teacher->user->name,
                    ]
                    : null,
            ];
        }

        return [
            'id' => $lesson->id,
            'teacher_id' => $lesson->teacher_id,
            'student_id' => $lesson->student_id,
            'course_type_id' => $lesson->course_type_id,
            'location_id' => $lesson->location_id,
            'club_id' => $lesson->club_id,
            'start_time' => $lesson->start_time,
            'end_time' => $lesson->end_time,
            'status' => $lesson->status,
            'price' => $lesson->price,
            'notes' => $lesson->notes,
            'est_legacy' => $lesson->est_legacy,
            'deduct_from_subscription' => $lesson->deduct_from_subscription,
            'created_at' => $lesson->created_at,
            'updated_at' => $lesson->updated_at,
            'teacher' => $teacher,
            'student' => ($lesson->relationLoaded('student') && $lesson->student)
                ? $mapStudent($lesson->student)
                : null,
            'students' => ($lesson->relationLoaded('students'))
                ? $lesson->students->map($mapStudent)->filter()->values()->all()
                : [],
            'course_type' => ($lesson->relationLoaded('courseType') && $lesson->courseType)
                ? [
                    'id' => $lesson->courseType->id,
                    'name' => $lesson->courseType->name,
                ]
                : null,
            'courseType' => ($lesson->relationLoaded('courseType') && $lesson->courseType)
                ? [
                    'id' => $lesson->courseType->id,
                    'name' => $lesson->courseType->name,
                ]
                : null,
            'subscription_instances' => $subscriptionInstances,
            'lesson_recurring_slot' => ($lesson->relationLoaded('lessonRecurringSlot') && $lesson->lessonRecurringSlot)
                ? [
                    'id' => $lesson->lessonRecurringSlot->id,
                    'lesson_id' => $lesson->lessonRecurringSlot->lesson_id,
                    'recurring_slot_id' => $lesson->lessonRecurringSlot->recurring_slot_id,
                ]
                : null,
        ];
    }
}
