<?php

namespace App\Services;

use App\Models\Lesson;
use App\Notifications\ClubNewBookingNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LessonBookingNotificationService
{
    /**
     * Notify club stakeholders (or generic club e-mail) that a booking was created — excludes teacher/student as they already receive LessonBookedNotification.
     */
    public function notifyClubStakeholdersOfNewBooking(Lesson $lesson): void
    {
        try {
            $lesson->loadMissing(['club', 'teacher.user', 'student.user', 'courseType', 'location']);

            $club = $lesson->club;
            if (! $club) {
                return;
            }

            $teacherUserId = $lesson->teacher?->user_id;
            $studentUserId = $lesson->student?->user_id;

            $stakeholders = $club->stakeholderUsersNotifiableByMail()->filter(function ($user) use ($teacherUserId, $studentUserId) {
                if ($teacherUserId && (int) $user->id === (int) $teacherUserId) {
                    return false;
                }
                if ($studentUserId && (int) $user->id === (int) $studentUserId) {
                    return false;
                }

                return true;
            });

            if ($stakeholders->isNotEmpty()) {
                Notification::send(
                    $stakeholders->unique('id')->values(),
                    new ClubNewBookingNotification($lesson)
                );

                return;
            }

            $this->notifyClubEmailFallback($club, $lesson);
        } catch (\Throwable $e) {
            Log::error('Erreur notification réservation (club): '.$e->getMessage(), [
                'lesson_id' => $lesson->id,
            ]);
        }
    }

    private function notifyClubEmailFallback(\App\Models\Club $club, Lesson $lesson): void
    {
        $email = $club->email ? trim((string) $club->email) : '';
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Notification::route('mail', $email)->notify(new ClubNewBookingNotification($lesson));
    }
}
