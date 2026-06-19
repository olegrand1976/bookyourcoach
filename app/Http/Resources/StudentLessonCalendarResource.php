<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student planning / history: no subscription or recurrence linkage; notes stripped of internal lines.
 */
class StudentLessonCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Lesson $lesson */
        $lesson = $this->resource;

        $courseType = ($lesson->relationLoaded('courseType') && $lesson->courseType)
            ? [
                'id' => $lesson->courseType->id,
                'name' => $lesson->courseType->name,
            ]
            : null;

        $householdContext = $request->attributes->get('household_student_ids', []);
        $attribution = $this->resolveHouseholdAttribution(
            $lesson,
            is_array($householdContext) ? $householdContext : []
        );

        return [
            'id' => $lesson->id,
            'club_id' => $lesson->club_id,
            'teacher_id' => $lesson->teacher_id,
            'student_id' => $lesson->student_id,
            'course_type_id' => $lesson->course_type_id,
            'location_id' => $lesson->location_id,
            'start_time' => $lesson->start_time?->toIso8601String(),
            'end_time' => $lesson->end_time?->toIso8601String(),
            'status' => $lesson->status,
            'price' => $lesson->price,
            'payment_status' => $lesson->payment_status,
            'notes' => self::sanitizeNotesForStudent($lesson->notes),
            'teacher_feedback' => $lesson->teacher_feedback,
            'rating' => $lesson->rating,
            'review' => $lesson->review,
            'course_type' => $courseType,
            'courseType' => $courseType,
            'teacher' => ($lesson->relationLoaded('teacher') && $lesson->teacher)
                ? [
                    'id' => $lesson->teacher->id,
                    'user' => [
                        'name' => $lesson->teacher->user?->name,
                    ],
                ]
                : null,
            'location' => ($lesson->relationLoaded('location') && $lesson->location)
                ? [
                    'id' => $lesson->location->id,
                    'name' => $lesson->location->name,
                ]
                : null,
            'club' => ($lesson->relationLoaded('club') && $lesson->club)
                ? [
                    'id' => $lesson->club->id,
                    'name' => $lesson->club->name,
                ]
                : null,
            'student' => ($lesson->relationLoaded('student') && $lesson->student)
                ? [
                    'id' => $lesson->student->id,
                    'name' => $lesson->student->name,
                    'user' => [
                        'name' => $lesson->student->user?->name,
                    ],
                ]
                : null,
            'participants' => ($lesson->relationLoaded('students') && $lesson->students)
                ? $lesson->students->map(fn ($student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'user' => [
                        'name' => $student->user?->name,
                    ],
                ])->values()->all()
                : [],
            'cancellation_reason' => $lesson->cancellation_reason,
            'cancellation_count_in_subscription' => $lesson->cancellation_count_in_subscription,
            'cancellation_certificate_status' => $lesson->cancellation_certificate_status,
            'cancellation_certificate_rejection_reason' => $lesson->cancellation_certificate_rejection_reason,
            'household_student_ids' => $attribution['ids'],
            'household_students' => $attribution['students'],
        ];
    }

    /**
     * Détermine quel(s) élève(s) du foyer courant ce cours concerne.
     * Couvre les 3 sources : élève principal (student_id), participants pivot (lesson_student)
     * et bénéficiaires d'un abonnement lié au cours (subscriptionInstances.students).
     * Indispensable pour étiqueter, dans la vue « tous », un cours dont l'enfant du foyer
     * n'est que bénéficiaire d'abonnement (ni student_id ni participant pivot).
     *
     * @param  array<int>  $householdIds
     * @return array{ids: array<int>, students: array<int, array{id: int, name: ?string}>}
     */
    private function resolveHouseholdAttribution(Lesson $lesson, array $householdIds): array
    {
        if ($householdIds === []) {
            return ['ids' => [], 'students' => []];
        }

        $householdIds = array_map('intval', $householdIds);
        $candidates = [];

        if ($lesson->student_id) {
            $candidates[(int) $lesson->student_id] = ($lesson->relationLoaded('student') && $lesson->student)
                ? ($lesson->student->user?->name ?? $lesson->student->name)
                : null;
        }

        if ($lesson->relationLoaded('students')) {
            foreach ($lesson->students as $student) {
                $candidates[(int) $student->id] = $student->user?->name ?? $student->name;
            }
        }

        if ($lesson->relationLoaded('subscriptionInstances')) {
            foreach ($lesson->subscriptionInstances as $instance) {
                if (! $instance->relationLoaded('students')) {
                    continue;
                }
                foreach ($instance->students as $student) {
                    if (! array_key_exists((int) $student->id, $candidates)) {
                        $candidates[(int) $student->id] = $student->user?->name ?? $student->name;
                    }
                }
            }
        }

        $ids = array_values(array_filter(
            array_keys($candidates),
            static fn ($id) => in_array((int) $id, $householdIds, true)
        ));

        $students = array_map(
            static fn ($id) => ['id' => (int) $id, 'name' => $candidates[$id]],
            $ids
        );

        return ['ids' => array_map('intval', $ids), 'students' => $students];
    }

    public static function sanitizeNotesForStudent(?string $notes): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $notes);
        $kept = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }
            if (preg_match('/^\[(Annulé|Annulation|récurrence|Récurrence|Recurrence|Généré)/iu', $t)) {
                continue;
            }
            $kept[] = $line;
        }

        $out = trim(implode("\n", $kept));

        return $out === '' ? null : $out;
    }
}
