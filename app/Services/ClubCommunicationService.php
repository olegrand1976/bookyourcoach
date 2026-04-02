<?php

namespace App\Services;

use App\Mail\ClubGeneralCommunicationMail;
use App\Models\Club;
use App\Models\ClubCommunicationLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class ClubCommunicationService
{
    /**
     * @return array{emails: list<string>}
     */
    public function resolveRecipientEmails(Club $club, string $audience): array
    {
        $emails = collect();

        if (in_array($audience, ['teachers', 'both'], true)) {
            $club->activeTeachers()->with('user')->get()->each(function ($teacher) use ($emails) {
                $email = $teacher->user?->email;
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails->push(strtolower(trim($email)));
                }
            });
        }

        if (in_array($audience, ['students', 'both'], true)) {
            $club->activeStudents()->with('user')->get()->each(function ($student) use ($emails) {
                $email = $student->user?->email;
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails->push(strtolower(trim($email)));
                }
            });
        }

        return [
            'emails' => $emails->unique()->values()->all(),
        ];
    }

    /**
     * Liste des contacts pour sélection ciblée (email valide uniquement).
     *
     * @return array{teachers: list<array{id:int,name:string,email:string}>, students: list<array{id:int,name:string,email:string}>}
     */
    public function listContacts(Club $club): array
    {
        $teachers = $club->activeTeachers()
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(function ($teacher) {
                $email = $teacher->user?->email;
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user?->name ?? 'Enseignant #'.$teacher->id,
                    'email' => strtolower(trim($email)),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $students = $club->activeStudents()
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(function ($student) {
                $email = $student->user?->email;
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return [
                    'id' => $student->id,
                    'name' => $student->user?->name ?? 'Élève #'.$student->id,
                    'email' => strtolower(trim($email)),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'teachers' => $teachers,
            'students' => $students,
        ];
    }

    /**
     * Prépare l’envoi : emails uniques, compteurs enseignants / élèves, métadonnées pour le journal.
     *
     * @param  list<int>|null  $teacherIds
     * @param  list<int>|null  $studentIds
     * @return array{
     *   emails: list<string>,
     *   teacher_recipient_count: int,
     *   student_recipient_count: int,
     *   audience: string,
     *   selection_mode: string,
     *   selected_teacher_ids: list<int>|null,
     *   selected_student_ids: list<int>|null
     * }
     */
    public function resolveSendContext(
        Club $club,
        string $selectionMode,
        ?string $audience,
        ?array $teacherIds,
        ?array $studentIds
    ): array {
        $teacherIds = array_values(array_unique(array_map('intval', $teacherIds ?? [])));
        $studentIds = array_values(array_unique(array_map('intval', $studentIds ?? [])));

        if ($selectionMode === 'all') {
            if (!in_array($audience, ['teachers', 'students', 'both'], true)) {
                throw new InvalidArgumentException('Audience invalide.');
            }

            $teacherSlots = 0;
            $studentSlots = 0;
            $emailOrder = [];

            if (in_array($audience, ['teachers', 'both'], true)) {
                $club->activeTeachers()->with('user')->orderBy('id')->get()->each(function ($teacher) use (&$teacherSlots, &$emailOrder) {
                    $email = $teacher->user?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $teacherSlots++;
                        $emailOrder[] = strtolower(trim($email));
                    }
                });
            }

            if (in_array($audience, ['students', 'both'], true)) {
                $club->activeStudents()->with('user')->orderBy('id')->get()->each(function ($student) use (&$studentSlots, &$emailOrder) {
                    $email = $student->user?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $studentSlots++;
                        $emailOrder[] = strtolower(trim($email));
                    }
                });
            }

            $uniqueEmails = Collection::make($emailOrder)->unique()->values()->all();

            return [
                'emails' => $uniqueEmails,
                'teacher_recipient_count' => $teacherSlots,
                'student_recipient_count' => $studentSlots,
                'audience' => $audience,
                'selection_mode' => 'all',
                'selected_teacher_ids' => null,
                'selected_student_ids' => null,
            ];
        }

        if ($selectionMode !== 'selected') {
            throw new InvalidArgumentException('Mode de sélection invalide.');
        }

        if ($teacherIds === [] && $studentIds === []) {
            throw new InvalidArgumentException('Sélectionnez au moins un destinataire.');
        }

        $this->assertTeacherIdsBelongToClub($club, $teacherIds);
        $this->assertStudentIdsBelongToClub($club, $studentIds);

        $teacherSlots = 0;
        $studentSlots = 0;
        $emailOrder = [];
        $loggedTeacherIds = [];
        $loggedStudentIds = [];

        if ($teacherIds !== []) {
            $club->activeTeachers()
                ->whereIn('teachers.id', $teacherIds)
                ->with('user')
                ->orderBy('id')
                ->get()
                ->each(function ($teacher) use (&$teacherSlots, &$emailOrder, &$loggedTeacherIds) {
                    $email = $teacher->user?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $teacherSlots++;
                        $emailOrder[] = strtolower(trim($email));
                        $loggedTeacherIds[] = $teacher->id;
                    }
                });
        }

        if ($studentIds !== []) {
            $club->activeStudents()
                ->whereIn('students.id', $studentIds)
                ->with('user')
                ->orderBy('id')
                ->get()
                ->each(function ($student) use (&$studentSlots, &$emailOrder, &$loggedStudentIds) {
                    $email = $student->user?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $studentSlots++;
                        $emailOrder[] = strtolower(trim($email));
                        $loggedStudentIds[] = $student->id;
                    }
                });
        }

        $uniqueEmails = Collection::make($emailOrder)->unique()->values()->all();

        $audienceForLog = 'both';
        if ($teacherSlots > 0 && $studentSlots === 0) {
            $audienceForLog = 'teachers';
        } elseif ($studentSlots > 0 && $teacherSlots === 0) {
            $audienceForLog = 'students';
        }

        return [
            'emails' => $uniqueEmails,
            'teacher_recipient_count' => $teacherSlots,
            'student_recipient_count' => $studentSlots,
            'audience' => $audienceForLog,
            'selection_mode' => 'selected',
            'selected_teacher_ids' => $teacherIds === [] ? null : $teacherIds,
            'selected_student_ids' => $studentIds === [] ? null : $studentIds,
        ];
    }

    /**
     * @param  list<int>  $teacherIds
     * @param  list<int>  $studentIds
     */
    public function assertTeacherIdsBelongToClub(Club $club, array $teacherIds): void
    {
        if ($teacherIds === []) {
            return;
        }

        $found = $club->teachers()
            ->wherePivot('is_active', true)
            ->whereKey($teacherIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $expected = $teacherIds;
        sort($expected);

        if ($found !== array_values($expected)) {
            throw new InvalidArgumentException('Un ou plusieurs enseignants ne sont pas actifs dans ce club.');
        }
    }

    /**
     * @param  list<int>  $studentIds
     */
    public function assertStudentIdsBelongToClub(Club $club, array $studentIds): void
    {
        if ($studentIds === []) {
            return;
        }

        $found = $club->students()
            ->wherePivot('is_active', true)
            ->whereKey($studentIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $expected = $studentIds;
        sort($expected);

        if ($found !== array_values($expected)) {
            throw new InvalidArgumentException('Un ou plusieurs élèves ne sont pas actifs dans ce club.');
        }
    }

    /**
     * @param  array{
     *   emails: list<string>,
     *   teacher_recipient_count: int,
     *   student_recipient_count: int,
     *   audience: string,
     *   selection_mode: string,
     *   selected_teacher_ids: list<int>|null,
     *   selected_student_ids: list<int>|null
     * }  $context
     * @return array{recipient_count: int, sent_count: int, failed_count: int}
     */
    public function deliverAndLog(
        Club $club,
        User $sender,
        array $context,
        string $subject,
        string $bodyPlain
    ): array {
        $emails = $context['emails'];
        $recipientCount = count($emails);
        $replyTo = $club->email;

        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new ClubGeneralCommunicationMail(
                    $club->name,
                    $subject,
                    $bodyPlain,
                    $replyTo
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Club communication email failed', [
                    'club_id' => $club->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        ClubCommunicationLog::create([
            'club_id' => $club->id,
            'sent_by_user_id' => $sender->id,
            'audience' => $context['audience'],
            'selection_mode' => $context['selection_mode'],
            'selected_teacher_ids' => $context['selected_teacher_ids'],
            'selected_student_ids' => $context['selected_student_ids'],
            'subject' => $subject,
            'body' => $bodyPlain,
            'recipient_count' => $recipientCount,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'teacher_recipient_count' => $context['teacher_recipient_count'],
            'student_recipient_count' => $context['student_recipient_count'],
        ]);

        return [
            'recipient_count' => $recipientCount,
            'sent_count' => $sent,
            'failed_count' => $failed,
        ];
    }

    /**
     * @return array{teachers_with_email: int, students_with_email: int, unique_total_for_both: int}
     */
    public function recipientCounts(Club $club): array
    {
        $teachers = $this->resolveRecipientEmails($club, 'teachers');
        $students = $this->resolveRecipientEmails($club, 'students');
        $both = $this->resolveRecipientEmails($club, 'both');

        return [
            'teachers_with_email' => count($teachers['emails']),
            'students_with_email' => count($students['emails']),
            'unique_total_for_both' => count($both['emails']),
        ];
    }

    /**
     * Historique filtré : envois ayant ciblé au moins un enseignant / un élève.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateHistory(Club $club, string $scope, int $perPage = 15)
    {
        $query = ClubCommunicationLog::query()
            ->where('club_id', $club->id)
            ->with(['sentBy:id,name,email'])
            ->orderByDesc('created_at');

        if ($scope === 'teachers') {
            $query->where(function ($q) {
                $q->where('teacher_recipient_count', '>', 0)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('teacher_recipient_count')
                            ->whereIn('audience', ['teachers', 'both']);
                    });
            });
        } else {
            $query->where(function ($q) {
                $q->where('student_recipient_count', '>', 0)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('student_recipient_count')
                            ->whereIn('audience', ['students', 'both']);
                    });
            });
        }

        return $query->paginate($perPage);
    }
}
