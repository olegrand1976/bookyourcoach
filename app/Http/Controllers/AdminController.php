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
        // Le middleware 'admin' est appliqué au niveau des routes
        // Pas besoin de middleware Sanctum ici pour éviter SIGSEGV
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

        // Récupérer les utilisateurs récents
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();

        // Récupérer les activités récentes
        $recentActivities = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'message' => $this->formatActivityMessage($log),
                    'created_at' => $log->created_at,
                    'user' => $log->user ? $log->user->name : 'Système',
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentActivities' => $recentActivities
        ]);
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
     *             required={"first_name","last_name","email","password","role"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin","teacher","student"}),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="street", type="string"),
     *             @OA\Property(property="street_number", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="birth_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created successfully")
     * )
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,student',
            'phone' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $name = trim($request->first_name . ' ' . $request->last_name);

        $user = User::create([
            'name' => $name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'street' => $request->street,
            'street_number' => $request->street_number,
            'postal_code' => $request->postal_code,
            'city' => $request->city,
            'country' => $request->country,
            'birth_date' => $request->birth_date,
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
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin","teacher","student"}),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="street", type="string"),
     *             @OA\Property(property="street_number", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="birth_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated successfully")
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:admin,teacher,student',
            'phone' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only([
            'first_name', 'last_name', 'email', 'role', 'phone', 'street', 'street_number', 'postal_code', 'city', 'country', 'birth_date'
        ]);

        if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
            $updateData['name'] = trim(($updateData['first_name'] ?? $user->first_name) . ' ' . ($updateData['last_name'] ?? $user->last_name));
        }

        $oldData = $user->toArray();
        $user->update($updateData);

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
            $settings = $request->all();

            // Valider les données selon le type
            $validator = $this->validateSettings($settings, $type);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Sauvegarder chaque paramètre dans la base de données
            foreach ($settings as $key => $value) {
                AppSetting::updateOrCreate(
                    [
                        'key' => "{$type}.{$key}",
                        'group' => $type
                    ],
                    [
                        'value' => is_array($value) ? json_encode($value) : (string)$value,
                        'type' => $this->getValueType($value)
                    ]
                );
            }

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'settings_updated',
                'model_type' => 'Settings',
                'data' => ['type' => $type, 'settings' => $settings],
            ]);

            return response()->json([
                'message' => 'Paramètres mis à jour avec succès',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default settings by type
     */
    private function getDefaultSettings($type)
    {
        // Récupérer les paramètres depuis la base de données ou utiliser les valeurs par défaut
        $defaultSettings = $this->getDefaultSettingsArray($type);
        $savedSettings = AppSetting::where('group', $type)->get();

        // Fusionner les paramètres sauvegardés avec les valeurs par défaut
        foreach ($savedSettings as $setting) {
            $key = str_replace($type . '.', '', $setting->key);
            $value = $setting->value;

            // Convertir selon le type
            switch ($setting->type) {
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'array':
                    $value = json_decode($value, true);
                    break;
                case 'float':
                    $value = (float)$value;
                    break;
                default:
                    // string - garder tel quel
                    break;
            }

            $defaultSettings[$key] = $value;
        }

        return $defaultSettings;
    }

    /**
     * Get default settings array by type
     */
    private function getDefaultSettingsArray($type)
    {
        switch ($type) {
            case 'general':
                return [
                    'platform_name' => 'activibe',
                    'logo_url' => '/logo.svg',
                    'contact_email' => 'contact@activibe.fr',
                    'contact_phone' => '+33 1 23 45 67 89',
                    'timezone' => 'Europe/Brussels',
                    'company_address' => 'activibe\nBelgique'
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
     * Validate settings based on type
     */
    private function validateSettings($settings, $type)
    {
        $rules = [];

        switch ($type) {
            case 'general':
                $rules = [
                    'platform_name' => 'required|string|max:255',
                    'contact_email' => 'required|email|max:255',
                    'contact_phone' => 'nullable|string|max:50',
                    'timezone' => 'required|string|max:50',
                    'company_address' => 'nullable|string|max:1000'
                ];
                break;

            case 'booking':
                $rules = [
                    'min_booking_hours' => 'required|integer|min:1|max:48',
                    'max_booking_days' => 'required|integer|min:1|max:365',
                    'cancellation_hours' => 'required|integer|min:1|max:168',
                    'default_lesson_duration' => 'required|integer|min:15|max:480',
                    'auto_confirm_bookings' => 'required|boolean',
                    'send_reminder_emails' => 'required|boolean',
                    'allow_student_cancellation' => 'required|boolean'
                ];
                break;

            case 'payment':
                $rules = [
                    'platform_commission' => 'required|numeric|min:0|max:50',
                    'vat_rate' => 'required|numeric|min:0|max:100',
                    'default_currency' => 'required|string|size:3',
                    'payout_delay_days' => 'required|integer|min:1|max:30',
                    'stripe_enabled' => 'required|boolean',
                    'auto_payout' => 'required|boolean'
                ];
                break;

            case 'notification':
                $rules = [
                    'email_new_booking' => 'required|boolean',
                    'email_booking_cancelled' => 'required|boolean',
                    'email_payment_received' => 'required|boolean',
                    'email_lesson_reminder' => 'required|boolean',
                    'sms_new_booking' => 'required|boolean',
                    'sms_lesson_reminder' => 'required|boolean'
                ];
                break;
        }

        return Validator::make($settings, $rules);
    }

    /**
     * Get value type for database storage
     */
    private function getValueType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'array';
        } else {
            return 'string';
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

    // =================================
    // GESTION DES CLUBS
    // =================================

    /**
     * @OA\Get(
     *     path="/api/admin/clubs",
     *     summary="Get all clubs",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Clubs retrieved successfully")
     * )
     */
    public function getClubs()
    {
        $clubs = Club::with(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email', 'users.role')
                ->withPivot('role', 'is_admin', 'joined_at');
        }])
            ->withCount(['users', 'teachers', 'students', 'admins'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($clubs);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/clubs",
     *     summary="Create a new club",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="website", type="string"),
     *             @OA\Property(property="facilities", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="disciplines", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="max_students", type="integer"),
     *             @OA\Property(property="subscription_price", type="number")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Club created successfully")
     * )
     */
    public function createClub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clubs,email',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string|max:255',
            'disciplines' => 'nullable|array',
            'disciplines.*' => 'string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'subscription_price' => 'nullable|numeric|min:0',
            'terms_and_conditions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $club = Club::create($request->all());

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'club_created',
                'description' => "Club créé: {$club->name}",
                'model_type' => Club::class,
                'model_id' => $club->id,
                'changes' => $club->toArray()
            ]);

            return response()->json([
                'message' => 'Club créé avec succès',
                'club' => $club
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/clubs/{id}",
     *     summary="Get a specific club",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Club retrieved successfully")
     * )
     */
    public function getClub($id)
    {
        $club = Club::with(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email', 'users.role')
                ->withPivot('role', 'is_admin', 'joined_at');
        }])->findOrFail($id);

        return response()->json($club);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/clubs/{id}",
     *     summary="Update a club",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=200, description="Club updated successfully")
     * )
     */
    public function updateClub(Request $request, $id)
    {
        $club = Club::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clubs,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string|max:255',
            'disciplines' => 'nullable|array',
            'disciplines.*' => 'string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'subscription_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'terms_and_conditions' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $originalData = $club->toArray();
            $club->update($request->all());

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'club_updated',
                'description' => "Club modifié: {$club->name}",
                'model_type' => Club::class,
                'model_id' => $club->id,
                'changes' => [
                    'before' => $originalData,
                    'after' => $club->fresh()->toArray()
                ]
            ]);

            return response()->json([
                'message' => 'Club mis à jour avec succès',
                'club' => $club->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/clubs/{id}",
     *     summary="Delete a club",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Club deleted successfully")
     * )
     */
    public function deleteClub($id)
    {
        $club = Club::findOrFail($id);

        try {
            $clubName = $club->name;
            $club->delete();

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'club_deleted',
                'description' => "Club supprimé: {$clubName}",
                'model_type' => Club::class,
                'model_id' => $id,
                'changes' => ['deleted' => $club->toArray()]
            ]);

            return response()->json([
                'message' => 'Club supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/clubs/{id}/toggle-status",
     *     summary="Toggle club active status",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Club status updated successfully")
     * )
     */
    public function toggleClubStatus($id)
    {
        $club = Club::findOrFail($id);

        try {
            $club->is_active = !$club->is_active;
            $club->save();

            $status = $club->is_active ? 'activé' : 'désactivé';

            // Log de l'action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'club_status_changed',
                'description' => "Club {$status}: {$club->name}",
                'model_type' => Club::class,
                'model_id' => $club->id,
                'changes' => ['is_active' => $club->is_active]
            ]);

            return response()->json([
                'message' => "Club {$status} avec succès",
                'club' => $club
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/upload-logo",
     *     summary="Upload platform logo",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="logo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Logo uploaded successfully")
     * )
     */
    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();

            // Stocker dans public/storage/logos
            $path = $file->storeAs('logos', $filename, 'public');
            $url = '/storage/' . $path;

            // Mettre à jour le paramètre logo_url
            AppSetting::updateOrCreate(
                ['key' => 'logo_url'],
                ['value' => $url, 'type' => 'general']
            );

            return response()->json([
                'message' => 'Logo uploadé avec succès',
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
