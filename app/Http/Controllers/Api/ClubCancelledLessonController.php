<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReactivateCancelledLessonRequest;
use App\Http\Resources\CancelledLessonResource;
use App\Models\Lesson;
use App\Services\LessonReactivationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ClubCancelledLessonController extends Controller
{
    public function __construct(
        private readonly LessonReactivationService $reactivationService,
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
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'cancelled_by_role' => 'nullable|string|in:student,club,teacher,system,unknown',
            'search' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:5|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $defaultFrom = Carbon::now()->subDays(90)->startOfDay();
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $defaultFrom;

        $query = Lesson::query()
            ->where('club_id', $club->id)
            ->where('status', 'cancelled')
            ->where(function ($q) use ($from) {
                $q->where('start_time', '>=', $from);
                if (Schema::hasColumn('lessons', 'cancelled_at')) {
                    $q->orWhere('cancelled_at', '>=', $from);
                }
            })
            ->with([
                'student.user',
                'students.user',
                'teacher.user',
                'courseType',
                'cancelledByUser',
                'subscriptionInstances',
            ])
            ->orderByDesc('cancelled_at')
            ->orderByDesc('start_time');

        if (isset($validated['to'])) {
            $query->where('start_time', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        if (! empty($validated['student_id'])) {
            $studentId = (int) $validated['student_id'];
            $query->where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                    ->orWhereHas('students', fn ($sq) => $sq->where('students.id', $studentId));
            });
        }

        if (! empty($validated['teacher_id'])) {
            $query->where('teacher_id', (int) $validated['teacher_id']);
        }

        if (! empty($validated['cancelled_by_role']) && Schema::hasColumn('lessons', 'cancelled_by_role')) {
            $query->where('cancelled_by_role', $validated['cancelled_by_role']);
        }

        if (! empty($validated['search'])) {
            $term = '%' . addcslashes($validated['search'], '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('student.user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhereHas('students.user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhereHas('teacher.user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhere('notes', 'like', $term);
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CancelledLessonResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $validated['to'] ?? null,
            ],
            'message' => 'Cours annulés récupérés avec succès',
        ]);
    }

    public function reactivate(ReactivateCancelledLessonRequest $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $club = $user->getFirstClub();

        if (! $club) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun club associé',
            ], 404);
        }

        $lesson = Lesson::where('club_id', $club->id)->findOrFail($id);

        $validated = $request->validated();

        $reattachSubscription = array_key_exists('reattach_subscription', $validated)
            ? (bool) $validated['reattach_subscription']
            : ! (bool) $lesson->cancellation_count_in_subscription;

        $result = $this->reactivationService->reactivate($lesson, $user, [
            'reactivate_scope' => $validated['reactivate_scope'] ?? 'single',
            'restore_recurring_slot' => $validated['restore_recurring_slot'] ?? true,
            'reattach_subscription' => $reattachSubscription,
            'reason' => $validated['reason'] ?? null,
        ]);

        if (! ($result['success'] ?? false)) {
            $status = isset($result['conflicts']) ? 422 : 400;

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Réactivation impossible',
                'conflicts' => $result['conflicts'] ?? null,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ]);
    }
}
