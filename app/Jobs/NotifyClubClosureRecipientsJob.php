<?php

namespace App\Jobs;

use App\Models\Club;
use App\Models\Lesson;
use App\Models\User;
use App\Support\FrontendUrl;
use App\Notifications\ClubClosureNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyClubClosureRecipientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $clubId,
        public string $dateYmd,
        /** closed = jour marqué fermé ; reopened = congés annulé */
        public string $kind = 'closed'
    ) {}

    public function handle(): void
    {
        $club = Club::query()->find($this->clubId);
        if (!$club) {
            Log::warning('NotifyClubClosureRecipientsJob: club not found', ['club_id' => $this->clubId]);

            return;
        }

        $lessons = Lesson::query()
            ->where('club_id', $this->clubId)
            ->whereDate('start_time', $this->dateYmd)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['teacher.user', 'student.user', 'students.user'])
            ->get();

        $users = collect();
        foreach ($lessons as $lesson) {
            if ($lesson->teacher?->user) {
                $users->push($lesson->teacher->user);
            }
            if ($lesson->student?->user) {
                $users->push($lesson->student->user);
            }
            foreach ($lesson->students as $student) {
                if ($student->user) {
                    $users->push($student->user);
                }
            }
        }

        $uniqueUsers = $users->filter(fn ($u) => $u instanceof User)->unique('id');

        $dateLabel = \Carbon\Carbon::parse($this->dateYmd)->translatedFormat('l j F Y');
        $clubBcc = $club->email ? trim((string) $club->email) : null;

        foreach ($uniqueUsers as $user) {
            if (!$user->email) {
                continue;
            }
            $afterLogin = match ($user->role) {
                User::ROLE_CLUB => '/club/planning',
                User::ROLE_TEACHER => '/teacher/dashboard',
                User::ROLE_STUDENT => '/student/dashboard',
                default => '/dashboard',
            };
            $planningUrl = FrontendUrl::login($afterLogin);

            $user->notify(new ClubClosureNotification(
                $club->name,
                $dateLabel,
                $planningUrl,
                $clubBcc ?: null,
                $this->kind
            ));
        }

        Log::info('Club closure notifications queued', [
            'club_id' => $this->clubId,
            'date' => $this->dateYmd,
            'recipient_count' => $uniqueUsers->count(),
        ]);
    }
}
