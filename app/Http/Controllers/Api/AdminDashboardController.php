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
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1),
     *         description="Numéro de page"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", default=10),
     *         description="Nombre d'éléments par page"
     *     ),
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
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         @OA\Schema(type="string"),
     *         description="Rechercher par nom ou email"
     *     ),
     *     @OA\Parameter(
     *         name="postal_code",
     *         in="query",
     *         @OA\Schema(type="string"),
     *         description="Filtrer par code postal"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs avec pagination"
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
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('postal_code')) {
                $query->where('postal_code', 'like', "%{$request->postal_code}%");
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = min($perPage, 100); // Limiter à 100 éléments max par page

            $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem()
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
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Créer un nouvel utilisateur",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="Jean"),
     *             @OA\Property(property="last_name", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="jean@example.com"),
     *             @OA\Property(property="phone", type="string", example="+33123456789"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="street", type="string", example="Rue de la Paix"),
     *             @OA\Property(property="street_number", type="string", example="123"),
     *             @OA\Property(property="postal_code", type="string", example="1000"),
     *             @OA\Property(property="city", type="string", example="Bruxelles"),
     *             @OA\Property(property="country", type="string", example="Belgium"),
     *             @OA\Property(property="role", type="string", enum={"admin", "teacher", "student"}, example="student"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès"
     *     )
     * )
     */
    public function createUser(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'role' => 'required|in:admin,teacher,student',
            'password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $userData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'birth_date',
                'street', 'street_number', 'postal_code', 'city', 'country', 'role'
            ]);
            
            $userData['name'] = trim($userData['first_name'] . ' ' . $userData['last_name']);
            $userData['password'] = bcrypt($request->password);
            $userData['status'] = 'active';
            $userData['is_active'] = true;

            $newUser = User::create($userData);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $newUser
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Modifier un utilisateur",
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
     *             @OA\Property(property="first_name", type="string", example="Jean"),
     *             @OA\Property(property="last_name", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="jean@example.com"),
     *             @OA\Property(property="phone", type="string", example="+33123456789"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="street", type="string", example="Rue de la Paix"),
     *             @OA\Property(property="street_number", type="string", example="123"),
     *             @OA\Property(property="postal_code", type="string", example="1000"),
     *             @OA\Property(property="city", type="string", example="Bruxelles"),
     *             @OA\Property(property="country", type="string", example="Belgium"),
     *             @OA\Property(property="role", type="string", enum={"admin", "teacher", "student"}, example="student")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur modifié avec succès"
     *     )
     * )
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'role' => 'required|in:admin,teacher,student'
        ]);

        try {
            $targetUser = User::findOrFail($id);

            $userData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'birth_date',
                'street', 'street_number', 'postal_code', 'city', 'country', 'role'
            ]);
            
            $userData['name'] = trim($userData['first_name'] . ' ' . $userData['last_name']);

            $targetUser->update($userData);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès',
                'data' => $targetUser
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'utilisateur',
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
     *             @OA\Property(property="is_active", type="boolean", example=true)
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
            'is_active' => 'required|boolean'
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

            $targetUser->update(['is_active' => $request->is_active]);

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

    /**
     * @OA\Post(
     *     path="/api/admin/upload-logo",
     *     summary="Upload du logo de la plateforme",
     *     tags={"Admin Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="logo", type="string", format="binary", description="Fichier logo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo uploadé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="logo_url", type="string", example="/storage/logo.svg"),
     *             @OA\Property(property="message", type="string", example="Logo uploadé avec succès")
     *         )
     *     )
     * )
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Administrateur requis.'
            ], 403);
        }

        $request->validate([
            'logo' => 'required|file|mimes:svg,png,jpg,jpeg|max:2048', // 2MB max
        ]);

        try {
            $file = $request->file('logo');

            // Générer un nom unique pour le fichier
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();

            // Stocker le fichier dans public/storage
            $path = $file->storeAs('logos', $filename, 'public');

            // Générer l'URL publique
            $logoUrl = '/storage/' . $path;

            return response()->json([
                'success' => true,
                'logo_url' => $logoUrl,
                'message' => 'Logo uploadé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
