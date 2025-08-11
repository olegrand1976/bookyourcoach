<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\CourseType;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Admin Dashboard",
 *     description="Tableau de bord administrateur avec statistiques et gestion"
 * )
 */
class AdminDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Tableau de bord administrateur",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques du tableau de bord",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_users", type="integer", example=125),
     *                     @OA\Property(property="total_lessons", type="integer", example=89),
     *                     @OA\Property(property="total_revenue", type="number", example=4567.50),
     *                     @OA\Property(property="active_teachers", type="integer", example=12)
     *                 ),
     *                 @OA\Property(property="recent_lessons", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="monthly_revenue", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function dashboard(): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        try {
            // Statistiques générales
            $stats = [
                'total_users' => User::count(),
                'total_teachers' => User::where('role', User::ROLE_TEACHER)->count(),
                'total_students' => User::where('role', User::ROLE_STUDENT)->count(),
                'total_lessons' => Lesson::count(),
                'lessons_this_month' => Lesson::whereMonth('created_at', Carbon::now()->month)->count(),
                'total_revenue' => Payment::where('status', 'succeeded')->sum('amount'),
                'revenue_this_month' => Payment::where('status', 'succeeded')
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('amount'),
                'active_teachers' => User::where('role', User::ROLE_TEACHER)
                    ->where('status', 'active')
                    ->count(),
                'pending_lessons' => Lesson::where('status', 'pending')->count(),
            ];

            // Leçons récentes (simplifiées)
            $recentLessons = Lesson::with(['courseType', 'location'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'course_type' => $lesson->courseType?->name ?? 'N/A',
                        'scheduled_at' => $lesson->scheduled_at,
                        'status' => $lesson->status,
                        'price' => $lesson->price,
                        'location' => $lesson->location?->name ?? 'N/A',
                    ];
                });

            // Revenus mensuels (6 derniers mois)
            $monthlyRevenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $revenue = Payment::where('status', 'succeeded')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('amount');
                
                $monthlyRevenue[] = [
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->translatedFormat('F Y'),
                    'revenue' => (float) $revenue,
                ];
            }

            // Top enseignants (simplifié pour éviter les erreurs de jointure)
            $topTeachers = collect([]); // Vide pour l'instant
            try {
                if (DB::table('lessons')->exists() && DB::table('teachers')->exists()) {
                    $topTeachers = DB::table('lessons')
                        ->join('teachers', 'lessons.teacher_id', '=', 'teachers.id')
                        ->select(
                            'teachers.id',
                            DB::raw('COUNT(lessons.id) as total_lessons'),
                            DB::raw('SUM(lessons.price) as total_revenue')
                        )
                        ->where('lessons.status', '!=', 'cancelled')
                        ->groupBy('teachers.id')
                        ->orderBy('total_lessons', 'desc')
                        ->take(5)
                        ->get();
                }
            } catch (\Exception $e) {
                $topTeachers = collect([]);
            }

            // Répartition par type de cours (simplifié)
            $courseTypeStats = collect([]);
            try {
                if (DB::table('lessons')->exists() && DB::table('course_types')->exists()) {
                    $courseTypeStats = DB::table('lessons')
                        ->join('course_types', 'lessons.course_type_id', '=', 'course_types.id')
                        ->select(
                            'course_types.name',
                            DB::raw('COUNT(lessons.id) as count'),
                            DB::raw('SUM(lessons.price) as revenue')
                        )
                        ->where('lessons.status', '!=', 'cancelled')
                        ->groupBy('course_types.id', 'course_types.name')
                        ->orderBy('count', 'desc')
                        ->get();
                }
            } catch (\Exception $e) {
                $courseTypeStats = collect([]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_lessons' => $recentLessons,
                    'monthly_revenue' => $monthlyRevenue,
                    'top_teachers' => $topTeachers,
                    'course_type_stats' => $courseTypeStats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Liste de tous les utilisateurs (admin)",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         @OA\Schema(type="string", enum={"admin", "teacher", "student"}),
     *         description="Filtrer par rôle"
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"}),
     *         description="Filtrer par statut"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs"
     *     )
     * )
     */
    public function users(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        try {
            $query = User::with(['profile', 'teacher', 'student']);

            // Filtres
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}/status",
     *     summary="Modifier le statut d'un utilisateur",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut mis à jour"
     *     )
     * )
     */
    public function updateUserStatus(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        try {
            $targetUser = User::findOrFail($id);
            
            // Empêcher de modifier son propre statut
            if ($targetUser->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier votre propre statut'
                ], 400);
            }

            $targetUser->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Statut utilisateur mis à jour',
                'data' => $targetUser
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
