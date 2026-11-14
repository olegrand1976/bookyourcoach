<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Rappels de la veille au soir : prépare, pour un club et un jour, ce que reçoivent
 * - chaque moniteur qui donne cours (ses cours ; alerte « ouverture » s'il est seul au premier horaire) ;
 * - chaque adresse élève (foyer compris) : cours du lendemain, heure et moniteur.
 *
 * Rien n'est préparé un jour de congé du club. Les moniteurs inactifs dans le club ne reçoivent rien
 * (la veille planning de 18:00 signale déjà ces cours aux responsables).
 */
class LessonEveReminderService
{
    /**
     * @return array{
     *     date: string,
     *     closed: bool,
     *     lessons_count: int,
     *     first_time: ?string,
     *     teachers: list<array{teacher_id: int, email: string, name: string, opens_alone: bool, lessons: list<array{lesson_id: int, start: string, end: string, students: string}>}>,
     *     students: list<array{email: string, name: string, lessons: list<array{lesson_id: int, student: string, start: string, end: string, teacher: string}>}>,
     *     skipped: list<string>
     * }
     */
    public function buildDay(Club $club, CarbonInterface $day): array
    {
        $ymd = $day->format('Y-m-d');
        $out = ['date' => $ymd, 'closed' => false, 'lessons_count' => 0, 'first_time' => null, 'teachers' => [], 'students' => [], 'skipped' => []];

        if (ClubClosureDay::query()->where('club_id', $club->id)->whereDate('closed_on', $ymd)->exists()) {
            $out['closed'] = true;

            return $out;
        }

        $lessons = Lesson::query()
            ->where('club_id', $club->id)
            ->whereDate('start_time', $ymd)
            ->where('status', '!=', 'cancelled')
            ->with(['teacher.user', 'student.user', 'students.user'])
            ->orderBy('start_time')
            ->get();
        $out['lessons_count'] = $lessons->count();
        if ($lessons->isEmpty()) {
            return $out;
        }

        // Ouverture : au premier horaire de la journée, un seul cours prévu → son moniteur est seul pour ouvrir.
        $firstStart = Carbon::parse($lessons->first()->start_time)->format('H:i');
        $atOpening = $lessons->filter(fn (Lesson $l) => Carbon::parse($l->start_time)->format('H:i') === $firstStart);
        $aloneTeacherId = $atOpening->count() === 1 ? (int) $atOpening->first()->teacher_id : null;
        $out['first_time'] = $firstStart;

        $activeTeacherIds = DB::table('club_teachers')
            ->where('club_id', $club->id)
            ->whereIn('teacher_id', $lessons->pluck('teacher_id')->filter()->unique()->all())
            ->where('is_active', true)
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $teachers = [];
        $students = [];
        foreach ($lessons as $lesson) {
            $start = Carbon::parse($lesson->start_time)->format('H:i');
            $end = $lesson->end_time ? Carbon::parse($lesson->end_time)->format('H:i') : '';
            $participants = $this->participants($lesson);
            $teacherName = $this->teacherName($lesson->teacher);

            $teacherId = (int) $lesson->teacher_id;
            if ($teacherId > 0) {
                $email = $this->validEmail($lesson->teacher?->user?->email);
                if (! in_array($teacherId, $activeTeacherIds, true)) {
                    $out['skipped'][] = "cours #{$lesson->id} : moniteur #{$teacherId} inactif dans le club";
                } elseif ($email === null) {
                    $out['skipped'][] = "cours #{$lesson->id} : moniteur #{$teacherId} sans e-mail valide";
                } else {
                    $teachers[$teacherId] ??= [
                        'teacher_id' => $teacherId,
                        'email' => $email,
                        'name' => $teacherName,
                        'opens_alone' => $teacherId === $aloneTeacherId,
                        'lessons' => [],
                    ];
                    $teachers[$teacherId]['lessons'][] = [
                        'lesson_id' => (int) $lesson->id,
                        'start' => $start,
                        'end' => $end,
                        'students' => implode(', ', array_map(fn (Student $s) => $this->studentName($s), $participants)) ?: '—',
                    ];
                }
            }

            foreach ($participants as $student) {
                if ($student->notifications_enabled === false) {
                    continue;
                }
                $email = $this->validEmail($student->user?->email);
                if ($email === null) {
                    $out['skipped'][] = "cours #{$lesson->id} : élève #{$student->id} sans e-mail valide";

                    continue;
                }
                $key = strtolower($email);
                $students[$key] ??= ['email' => $email, 'name' => $this->greetingName($student), 'lessons' => []];
                $students[$key]['lessons'][] = [
                    'lesson_id' => (int) $lesson->id,
                    'student' => $this->studentName($student),
                    'start' => $start,
                    'end' => $end,
                    'teacher' => $teacherName,
                ];
            }
        }

        $out['teachers'] = array_values($teachers);
        $out['students'] = array_values($students);

        return $out;
    }

    /**
     * Élève principal + participants du cours collectif (hors participants annulés), sans doublon.
     *
     * @return list<Student>
     */
    private function participants(Lesson $lesson): array
    {
        $byId = [];
        if ($lesson->student) {
            $byId[(int) $lesson->student->id] = $lesson->student;
        }
        foreach ($lesson->students as $student) {
            if (($student->pivot->status ?? null) === 'cancelled') {
                continue;
            }
            $byId[(int) $student->id] ??= $student;
        }

        return array_values($byId);
    }

    private function teacherName(?Teacher $teacher): string
    {
        $user = $teacher?->user;
        if (! $user) {
            return 'votre moniteur';
        }
        $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $full !== '' ? $full : (string) $user->name;
    }

    private function studentName(Student $student): string
    {
        $full = trim(($student->first_name ?? '').' '.($student->last_name ?? ''));

        return $full !== '' ? $full : ($student->user?->name ?? 'Élève #'.$student->id);
    }

    private function greetingName(Student $student): string
    {
        $user = $student->user;
        $first = trim((string) ($user?->first_name ?? ''));

        return $first !== '' ? $first : trim((string) ($user?->name ?? ''));
    }

    private function validEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
