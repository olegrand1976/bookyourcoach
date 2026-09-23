<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\ClubClosureRequest;
use Illuminate\Console\Command;

/**
 * Qui a fermé ou rouvert quelle journée, d'où, et avec quelle confirmation.
 */
class ShowClubClosureRequestsCommand extends Command
{
    protected $signature = 'club:closure-requests
                            {club : Identifiant numérique du club}
                            {--date= : Journée visée (Y-m-d)}
                            {--limit=30 : Nombre de lignes}';

    protected $description = 'Affiche les dernières demandes de congés d’un club';

    public function handle(): int
    {
        $club = Club::find((int) $this->argument('club'));

        if (! $club) {
            $this->error('Club introuvable : '.$this->argument('club'));

            return self::FAILURE;
        }

        $requests = ClubClosureRequest::query()
            ->with('user:id,email')
            ->where('club_id', $club->id)
            ->when($this->option('date'), fn ($q, $date) => $q->whereDate('closed_on', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($requests->isEmpty()) {
            $this->warn("Aucune demande de congés enregistrée pour le club #{$club->id}.");

            return self::SUCCESS;
        }

        $this->info("Demandes de congés du club #{$club->id} — la plus récente en tête");

        $this->table(
            ['Date', 'Journée', 'Action', 'Résultat', 'Confirmation', 'Par', 'Adresse IP', 'Localisation', 'Appareil'],
            $requests->map(fn (ClubClosureRequest $r) => [
                $r->created_at?->format('Y-m-d H:i:s'),
                $r->closed_on?->format('Y-m-d'),
                $r->action === ClubClosureRequest::ACTION_CLOSE ? 'fermeture' : 'réouverture',
                $r->outcome,
                $r->confirmation_method ?? '—',
                $r->user?->email ?? '—',
                $r->ip_address ?? '—',
                $r->location_label ?? '—',
                trim(implode(' ', array_filter([$r->device_type, $r->browser, $r->platform]))) ?: '—',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
