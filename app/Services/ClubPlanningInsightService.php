<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Lesson;
use App\Models\Student;
use App\Support\UnionFind;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClubPlanningInsightService
{
    /**
     * Build a JSON-serializable payload for AI and e-mail (target day = full calendar day in timezone).
     *
     * @return array<string, mixed>
     */
    public function buildPayload(Club $club, CarbonInterface $targetDay, string $timezone = 'Europe/Brussels'): array
    {
        $day = $targetDay->copy()->timezone($timezone)->startOfDay();
        $start = $day->copy();
        $end = $day->copy()->endOfDay();

        $lessons = Lesson::query()
            ->where('club_id', $club->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_time', [$start, $end])
            ->with([
                'teacher.user',
                'courseType',
                'location',
                'students' => function ($q) {
                    $q->wherePivot('status', '!=', 'cancelled');
                },
                'subscriptionInstances.students',
            ])
            ->orderBy('start_time')
            ->get();

        $participantIds = [];
        foreach ($lessons as $lesson) {
            if ($lesson->student_id) {
                $participantIds[(int) $lesson->student_id] = true;
            }
            foreach ($lesson->students as $student) {
                $participantIds[(int) $student->id] = true;
            }
            foreach ($lesson->subscriptionInstances as $instance) {
                foreach ($instance->students as $student) {
                    $participantIds[(int) $student->id] = true;
                }
            }
        }

        $participantIdList = array_map('intval', array_keys($participantIds));
        sort($participantIdList);

        $students = $participantIdList === []
            ? collect()
            : Student::query()->whereIn('id', $participantIdList)->get()->keyBy('id');

        $uf = new UnionFind($participantIdList);

        $familyLinks = DB::table('student_family_links')
            ->whereIn('primary_student_id', $participantIdList)
            ->whereIn('linked_student_id', $participantIdList)
            ->get(['primary_student_id', 'linked_student_id']);

        foreach ($familyLinks as $row) {
            $uf->union((int) $row->primary_student_id, (int) $row->linked_student_id);
        }

        $byLastName = [];
        foreach ($students as $student) {
            $norm = $this->normalizedLastName($student->last_name);
            if ($norm === '') {
                continue;
            }
            $byLastName[$norm][] = (int) $student->id;
        }
        foreach ($byLastName as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            $first = $ids[0];
            foreach (array_slice($ids, 1) as $otherId) {
                $uf->union($first, $otherId);
            }
        }

        foreach ($lessons as $lesson) {
            foreach ($lesson->subscriptionInstances as $instance) {
                $idsOnInstance = $instance->students
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => isset($participantIds[$id]))
                    ->values()
                    ->all();
                if (count($idsOnInstance) < 2) {
                    continue;
                }
                $first = $idsOnInstance[0];
                foreach (array_slice($idsOnInstance, 1) as $otherId) {
                    $uf->union($first, $otherId);
                }
            }
        }

        $roots = [];
        foreach ($participantIdList as $id) {
            $roots[$uf->find($id)][] = $id;
        }

        $familyGroups = [];
        $groupId = 1;
        foreach ($roots as $memberIds) {
            if (count($memberIds) < 2) {
                continue;
            }
            sort($memberIds);
            $labels = [];
            foreach ($memberIds as $mid) {
                $s = $students->get($mid);
                $labels[] = $s ? trim($s->first_name.' '.$s->last_name) : 'student #'.$mid;
            }
            $familyGroups[] = [
                'group_id' => $groupId++,
                'student_ids' => $memberIds,
                'student_labels' => $labels,
            ];
        }

        $lessonPayload = [];
        foreach ($lessons as $lesson) {
            $studentPayload = [];
            $seenStudent = [];
            $attachStudent = function (Student $s) use (&$studentPayload, &$seenStudent, $lesson) {
                $sid = (int) $s->id;
                if (isset($seenStudent[$sid])) {
                    return;
                }
                $seenStudent[$sid] = true;
                $pivot = $lesson->students->find($sid)?->pivot;
                $studentPayload[] = [
                    'id' => $sid,
                    'name' => trim($s->first_name.' '.$s->last_name),
                    'last_name_normalized' => $this->normalizedLastName($s->last_name),
                    'lesson_student_price' => $pivot && $pivot->price !== null ? (float) $pivot->price : null,
                ];
            };

            if ($lesson->student_id && $students->has($lesson->student_id)) {
                $attachStudent($students->get($lesson->student_id));
            }
            foreach ($lesson->students as $s) {
                if ($students->has($s->id)) {
                    $attachStudent($students->get($s->id));
                }
            }
            foreach ($lesson->subscriptionInstances as $instance) {
                foreach ($instance->students as $s) {
                    if ($students->has($s->id)) {
                        $attachStudent($students->get($s->id));
                    }
                }
            }

            $subIds = $lesson->subscriptionInstances->pluck('id')->values()->all();

            $teacherUser = $lesson->teacher?->user;
            $teacherLabel = $teacherUser
                ? trim(($teacherUser->first_name ?? '').' '.($teacherUser->last_name ?? ''))
                : null;
            if ($teacherLabel === '') {
                $teacherLabel = $lesson->teacher_id ? 'teacher #'.$lesson->teacher_id : null;
            }

            $durationMin = null;
            if ($lesson->start_time && $lesson->end_time) {
                $durationMin = $lesson->start_time->diffInMinutes($lesson->end_time);
            }

            $lessonPayload[] = [
                'id' => $lesson->id,
                'start_local' => $lesson->start_time?->timezone($timezone)->format('Y-m-d H:i'),
                'end_local' => $lesson->end_time?->timezone($timezone)->format('Y-m-d H:i'),
                'duration_minutes' => $durationMin,
                'course_type' => $lesson->courseType?->name,
                'location' => $lesson->location?->name,
                'teacher' => $teacherLabel,
                'price' => $lesson->price !== null ? (float) $lesson->price : null,
                'montant' => $lesson->montant !== null ? (float) $lesson->montant : null,
                'deduct_from_subscription' => (bool) $lesson->deduct_from_subscription,
                'payment_status' => $lesson->payment_status,
                'participant_count' => count($studentPayload),
                'student_ids' => array_column($studentPayload, 'id'),
                'students' => $studentPayload,
                'subscription_instance_ids' => $subIds,
            ];
        }

        return [
            'meta' => [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'target_date' => $day->toDateString(),
                'timezone' => $timezone,
            ],
            'family_constraint_groups' => $familyGroups,
            'family_rules_explainer' => implode(' ', [
                'Students in the same group must not be spread far apart in the daily schedule',
                '(same morning block / close time slots).',
                'Groups merge: explicit family links, same normalized last name among participants,',
                'or two or more participants sharing a subscription_instance_id on that day.',
            ]),
            'lessons' => $lessonPayload,
        ];
    }

    protected function normalizedLastName(?string $lastName): string
    {
        $t = trim((string) $lastName);

        return Str::lower($t);
    }
}
