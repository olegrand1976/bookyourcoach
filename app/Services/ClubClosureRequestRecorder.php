<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubClosureRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Consigne chaque demande de congés du club, acceptée ou refusée, avec son origine.
 *
 * Comme pour les connexions, écrire cette trace ne doit jamais empêcher l'action :
 * toute erreur ici est journalisée et avalée.
 */
class ClubClosureRequestRecorder
{
    public function __construct(
        private readonly RequestOriginResolver $origin,
    ) {}

    public function record(
        Request $request,
        Club $club,
        ?User $user,
        string $dateYmd,
        string $action,
        ?string $method,
        string $outcome,
        ?int $impactedLessons = null,
    ): ?ClubClosureRequest {
        try {
            return ClubClosureRequest::create(array_merge([
                'club_id' => $club->id,
                'user_id' => $user?->id,
                'closed_on' => $dateYmd,
                'action' => $action,
                'confirmation_method' => $method,
                'outcome' => $outcome,
                'impacted_lessons' => $impactedLessons,
            ], $this->origin->describe($request)));
        } catch (\Throwable $e) {
            Log::warning('Demande de congés : trace impossible', [
                'club_id' => $club->id,
                'date' => $dateYmd,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
