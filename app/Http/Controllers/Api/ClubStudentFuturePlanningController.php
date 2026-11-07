<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProlongRecurringSlotsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * « Planifier l'avenir » depuis la fiche élève : prolonge ses séries récurrentes tant qu'un
 * abonnement est actif et génère les cours à venir (mêmes règles que la tâche quotidienne).
 */
class ClubStudentFuturePlanningController extends Controller
{
    public function __construct(
        private readonly ProlongRecurringSlotsService $service,
    ) {}

    public function store(int $studentId): JsonResponse
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            return response()->json(['success' => false, 'message' => 'Accès réservé aux clubs'], 403);
        }
        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json(['success' => false, 'message' => 'Aucun club associé'], 404);
        }
        $belongs = DB::table('club_students')->where('club_id', $club->id)->where('student_id', $studentId)->exists();
        if (! $belongs) {
            return response()->json(['success' => false, 'message' => 'Élève introuvable dans ce club'], 404);
        }

        try {
            $result = $this->service->prolongActiveRecurrences($studentId, (int) $club->id);
        } catch (\Throwable $e) {
            Log::error('Planification de l\'avenir impossible', ['club_id' => $club->id, 'student_id' => $studentId, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'La planification a échoué.'], 500);
        }

        $last = $this->service->lastPlannedLessonFor($studentId);
        $generated = (int) $result['lessons_generated'];
        $message = $generated > 0
            ? "{$generated} cours planifié(s)."
            : 'Aucun nouveau cours à planifier (planning déjà à jour, plus de place dans les abonnements ou aucun abonnement actif).';

        return response()->json([
            'success' => true,
            'data' => $result + ['last_planned_at' => $last?->start_time?->toIso8601String()],
            'message' => $message,
        ]);
    }
}
