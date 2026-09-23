<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoginAttemptResource;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historique des connexions. Chacun consulte le sien ; les administrateurs
 * plateforme consultent celui de n'importe quel compte, pour enquêter.
 *
 * Les gérants de club n'y ont délibérément pas accès : ce serait de la
 * surveillance d'enseignants et d'élèves, avec une base légale à établir.
 */
class LoginHistoryController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function mine(Request $request): JsonResponse
    {
        return $this->respondWith($request, (int) $request->user()->id);
    }

    public function forUser(Request $request, int $userId): JsonResponse
    {
        if (! User::whereKey($userId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return $this->respondWith($request, $userId);
    }

    private function respondWith(Request $request, int $userId): JsonResponse
    {
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) $request->integer('per_page', 25)));

        $attempts = LoginAttempt::query()
            ->where('user_id', $userId)
            ->when($request->boolean('failed_only'), fn ($q) => $q->failed())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => LoginAttemptResource::collection($attempts->items()),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'per_page' => $attempts->perPage(),
                'total' => $attempts->total(),
                'retention_days' => (int) config('bookyourcoach.auth.login_history_retention_days', 365),
            ],
            'message' => null,
        ]);
    }
}
