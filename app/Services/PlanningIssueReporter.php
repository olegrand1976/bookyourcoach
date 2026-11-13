<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ouvre une issue GitHub pour un jour de planning incohérent, à traiter ensuite par Claude
 * (analyse en lecture seule de la base de production, correctif, issue fermée).
 *
 * Le dépôt est public : l'issue ne contient AUCUNE donnée personnelle (ni nom d'élève, ni nom
 * d'enseignant, ni e-mail) — seulement identifiants techniques, heures et types d'incohérence.
 */
class PlanningIssueReporter
{
    public function isEnabled(): bool
    {
        return (string) config('services.github_planning_issues.token') !== ''
            && (string) config('services.github_planning_issues.repo') !== '';
    }

    /**
     * @param  array<string, mixed>  $report  Résultat de PlanningConsistencyCheckService::checkDay
     * @return string|null URL de l'issue créée
     */
    public function report(Club $club, array $report): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $response = Http::withToken((string) config('services.github_planning_issues.token'))
            ->acceptJson()
            ->timeout(15)
            ->post('https://api.github.com/repos/'.config('services.github_planning_issues.repo').'/issues', [
                'title' => $this->title($club, $report),
                'body' => $this->body($club, $report),
                'labels' => [(string) config('services.github_planning_issues.label', 'veille-planning')],
            ]);

        if (! $response->successful()) {
            Log::warning('Veille planning : issue GitHub non créée', ['club_id' => $club->id, 'status' => $response->status()]);

            return null;
        }

        return (string) $response->json('html_url');
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function title(Club $club, array $report): string
    {
        $c = $report['counts'];

        return sprintf('[Veille planning] Club #%d — %s — %d à corriger, %d à vérifier', $club->id, $report['date'], $c['critical'], $c['warning']);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function body(Club $club, array $report): string
    {
        $lines = [
            "Incohérences détectées par `planning:check-consistency` sur le planning du **{$report['date']}** (club #{$club->id}, {$report['lessons_count']} cours).",
            '',
            '| Niveau | Heure | Type | Cours (ids) |',
            '|---|---|---|---|',
        ];
        foreach ($report['issues'] as $i) {
            $lines[] = sprintf('| %s | %s | `%s` | %s |', $i['level'], $i['time'] !== '' ? $i['time'] : '—', $i['type'], implode(', ', $i['lesson_ids']));
        }
        $lines[] = '';
        $lines[] = '### À traiter par Claude';
        $lines[] = '- Rejouer le contrôle : `php artisan planning:check-consistency --club='.$club->id.' --date='.$report['date'].' --dry-run`.';
        $lines[] = '- Analyser en **lecture seule** la base de production (cours listés, `lesson_action_logs`, `lesson_movement_histories`, séries `subscription_recurring_slots`) pour trouver la cause.';
        $lines[] = '- Proposer le correctif (code et/ou données) ; toute écriture en base attend la validation du club.';
        $lines[] = '- Ne jamais recopier ici de nom, e-mail ou autre donnée personnelle (dépôt public).';

        return implode("\n", $lines);
    }
}
