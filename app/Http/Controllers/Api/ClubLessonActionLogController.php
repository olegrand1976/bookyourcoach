<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonActionLogResource;
use App\Models\LessonActionLog;
use App\Services\LessonActionLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ClubLessonActionLogController extends Controller
{
    public function __construct(
        private readonly LessonActionLogService $lessonActionLogService,
    ) {}

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

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : null;

        $studentId = ! empty($validated['student_id']) ? (int) $validated['student_id'] : null;

        $this->lessonActionLogService->syncMissingCancellationLogs(
            (int) $club->id,
            $from,
            $to,
            $studentId,
        );

        $query = LessonActionLog::query()
            ->where('club_id', $club->id)
            ->with([
                'student.user',
                'subscriptionInstance.subscription.template',
                'performedByUser',
                'lesson',
            ])
            ->orderByDesc('created_at');

        $this->applyDateFilters($query, $from, $to);

        if ($studentId !== null) {
            $query->where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->orWhereHas('lesson', function ($l) use ($studentId) {
                        $l->where('student_id', $studentId)
                            ->orWhereHas('students', fn ($sq) => $sq->where('students.id', $studentId));
                    });
            });
        }

        if (! empty($validated['subscription_instance_id'])) {
            $query->where('subscription_instance_id', (int) $validated['subscription_instance_id']);
        }

        $actionFilters = LessonActionLog::resolveActionFilters($validated['action'] ?? null);
        if ($actionFilters !== null) {
            $query->whereIn('action', $actionFilters);
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
            ->prepend([
                'value' => LessonActionLog::ACTION_FILTER_ALL_CANCELLATIONS,
                'label' => 'Toutes les annulations',
            ])
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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<LessonActionLog>  $query
     */
    private function applyDateFilters($query, Carbon $from, ?Carbon $to): void
    {
        $query->where(function ($q) use ($from) {
            $q->where('created_at', '>=', $from)
                ->orWhereHas('lesson', function ($l) use ($from) {
                    $l->where('start_time', '>=', $from);
                    if (Schema::hasColumn('lessons', 'cancelled_at')) {
                        $l->orWhere('cancelled_at', '>=', $from);
                    }
                });
        });

        if ($to === null) {
            return;
        }

        $query->where(function ($q) use ($to) {
            $q->where('created_at', '<=', $to)
                ->orWhereHas('lesson', function ($l) use ($to) {
                    $l->where('start_time', '<=', $to);
                    if (Schema::hasColumn('lessons', 'cancelled_at')) {
                        $l->orWhere('cancelled_at', '<=', $to);
                    }
                });
        });
    }
}
