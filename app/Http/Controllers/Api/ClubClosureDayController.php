<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexClubClosureDayRequest;
use App\Http\Requests\UpsertClubClosureDayRequest;
use App\Models\ClubClosureDay;
use App\Services\ClubClosureDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubClosureDayController extends Controller
{
    public function __construct(
        private readonly ClubClosureDayService $clubClosureDayService
    ) {}

    public function index(IndexClubClosureDayRequest $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $dates = ClubClosureDay::query()
            ->where('club_id', $club->id)
            ->whereBetween('closed_on', [$dateFrom, $dateTo])
            ->orderBy('closed_on')
            ->get()
            ->map(fn (ClubClosureDay $row) => $row->closed_on->format('Y-m-d'))
            ->values();

        return response()->json([
            'success' => true,
            'data' => ['dates' => $dates],
            'message' => null,
        ]);
    }

    public function upsert(UpsertClubClosureDayRequest $request): JsonResponse
    {
        $club = $request->user()->getFirstClub();
        if (!$club) {
            return response()->json(['success' => false, 'message' => 'Club non trouvé'], 404);
        }

        $dateYmd = $request->validated('date');
        $closed = $request->boolean('closed');

        if ($closed) {
            $meta = $this->clubClosureDayService->closeDay($club, $dateYmd);

            return response()->json([
                'success' => true,
                'data' => $meta,
                'message' => $meta['notified']
                    ? 'Jour marqué comme fermé. Les crédits abonnement ont été restitués pour les cours liés ce jour-là. Les participants ont été notifiés par e-mail.'
                    : 'Ce jour était déjà marqué comme fermé.',
            ]);
        }

        $opened = $this->clubClosureDayService->openDay($club, $dateYmd);

        return response()->json([
            'success' => true,
            'data' => ['reopened' => $opened],
            'message' => $opened
                ? 'Congé annulé. Les moniteurs et élèves concernés ont été notifiés par e-mail. Les abonnements ne sont pas ré-appliqués automatiquement aux cours existants.'
                : 'Aucune fermeture enregistrée pour cette date.',
        ]);
    }
}
