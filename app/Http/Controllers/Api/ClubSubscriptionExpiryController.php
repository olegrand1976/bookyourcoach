<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionExpiryWatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tableau de bord club : abonnements en fin de vie des élèves qui ont cours
 * dans les prochains jours (même règles que la veille e-mail).
 */
class ClubSubscriptionExpiryController extends Controller
{
    public function __construct(
        private readonly SubscriptionExpiryWatchService $watchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'club') {
            return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
        }

        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }

        $tz = (string) config('bookyourcoach.subscription_expiry_watch.timezone', 'Europe/Brussels');
        $days = max(1, min(31, (int) $request->input('days', config('bookyourcoach.subscription_expiry_watch.dashboard_days', 7))));
        $from = now($tz)->startOfDay();
        $to = $from->copy()->addDays($days - 1);

        $report = $this->watchService->buildReport($club, $from, $to, $tz);

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $report['from'],
                'to' => $report['to'],
                'counts' => $report['counts'],
                'students_with_lessons' => $report['students_with_lessons'],
                'items' => $this->watchService->flatten($report)->all(),
            ],
            'message' => 'Abonnements en fin de vie des élèves ayant cours prochainement.',
        ]);
    }
}
