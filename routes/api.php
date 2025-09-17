<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\AuthControllerSimple;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\FileUploadController;
// use App\Http\Controllers\Api\GraphAnalyticsController;
use App\Http\Controllers\Api\Teacher\DashboardController;

// Routes publiques (CORS géré par config/cors.php)
Route::get('/activity-types', function() {
    return response()->json([
        'success' => true,
        'data' => App\Models\ActivityType::all()
    ]);
});

// Authentification
Route::post('/auth/register', [AuthControllerSimple::class, 'register']);
Route::post('/auth/login', [AuthControllerSimple::class, 'login']);
//Route::get('/auth/user-test', [AuthControllerSimple::class, 'userTest']);

// Route user en dehors du groupe pour éviter les middlewares
Route::get('/auth/user', [AuthControllerSimple::class, 'user']);

// Route de test pour isoler le problème
Route::get('/auth/user-simple', function() {
    return response()->json([
        'user' => [
            'id' => 2,
            'name' => 'Sophie Martin',
            'email' => 'sophie.martin@activibe.com',
            'role' => 'teacher',
            'is_active' => true,
        ]
    ], 200);
});

// Routes protégées avec authentification manuelle
Route::group([], function () {
    Route::post('/auth/logout', [AuthControllerSimple::class, 'logout']);
    
    // Routes utilisateurs
    Route::get('/users', function() {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        return response()->json([
            'users' => App\Models\User::all()
        ]);
    });
    
    // Routes profils
    Route::get('/profiles', function() {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        return response()->json([
            'profiles' => App\Models\Profile::all()
        ]);
    });
    
    Route::post('/profiles', function(Request $request) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        // Validation des données
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $request->merge(['user_id' => $user->id]);
        
        $profile = App\Models\Profile::create($request->all());
        
        return response()->json([
            'message' => 'Profile created successfully',
            'profile' => $profile
        ], 201);
    });
    
    Route::get('/profiles/{id}', function($id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $profile = App\Models\Profile::with('user')->findOrFail($id);
        
        return response()->json([
            'profile' => $profile
        ]);
    });
    
    Route::put('/profiles/{id}', function(Request $request, $id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $profile = App\Models\Profile::findOrFail($id);
        $profile->update($request->all());
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    });
    
    Route::delete('/profiles/{id}', function($id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $profile = App\Models\Profile::findOrFail($id);
        $profile->delete();
        
        return response()->json([
            'message' => 'Profile deleted successfully'
        ]);
    });
    
    // Upload avec authentification
    Route::post('/upload/logo', [FileUploadController::class, 'uploadLogo']);
});

// Routes admin avec authentification manuelle
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function() {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_users' => App\Models\User::count(),
                    'total_teachers' => App\Models\User::where('role', 'teacher')->count(),
                    'total_students' => App\Models\User::where('role', 'student')->count(),
                    'total_clubs' => App\Models\Club::count(),
                    'total_lessons' => App\Models\Lesson::count(),
                    'total_payments' => App\Models\Payment::count(),
                    'revenue_this_month' => App\Models\Payment::whereMonth('created_at', now()->month)->sum('amount'),
                ],
                'recent_users' => App\Models\User::latest()->take(5)->get(),
                'recent_lessons' => App\Models\Lesson::latest()->take(5)->get(),
            ]
        ]);
    });
    
    Route::get('/users', function() {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        // Récupérer les paramètres de filtrage
        $search = request('search');
        $role = request('role');
        $status = request('status');
        $postal_code = request('postal_code');
        $page = request('page', 1);
        $per_page = request('per_page', 10);
        
        // Construire la requête avec filtres
        $query = App\Models\User::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        if ($role) {
            $query->where('role', $role);
        }
        
        if ($status) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        if ($postal_code) {
            $query->where('postal_code', $postal_code);
        }
        
        // Pagination
        $users = $query->orderBy('created_at', 'desc')->paginate($per_page, ['*'], 'page', $page);
        
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
    });
    
    Route::put('/users/{id}/status', function(Request $request, $id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetUser = App\Models\User::findOrFail($id);
        $targetUser->update(['is_active' => $request->is_active]);
        
    return response()->json([
            'success' => true,
            'message' => 'Statut utilisateur mis à jour'
    ]);
});

    // Route pour créer un nouvel utilisateur
    Route::post('/users', function(Request $request) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        // Validation des données
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,student',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Construire le nom complet
        $fullName = trim($request->first_name . ' ' . $request->last_name);

        $newUser = App\Models\User::create([
            'name' => $fullName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
            'street' => $request->street,
            'street_number' => $request->street_number,
            'postal_code' => $request->postal_code,
            'city' => $request->city,
            'country' => $request->country,
            'is_active' => true,
            'status' => 'active',
        ]);

        return response()->json($newUser, 201);
    });
    
    // Route pour mettre à jour un utilisateur
    Route::put('/users/{id}', function(Request $request, $id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        $targetUser = App\Models\User::findOrFail($id);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:admin,teacher,student',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Préparer les données de mise à jour
        $updateData = $request->only([
            'first_name', 'last_name', 'email', 'role', 'phone', 
            'birth_date', 'street', 'street_number', 'postal_code', 
            'city', 'country'
        ]);
        
        // Reconstruire le nom complet si first_name ou last_name sont modifiés
        if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
            $firstName = $updateData['first_name'] ?? $targetUser->first_name;
            $lastName = $updateData['last_name'] ?? $targetUser->last_name;
            $updateData['name'] = trim($firstName . ' ' . $lastName);
        }
        
        // Ajouter le mot de passe si fourni
        if ($request->filled('password')) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        
        $targetUser->update($updateData);

        return response()->json($targetUser);
    });
    
    // Route pour basculer le statut d'un utilisateur
    Route::patch('/users/{id}/toggle-status', function($id) {
        $token = request()->header('Authorization');
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }
        
        $targetUser = App\Models\User::findOrFail($id);

        // Empêcher la désactivation du dernier admin
        if ($targetUser->role === 'admin' && $targetUser->is_active) {
            $activeAdmins = App\Models\User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return response()->json(['message' => 'Impossible de désactiver le dernier administrateur'], 422);
            }
        }

        $targetUser->is_active = !$targetUser->is_active;
        $targetUser->status = $targetUser->is_active ? 'active' : 'inactive';
        $targetUser->save();

        return response()->json($targetUser);
    });

    Route::put('/settings/{type}', function(Request $request, $type) {
        $token = request()->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Missing token'], 401);
        }

        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$personalAccessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = $personalAccessToken->tokenable;

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied - Admin rights required'], 403);
        }

        try {
            // Validation selon le type
            $validationRules = [];
            
            switch ($type) {
                case 'general':
                    $validationRules = [
                        'platform_name' => 'sometimes|string|max:255',
                        'logo_url' => 'sometimes|string|max:500',
                        'contact_email' => 'sometimes|email|max:255',
                        'contact_phone' => 'sometimes|string|max:50',
                        'timezone' => 'sometimes|string|max:100',
                        'company_address' => 'sometimes|string|max:1000'
                    ];
                    break;
                    
                case 'booking':
                    $validationRules = [
                        'min_booking_hours' => 'sometimes|integer|min:1|max:24',
                        'max_booking_days' => 'sometimes|integer|min:1|max:365',
                        'cancellation_hours' => 'sometimes|integer|min:1|max:168',
                        'default_lesson_duration' => 'sometimes|integer|min:15|max:480',
                        'auto_confirm_bookings' => 'sometimes|boolean',
                        'send_reminder_emails' => 'sometimes|boolean',
                        'allow_student_cancellation' => 'sometimes|boolean'
                    ];
                    break;
                    
                case 'payment':
                    $validationRules = [
                        'platform_commission' => 'sometimes|numeric|min:0|max:100',
                        'vat_rate' => 'sometimes|numeric|min:0|max:100',
                        'default_currency' => 'sometimes|string|size:3',
                        'payout_delay_days' => 'sometimes|integer|min:0|max:30',
                        'stripe_enabled' => 'sometimes|boolean',
                        'auto_payout' => 'sometimes|boolean'
                    ];
                    break;
                    
                case 'notifications':
                    $validationRules = [
                        'email_new_booking' => 'sometimes|boolean',
                        'email_booking_cancelled' => 'sometimes|boolean',
                        'email_payment_received' => 'sometimes|boolean',
                        'email_lesson_reminder' => 'sometimes|boolean',
                        'sms_new_booking' => 'sometimes|boolean',
                        'sms_lesson_reminder' => 'sometimes|boolean'
                    ];
                    break;
                    
                default:
                    return response()->json(['message' => 'Invalid settings type'], 400);
            }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Sauvegarder les paramètres
            foreach ($request->all() as $key => $value) {
                App\Models\AppSetting::updateOrCreate(
                    [
                        'key' => $type . '.' . $key,
                        'group' => $type
                    ],
                    [
                        'value' => is_array($value) ? json_encode($value) : (string)$value,
                        'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? (is_float($value) ? 'float' : 'integer') : (is_array($value) ? 'array' : 'string')),
                        'is_active' => true
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètres sauvegardés avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], 500);
        }
});

    Route::get('/settings/{type}', function($type) {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            try {
                // Paramètres par défaut selon le type
                $defaultSettings = [];
                
                switch ($type) {
                    case 'general':
                        $defaultSettings = [
                            'platform_name' => 'activibe',
                            'logo_url' => '/logo-activibe.svg',
                            'contact_email' => 'contact@activibe.fr',
                            'contact_phone' => '+33 1 23 45 67 89',
                            'timezone' => 'Europe/Brussels',
                            'company_address' => 'activibe\nBelgique'
                        ];
                        break;
                        
                    case 'booking':
                        $defaultSettings = [
                            'min_booking_hours' => 2,
                            'max_booking_days' => 30,
                            'cancellation_hours' => 24,
                            'default_lesson_duration' => 60,
                            'auto_confirm_bookings' => true,
                            'send_reminder_emails' => true,
                            'allow_student_cancellation' => true
                        ];
                        break;
                        
                    case 'payment':
                        $defaultSettings = [
                            'platform_commission' => 10,
                            'vat_rate' => 21,
                            'default_currency' => 'EUR',
                            'payout_delay_days' => 7,
                            'stripe_enabled' => true,
                            'auto_payout' => false
                        ];
                        break;
                        
                    case 'notifications':
                        $defaultSettings = [
                            'email_new_booking' => true,
                            'email_booking_cancelled' => true,
                            'email_payment_received' => true,
                            'email_lesson_reminder' => true,
                            'sms_new_booking' => false,
                            'sms_lesson_reminder' => false
                        ];
                        break;
                        
                    default:
                        return response()->json(['message' => 'Invalid settings type'], 400);
                }
                
                // Récupérer les paramètres sauvegardés depuis la base de données
                $savedSettings = App\Models\AppSetting::where('group', $type)->get();
                
                // Fusionner avec les valeurs par défaut
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
                
                return response()->json($defaultSettings);
                
            } catch (\Exception $e) {
                return response()->json(['message' => 'Erreur lors du chargement des paramètres: ' . $e->getMessage()], 500);
            }
        });
        
        Route::put('/settings/{type}', function(Request $request, $type) {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            try {
                $settings = $request->all();
                
                // Valider les données selon le type
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
                        
                    case 'notifications':
                        $rules = [
                            'email_new_booking' => 'required|boolean',
                            'email_booking_cancelled' => 'required|boolean',
                            'email_payment_received' => 'required|boolean',
                            'email_lesson_reminder' => 'required|boolean',
                            'sms_new_booking' => 'required|boolean',
                            'sms_lesson_reminder' => 'required|boolean'
                        ];
                        break;
                        
                    default:
                        return response()->json(['message' => 'Invalid settings type'], 400);
                }
                
                $validator = \Illuminate\Support\Facades\Validator::make($settings, $rules);
                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'Données invalides',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                // Sauvegarder chaque paramètre dans la base de données
                foreach ($settings as $key => $value) {
                    // Déterminer le type de valeur
                    $valueType = 'string';
                    if (is_bool($value)) {
                        $valueType = 'boolean';
                    } elseif (is_int($value)) {
                        $valueType = 'integer';
                    } elseif (is_float($value)) {
                        $valueType = 'float';
                    } elseif (is_array($value)) {
                        $valueType = 'array';
                    }
                    
                    App\Models\AppSetting::updateOrCreate(
                        [
                            'key' => "{$type}.{$key}",
                            'group' => $type
                        ],
                        [
                            'value' => is_array($value) ? json_encode($value) : (string)$value,
                            'type' => $valueType,
                            'is_active' => true
                        ]
                    );
                }

        return response()->json([
                    'message' => 'Paramètres mis à jour avec succès',
                    'settings' => $settings
        ]);
                
            } catch (\Exception $e) {
        return response()->json([
                    'message' => 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage()
        ], 500);
    }
});

        // Routes supplémentaires pour AdminControllerTest
        Route::get('/stats', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'total_users' => App\Models\User::count(),
                    'total_teachers' => App\Models\User::where('role', 'teacher')->count(),
                    'total_students' => App\Models\User::where('role', 'student')->count(),
                    'total_clubs' => App\Models\Club::count(),
                    'total_lessons' => App\Models\Lesson::count(),
                    'total_payments' => App\Models\Payment::count(),
                    'revenue_this_month' => App\Models\Payment::whereMonth('created_at', now()->month)->sum('amount'),
                ],
                'recentUsers' => App\Models\User::latest()->take(5)->get()
            ]);
        });
        
        // Route pour créer un nouveau club
        Route::post('/clubs', function(Request $request) {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:clubs',
                'phone' => 'nullable|string|max:20',
                'street' => 'nullable|string|max:255',
                'street_number' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'website' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $club = App\Models\Club::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'street' => $request->street,
                'street_number' => $request->street_number,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'Belgium',
                'description' => $request->description,
                'website' => $request->website,
                'is_active' => true,
            ]);

            return response()->json($club, 201);
        });

        // Route pour réinitialiser le mot de passe d'un utilisateur
        Route::post('/users/{id}/reset-password', function($id) {
            $token = request()->header('Authorization');

            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }

            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            $adminUser = $personalAccessToken->tokenable;

            if (!$adminUser || $adminUser->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }

            // Trouver l'utilisateur
            $user = App\Models\User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Générer un nouveau mot de passe temporaire
            $newPassword = 'temp' . rand(1000, 9999);
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'message' => 'Password reset successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'temporary_password' => $newPassword
            ], 200);
        });
        
        Route::patch('/users/{id}/role', function($id) {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
                'role' => 'required|in:admin,teacher,student,club'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $targetUser = App\Models\User::findOrFail($id);
            $targetUser->update(['role' => request('role')]);
            
            return response()->json([
                'message' => 'User role updated successfully',
                'user' => $targetUser
            ]);
        });
        
        Route::get('/settings', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            $settings = App\Models\AppSetting::where('is_active', true)->get();
            
            return response()->json([
                'settings' => $settings
            ]);
        });
        
        Route::put('/settings', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required'
            ]);
            
            // Validation supplémentaire pour les valeurs spécifiques
            $hasErrors = false;
            foreach (request('settings') as $index => $setting) {
                $key = $setting['key'];
                $value = $setting['value'];
                
                // Validation spécifique selon la clé
                if (str_contains($key, 'contact_email')) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $validator->errors()->add("settings.{$index}.value", "L'email n'est pas valide");
                        $hasErrors = true;
                    }
                }
                
                if (str_contains($key, 'platform_name') && strlen($value) > 255) {
                    $validator->errors()->add("settings.{$index}.value", "Le nom de la plateforme est trop long");
                    $hasErrors = true;
                }
                
                if (str_contains($key, 'min_booking_hours') && (!is_numeric($value) || $value < 1 || $value > 24)) {
                    $validator->errors()->add("settings.{$index}.value", "Les heures de réservation minimum doivent être entre 1 et 24");
                    $hasErrors = true;
                }
            }
            
            if ($validator->fails() || $hasErrors) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            foreach (request('settings') as $setting) {
                App\Models\AppSetting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value'], 'is_active' => true]
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Paramètres sauvegardés avec succès'
            ]);
        });
        
        Route::get('/audit-logs', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            $logs = App\Models\AuditLog::latest()->take(50)->get();
            
            return response()->json([
                'audit_logs' => $logs
            ]);
        });
        
        Route::post('/cache/clear', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            
            return response()->json([
                'message' => 'Cache cleared successfully'
            ]);
        });
        
        Route::post('/maintenance', function() {
            $token = request()->header('Authorization');
            
            if (!$token || !str_starts_with($token, 'Bearer ')) {
                return response()->json(['message' => 'Missing token'], 401);
            }
            
            $token = substr($token, 7);
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            
            $user = $personalAccessToken->tokenable;
            
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied - Admin rights required'], 403);
            }
            
            $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
                'command' => 'required|string|in:migrate,optimize,queue:restart'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            try {
                \Illuminate\Support\Facades\Artisan::call(request('command'));
                
                return response()->json([
                    'message' => 'Maintenance command executed successfully',
                    'output' => \Illuminate\Support\Facades\Artisan::output()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Maintenance command failed',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
});

// Route de test simple pour le dashboard enseignant
Route::get('/teacher/dashboard-simple', function() {
    return response()->json([
        'stats' => [
            'today_lessons' => 3,
            'active_students' => 12,
            'monthly_earnings' => 1250.50,
            'average_rating' => 4.8,
            'week_lessons' => 8,
            'week_hours' => 16.5,
            'week_earnings' => 420.75,
            'new_students' => 2,
        ],
        'upcomingLessons' => [
            [
                'id' => 1,
                'student_name' => 'Marie Dubois',
                'type' => 'Cours débutant',
                'start_time' => '2025-09-18 10:00:00',
                'end_time' => '2025-09-18 11:00:00',
                'status' => 'confirmed'
            ],
            [
                'id' => 2,
                'student_name' => 'Pierre Martin',
                'type' => 'Cours avancé',
                'start_time' => '2025-09-18 14:00:00',
                'end_time' => '2025-09-18 15:30:00',
                'status' => 'confirmed'
            ]
        ]
    ]);
});

// Routes Enseignant - Temporairement sans middleware pour debug
Route::prefix('teacher')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    Route::get('/lessons', [DashboardController::class, 'getLessons']);
    Route::post('/lessons', [DashboardController::class, 'createLesson']);
    Route::put('/lessons/{id}', [DashboardController::class, 'updateLesson']);
    Route::delete('/lessons/{id}', [DashboardController::class, 'deleteLesson']);
    Route::get('/availabilities', [DashboardController::class, 'getAvailabilities']);
    Route::post('/availabilities', [DashboardController::class, 'createAvailability']);
    Route::put('/availabilities/{id}', [DashboardController::class, 'updateAvailability']);
    Route::delete('/availabilities/{id}', [DashboardController::class, 'deleteAvailability']);
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/students', [DashboardController::class, 'getStudents']);
});

// Routes Graph Analytics (Neo4j)
Route::prefix('graph')->middleware(['auth:sanctum'])->group(function () {
    // Routes temporairement commentées à cause du problème de dépendance Neo4j
    // Route::get('/dashboard', [GraphAnalyticsController::class, 'getDashboard']);                                                                           
    // Route::get('/network-stats', [GraphAnalyticsController::class, 'getNetworkStats']);                                                                    
    // Route::get('/top-teachers', [GraphAnalyticsController::class, 'getTopTeachers']);                                                                      
    // Route::get('/skills-network', [GraphAnalyticsController::class, 'getSkillsNetwork']);                                                                  
    // Route::get('/student-progress', [GraphAnalyticsController::class, 'getStudentProgress']);                                                              
    // Route::get('/recommendations', [GraphAnalyticsController::class, 'getRecommendations']);                                                               
    // Route::post('/teacher-matching', [GraphAnalyticsController::class, 'getTeacherMatching']);                                                             
    // Route::post('/teacher-performance', [GraphAnalyticsController::class, 'getTeacherPerformance']);                                                       
    // Route::post('/predict-success', [GraphAnalyticsController::class, 'predictStudentSuccess']);                                                           
    // Route::get('/visualization', [GraphAnalyticsController::class, 'getGraphVisualization']);                                                              
    // Route::post('/sync', [GraphAnalyticsController::class, 'syncAllData']);
    // Route::get('/status', [GraphAnalyticsController::class, 'getStatus']);
});

