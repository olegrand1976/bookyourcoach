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
        ];
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
