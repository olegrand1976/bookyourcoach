<?php

namespace App\Console\Commands;

use App\Mail\StudentEveReminderMail;
use App\Mail\TeacherEveReminderMail;
use App\Models\Club;
use App\Services\LessonEveReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Rappels de la veille au soir (21h30) : chaque moniteur reçoit ses cours du lendemain (alerte « ouverture »
 * s'il est seul au premier horaire) et chaque élève / foyer son cours, l'heure et le moniteur.
 * Un destinataire déjà servi pour ce jour n'est pas relancé (cache 36 h), sauf --force.
 */
class SendEveRemindersCommand extends Command
{
    protected $signature = 'lessons:send-eve-reminders
                            {--club= : Limiter à un club_id}
                            {--date= : Jour des cours (Y-m-d), défaut = demain}
                            {--only= : teachers ou students (défaut : les deux)}
                            {--dry-run : Afficher les destinataires sans envoyer}
                            {--force : Ignorer le cache d’idempotence}';

    protected $description = 'Rappels de la veille : cours du lendemain aux moniteurs (alerte ouverture) et aux élèves.';

    public function __construct(private readonly LessonEveReminderService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tz = (string) config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels');
        $day = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), $tz)->startOfDay()
            : now($tz)->addDay()->startOfDay();
        $only = (string) $this->option('only');
        if (! in_array($only, ['', 'teachers', 'students'], true)) {
            $this->error('--only accepte teachers ou students');

            return self::INVALID;
        }
        $dryRun = (bool) $this->option('dry-run');
        $dateLabel = $day->copy()->locale('fr')->isoFormat('dddd D MMMM');

        $clubs = Club::query()->where('is_active', true)
            ->when($this->option('club'), fn ($q) => $q->where('id', (int) $this->option('club')))
            ->orderBy('id')->get();

        $sent = ['teachers' => 0, 'students' => 0];
        foreach ($clubs as $club) {
            $plan = $this->service->buildDay($club, $day);
            if ($plan['closed'] || $plan['lessons_count'] === 0) {
                continue;
            }
            $this->info("club #{$club->id} {$club->name} — {$plan['date']} — {$plan['lessons_count']} cours, premier à {$plan['first_time']}");

            if ($only !== 'students') {
                foreach ($plan['teachers'] as $t) {
                    $times = implode(', ', array_column($t['lessons'], 'start'));
                    if ($dryRun) {
                        $this->line('  moniteur '.$t['name'].' <'.$this->mask($t['email']).'> : '.$times.($t['opens_alone'] ? ' — SEUL À L’OUVERTURE' : ''));

                        continue;
                    }
                    $sent['teachers'] += (int) $this->deliver('teacher', $club->id, $plan['date'], $t['email'],
                        new TeacherEveReminderMail($club->name, $dateLabel, $t));
                }
            }

            if ($only !== 'teachers') {
                foreach ($plan['students'] as $s) {
                    if ($dryRun) {
                        $lines = array_map(fn ($l) => "{$l['start']} {$l['student']} avec {$l['teacher']}", $s['lessons']);
                        $this->line('  élève <'.$this->mask($s['email']).'> : '.implode(' ; ', $lines));

                        continue;
                    }
                    $sent['students'] += (int) $this->deliver('student', $club->id, $plan['date'], $s['email'],
                        new StudentEveReminderMail($club->name, $dateLabel, $s));
                }
            }

            foreach ($plan['skipped'] as $reason) {
                $this->warn('  non envoyé : '.$reason);
            }
        }

        $this->info($dryRun
            ? 'Simulation : aucun e-mail envoyé.'
            : "Terminé : e-mails moniteurs {$sent['teachers']}, e-mails élèves {$sent['students']}.");

        return self::SUCCESS;
    }

    private function deliver(string $kind, int $clubId, string $date, string $email, \Illuminate\Mail\Mailable $mail): bool
    {
        $cacheKey = "eve_reminder:{$kind}:{$clubId}:{$date}:".sha1(strtolower($email));
        if (! $this->option('force') && Cache::has($cacheKey)) {
            return false;
        }
        try {
            Mail::to($email)->send($mail);
        } catch (\Throwable $e) {
            Log::error('Rappel de la veille : envoi impossible', ['kind' => $kind, 'club_id' => $clubId, 'date' => $date, 'error' => $e->getMessage()]);

            return false;
        }
        Cache::put($cacheKey, 1, now()->addHours(36));

        return true;
    }

    /** Pour les journaux : jamais l'adresse complète. */
    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
