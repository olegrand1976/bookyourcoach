<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubClosureDay;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\SubscriptionInstance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Veille proactive des abonnements : pour les élèves qui ont cours sur une période
 * (la veille : le lendemain), repère les abonnements qui arrivent en fin de vie.
 *
 * Un abonnement est signalé dès qu'un seuil est atteint — cours restants OU jours
 * avant expiration (config bookyourcoach.subscription_expiry_watch.thresholds) —
 * et classé au niveau le plus urgent atteint. Un élève qui a cours mais dont tous
 * les abonnements du club sont terminés est classé « critique ».
 */
class SubscriptionExpiryWatchService
{
    public const LEVEL_CRITICAL = 'critical';

    public const LEVEL_URGENT = 'urgent';

    public const LEVEL_WATCH = 'watch';

    /** Ordre d'affichage : le plus urgent d'abord. */
    public const LEVELS = [self::LEVEL_CRITICAL, self::LEVEL_URGENT, self::LEVEL_WATCH];

    /**
     * « Plus d'abonnement actif » n'est signalé que si le dernier abonnement a expiré
     * récemment : un élève passé au cours à l'unité depuis longtemps n'est pas une alerte.
     */
    private const NO_ACTIVE_LOOKBACK_DAYS = 90;

    /**
     * @return array{
     *     club_id: int,
     *     from: string,
     *     to: string,
     *     skipped_closure_days: list<string>,
     *     students_with_lessons: int,
     *     counts: array<string, int>,
     *     groups: array<string, list<array<string, mixed>>>
     * }
     */
    public function buildReport(Club $club, CarbonInterface $from, CarbonInterface $to, string $timezone = 'Europe/Brussels'): array
    {
        $start = Carbon::parse($from->format('Y-m-d'), $timezone)->startOfDay();
        $end = Carbon::parse($to->format('Y-m-d'), $timezone)->endOfDay();

        // Les jours de congé du club ne donnent lieu à aucun cours : on les écarte.
        $closed = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (ClubClosureDay::clubIsClosedOn($club->id, $d->toDateString())) {
                $closed[] = $d->toDateString();
            }
        }

        // start_time est stocké dans le fuseau de l'application : bornes exprimées dans ce fuseau.
        $appTz = (string) config('app.timezone', 'UTC');
        $lessons = Lesson::query()
            ->where('club_id', $club->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('start_time', [$start->copy()->timezone($appTz), $end->copy()->timezone($appTz)])
            ->with([
                // Un élève désinscrit du cours (pivot annulé) n'a pas cours.
                'students' => fn ($q) => $q->select('students.id')->where(function ($w) {
                    $w->whereNull('lesson_student.status')->orWhere('lesson_student.status', '!=', 'cancelled');
                }),
                'courseType',
            ])
            ->orderBy('start_time')
            ->get()
            ->reject(fn (Lesson $l) => in_array($l->start_time?->copy()->timezone($timezone)->toDateString(), $closed, true));

        // Prochain cours de chaque élève sur la période
        $nextLessonByStudent = [];
        foreach ($lessons as $lesson) {
            $ids = $lesson->students->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($lesson->student_id) {
                $ids[] = (int) $lesson->student_id;
            }
            foreach (array_unique($ids) as $sid) {
                $nextLessonByStudent[$sid] ??= $lesson;
            }
        }

        $groups = array_fill_keys(self::LEVELS, []);
        $studentIds = array_keys($nextLessonByStudent);

        if ($studentIds !== []) {
            $students = Student::query()->with('user:id,name,first_name,last_name,email')
                ->whereIn('id', $studentIds)->get()->keyBy('id');

            // Abonnements du club (cloisonnement par club_id via subscriptions.club_id)
            $instances = SubscriptionInstance::query()
                ->whereHas('subscription', fn ($q) => $q->where('club_id', $club->id))
                ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
                ->with(['subscription.template', 'students:id'])
                ->get();

            $byStudent = [];
            foreach ($instances as $instance) {
                foreach ($instance->students as $s) {
                    if (isset($nextLessonByStudent[(int) $s->id])) {
                        $byStudent[(int) $s->id][] = $instance;
                    }
                }
            }

            foreach ($byStudent as $sid => $studentInstances) {
                $lesson = $nextLessonByStudent[$sid];
                $lessonDay = $lesson->start_time->copy()->timezone($timezone)->startOfDay();
                $active = array_values(array_filter($studentInstances, fn ($i) => $i->status === 'active'));

                if ($active === []) {
                    // A eu un abonnement récemment, n'en a plus d'actif, mais a cours : à régulariser.
                    $latest = collect($studentInstances)->sortByDesc(fn ($i) => $i->expires_at?->timestamp ?? PHP_INT_MAX)->first();
                    $recent = ! $latest->expires_at
                        || $latest->expires_at->gte($lessonDay->copy()->subDays(self::NO_ACTIVE_LOOKBACK_DAYS));
                    if ($recent) {
                        $groups[self::LEVEL_CRITICAL][] = $this->entry($students->get($sid), $sid, $lesson, $latest, $lessonDay, $timezone, 'no_active_subscription');
                    }

                    continue;
                }

                // Déjà renouvelé : un autre abonnement actif du même modèle n'est pas en fin de vie.
                $healthyTemplates = [];
                foreach ($active as $instance) {
                    if ($this->levelFor($instance, $lessonDay) === null) {
                        $healthyTemplates[(int) $instance->subscription?->subscription_template_id] = true;
                    }
                }

                foreach ($active as $instance) {
                    $level = $this->levelFor($instance, $lessonDay);
                    if ($level === null || isset($healthyTemplates[(int) $instance->subscription?->subscription_template_id])) {
                        continue;
                    }
                    $groups[$level][] = $this->entry($students->get($sid), $sid, $lesson, $instance, $lessonDay, $timezone, null);
                }
            }
        }

        foreach ($groups as $level => $entries) {
            usort($entries, fn ($a, $b) => [$a['remaining_lessons'] ?? PHP_INT_MAX, $a['days_until_expiry'] ?? PHP_INT_MAX, $a['student_name']]
                <=> [$b['remaining_lessons'] ?? PHP_INT_MAX, $b['days_until_expiry'] ?? PHP_INT_MAX, $b['student_name']]);
            $groups[$level] = $entries;
        }

        return [
            'club_id' => (int) $club->id,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'skipped_closure_days' => $closed,
            'students_with_lessons' => count($studentIds),
            'counts' => array_map('count', $groups),
            'groups' => $groups,
        ];
    }

    /** Niveau d'urgence atteint (le plus urgent), ou null si l'abonnement n'est pas en fin de vie. */
    public function levelFor(SubscriptionInstance $instance, CarbonInterface $referenceDay): ?string
    {
        $thresholds = (array) config('bookyourcoach.subscription_expiry_watch.thresholds', []);
        $remaining = (int) $instance->remaining_consumed;
        $days = $this->daysUntilExpiry($instance, $referenceDay);

        foreach (self::LEVELS as $level) {
            $t = $thresholds[$level] ?? null;
            if (! $t) {
                continue;
            }
            if ($remaining <= (int) $t['lessons'] || ($days !== null && $days <= (int) $t['days'])) {
                return $level;
            }
        }

        return null;
    }

    private function daysUntilExpiry(?SubscriptionInstance $instance, CarbonInterface $referenceDay): ?int
    {
        if (! $instance?->expires_at) {
            return null;
        }

        return (int) $referenceDay->copy()->startOfDay()->diffInDays($instance->expires_at->copy()->startOfDay(), false);
    }

    /** @return array<string, mixed> */
    private function entry(?Student $student, int $studentId, Lesson $lesson, ?SubscriptionInstance $instance, CarbonInterface $lessonDay, string $timezone, ?string $reason): array
    {
        $remaining = $instance ? (int) $instance->remaining_consumed : null;
        $days = $this->daysUntilExpiry($instance, $lessonDay);

        $reasons = [];
        if ($reason === 'no_active_subscription') {
            $reasons[] = 'Plus aucun abonnement actif';
        } else {
            if ($remaining !== null) {
                $reasons[] = $remaining <= 1
                    ? ($remaining === 0 ? 'Plus aucun cours restant' : 'Dernier cours restant')
                    : "{$remaining} cours restants";
            }
            if ($days !== null && $days <= (int) config('bookyourcoach.subscription_expiry_watch.thresholds.watch.days', 14)) {
                $reasons[] = $days < 0 ? 'Expiré' : ($days === 0 ? 'Expire le jour du cours' : "Expire dans {$days} j");
            }
        }

        return [
            'student_id' => $studentId,
            'student_name' => $this->studentName($student, $studentId),
            'student_email' => $student?->user?->email,
            'next_lesson_at' => $lesson->start_time?->copy()->timezone($timezone)->format('Y-m-d H:i'),
            'next_lesson_course_type' => $lesson->courseType?->name,
            'subscription_instance_id' => $instance?->id,
            'subscription_label' => $instance ? $this->subscriptionLabel($instance) : null,
            'status' => $instance?->status,
            'remaining_lessons' => $remaining,
            'expires_at' => $instance?->expires_at?->toDateString(),
            'days_until_expiry' => $days,
            'reason' => $reason,
            'reasons' => $reasons,
        ];
    }

    private function studentName(?Student $student, int $id): string
    {
        if (! $student) {
            return 'Élève #'.$id;
        }
        $name = trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
        if ($name === '' && $student->user) {
            $name = $student->user->name ?: trim(($student->user->first_name ?? '').' '.($student->user->last_name ?? ''));
        }

        return $name !== '' ? $name : 'Élève #'.$id;
    }

    private function subscriptionLabel(SubscriptionInstance $instance): string
    {
        $template = $instance->subscription?->template;
        $parts = array_filter([
            $template?->model_number,
            $template ? ((int) $template->total_lessons + (int) $template->free_lessons).' cours' : null,
        ]);

        return $parts !== [] ? implode(' — ', $parts) : 'Abonnement #'.$instance->id;
    }

    /** Liste à plat triée par urgence (pour le tableau de bord). */
    public function flatten(array $report): Collection
    {
        return collect(self::LEVELS)->flatMap(
            fn (string $level) => collect($report['groups'][$level] ?? [])->map(fn ($e) => $e + ['level' => $level])
        )->values();
    }
}
