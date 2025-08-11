<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Notifications\LessonReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLessonReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Lesson $lesson
    ) {}

    public function handle(): void
    {
        // Vérifier que le cours n'a pas été annulé
        if ($this->lesson->status === 'cancelled') {
            return;
        }

        // Envoyer le rappel à l'élève
        $this->lesson->student->user->notify(new LessonReminderNotification($this->lesson));

        // Envoyer le rappel à l'enseignant
        $this->lesson->teacher->user->notify(new LessonReminderNotification($this->lesson));
    }
}
