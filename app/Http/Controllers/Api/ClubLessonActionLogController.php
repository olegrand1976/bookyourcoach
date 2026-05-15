<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonActionLogResource;
use App\Models\LessonActionLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClubLessonActionLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'club') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux clubs',
            ], 403);
        }

        $club = $user->getFirstClub();
        if (! $club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé',
            ], 404);
        }

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'student_id' => 'nullable|integer|exists:students,id',
            'subscription_instance_id' => 'nullable|integer|exists:subscription_instances,id',
            'action' => 'nullable|string|max:64',
            'performed_by_user_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:10|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $defaultFrom = Carbon::now()->subDays(90)->startOfDay();
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $defaultFrom;

        $query = LessonActionLog::query()
            ->where('club_id', $club->id)
            ->where('created_at', '>=', $from)
            ->with([
                'student.user',
                'subscriptionInstance.subscription.template',
                'performedByUser',
                'lesson',
            ])
            ->orderByDesc('created_at');

        if (isset($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        if (! empty($validated['student_id'])) {
            $query->where('student_id', (int) $validated['student_id']);
        }

        if (! empty($validated['subscription_instance_id'])) {
            $query->where('subscription_instance_id', (int) $validated['subscription_instance_id']);
        }

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['performed_by_user_id'])) {
            $query->where('performed_by_user_id', (int) $validated['performed_by_user_id']);
        }

        if (! empty($validated['search'])) {
            $term = '%' . addcslashes($validated['search'], '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('student.user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhereHas('performedByUser', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhere('meta->student_names', 'like', $term);
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        $actionTypes = collect(LessonActionLog::ACTION_LABELS)
            ->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])
            ->values();

        return response()->json([
            'success' => true,
            'data' => LessonActionLogResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'action_types' => $actionTypes,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $validated['to'] ?? null,
            ],
            'message' => 'Journal des actions récupéré avec succès',
        ]);
    }
}
