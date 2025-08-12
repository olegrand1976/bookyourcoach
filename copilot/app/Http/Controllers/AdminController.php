<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Club;
use App\Models\AuditLog;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Administration endpoints"
 * )
 */
class AdminController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'admin']);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/stats",
     *     summary="Get platform statistics",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistics retrieved successfully")
     * )
     */
    public function getStats()
    {
        $stats = [
            'users' => User::count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
            'clubs' => Club::count(),
            'active_users' => User::where('is_active', true)->count(),
            'lessons_today' => 0, // À implémenter avec le modèle Lesson
            'revenue_month' => 0, // À implémenter avec le modèle Payment
        ];

        return response()->json($stats);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get users list for admin",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="role", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Users retrieved successfully")
     * )
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $is_active = $request->status === 'active';
            $query->where('is_active', $is_active);
        }

        $perPage = $request->get('per_page', 10);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create a new user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin","teacher","student"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created successfully")
     * )
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Log de l'action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user_created',
            'model_type' => 'User',
            'model_id' => $user->id,
            'data' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);

        return response()->json($user, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Update a user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin","teacher","student"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated successfully")
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $user->toArray();
        $user->update($request->only(['name', 'email', 'role']));

        // Log de l'action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user_updated',
            'model_type' => 'User',
            'model_id' => $user->id,
            'data' => ['old' => $oldData, 'new' => $user->toArray()],
        ]);

        return response()->json($user);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/users/{id}/toggle-status",
     *     summary="Toggle user active status",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User status toggled successfully")
     * )
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        // Empêcher la désactivation du dernier admin
        if ($user->role === 'admin' && $user->is_active) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return response()->json(['message' => 'Impossible de désactiver le dernier administrateur'], 422);
            }
        }

        $user->is_active = !$user->is_active;
        $user->status = $user->is_active ? 'active' : 'inactive';
        $user->save();

        // Log de l'action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user_status_toggled',
            'model_type' => 'User',
            'model_id' => $user->id,
            'data' => ['status' => $user->status],
        ]);

        return response()->json($user);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/activities",
     *     summary="Get recent admin activities",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Activities retrieved successfully")
     * )
     */
    public function getActivities(Request $request)
    {
        $limit = $request->get('limit', 10);

        $activities = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'message' => $this->formatActivityMessage($log),
                    'time' => $log->created_at->diffForHumans(),
                    'icon' => $this->getActivityIcon($log->action),
                    'user' => $log->user ? $log->user->name : 'Système',
                ];
            });

        return response()->json(['data' => $activities]);
    }

    /**
     * Get all system settings
     */
    public function getAllSettings()
    {
        try {
            $settings = [
                'general' => $this->getDefaultSettings('general'),
                'booking' => $this->getDefaultSettings('booking'),
                'payment' => $this->getDefaultSettings('payment'),
                'notifications' => $this->getDefaultSettings('notifications'),
            ];
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du chargement des paramètres'], 500);
        }
    }

    /**
     * Get system settings
     */
    public function getSettings($type)
    {
        try {
            // Pour l'instant, retourner des paramètres par défaut basés sur le type
            $defaultSettings = $this->getDefaultSettings($type);
            return response()->json($defaultSettings);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du chargement des paramètres'], 500);
        }
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request, $type)
    {
        try {
            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'settings_updated',
                'model_type' => 'Settings',
                'data' => ['type' => $type, 'settings' => $request->all()],
            ]);

            return response()->json(['message' => 'Paramètres mis à jour avec succès']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour des paramètres'], 500);
        }
    }

    /**
     * Get default settings by type
     */
    private function getDefaultSettings($type)
    {
        switch ($type) {
            case 'general':
                return [
                    'platform_name' => 'BookYourCoach',
                    'contact_email' => 'contact@bookyourcoach.fr',
                    'contact_phone' => '+33 1 23 45 67 89',
                    'timezone' => 'Europe/Brussels',
                    'company_address' => ''
                ];

            case 'booking':
                return [
                    'min_booking_hours' => 2,
                    'max_booking_days' => 30,
                    'cancellation_hours' => 24,
                    'default_lesson_duration' => 60,
                    'auto_confirm_bookings' => true,
                    'send_reminder_emails' => true,
                    'allow_student_cancellation' => true
                ];

            case 'payment':
                return [
                    'platform_commission' => 10,
                    'vat_rate' => 21,
                    'default_currency' => 'EUR',
                    'payout_delay_days' => 7,
                    'stripe_enabled' => true,
                    'auto_payout' => false
                ];

            case 'notification':
                return [
                    'email_new_booking' => true,
                    'email_booking_cancelled' => true,
                    'email_payment_received' => true,
                    'email_lesson_reminder' => true,
                    'sms_new_booking' => false,
                    'sms_lesson_reminder' => false
                ];

            default:
                return [];
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus()
    {
        $services = [
            [
                'name' => 'API Backend',
                'status' => 'online',
                'description' => 'Service principal'
            ],
            [
                'name' => 'Base de données',
                'status' => $this->checkDatabaseStatus(),
                'description' => 'MySQL'
            ],
            [
                'name' => 'Cache Redis',
                'status' => $this->checkRedisStatus(),
                'description' => 'Cache en mémoire'
            ],
            [
                'name' => 'Stockage',
                'status' => $this->checkStorageStatus(),
                'description' => 'Fichiers'
            ],
        ];

        return response()->json(['services' => $services]);
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'cache_cleared',
                'model_type' => 'System',
                'data' => ['timestamp' => now()],
            ]);

            return response()->json(['message' => 'Cache vidé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du vidage du cache'], 500);
        }
    }

    /**
     * Helpers
     */
    private function formatActivityMessage($log)
    {
        $messages = [
            'user_created' => 'Utilisateur créé : ' . ($log->data['name'] ?? 'N/A'),
            'user_updated' => 'Utilisateur modifié : ' . ($log->data['new']['name'] ?? 'N/A'),
            'user_status_toggled' => 'Statut utilisateur modifié',
            'settings_updated' => 'Paramètres système mis à jour',
            'cache_cleared' => 'Cache système vidé',
        ];

        return $messages[$log->action] ?? $log->action;
    }

    private function getActivityIcon($action)
    {
        $icons = [
            'user_created' => 'helmet',
            'user_updated' => 'saddle',
            'user_status_toggled' => 'horseshoe',
            'settings_updated' => 'trophy',
            'cache_cleared' => 'horse',
        ];

        return $icons[$action] ?? 'horseshoe';
    }

    private function checkDatabaseStatus()
    {
        try {
            DB::connection()->getPdo();
            return 'online';
        } catch (\Exception $e) {
            return 'offline';
        }
    }

    private function checkRedisStatus()
    {
        try {
            Cache::store('redis')->put('test', 'value', 1);
            return 'online';
        } catch (\Exception $e) {
            return 'offline';
        }
    }

    private function checkStorageStatus()
    {
        try {
            return Storage::disk('public')->exists('') ? 'online' : 'offline';
        } catch (\Exception $e) {
            return 'offline';
        }
    }
}
