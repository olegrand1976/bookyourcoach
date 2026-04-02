<?php

namespace App\Services;

use App\Mail\ClubGeneralCommunicationMail;
use App\Models\Club;
use App\Models\ClubCommunicationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * @return array{recipient_count: int, sent_count: int, failed_count: int}
     */
    public function sendGeneralCommunication(
        Club $club,
        User $sender,
        string $audience,
        string $subject,
        string $bodyPlain
    ): array {
        $resolved = $this->resolveRecipientEmails($club, $audience);
        $emails = $resolved['emails'];
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
            'audience' => $audience,
            'subject' => $subject,
            'body' => $bodyPlain,
            'recipient_count' => $recipientCount,
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);

        return [
            'recipient_count' => $recipientCount,
            'sent_count' => $sent,
            'failed_count' => $failed,
        ];
    }

    /**
     * Counts for UI (unique emails may differ from sum when same email in both groups).
     *
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
}
