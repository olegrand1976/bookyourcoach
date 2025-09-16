<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthControllerSimple;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\FileUploadController;

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
Route::get('/auth/user-test', [AuthControllerSimple::class, 'userTest']);

// Routes protégées avec authentification manuelle
Route::group([], function () {
    Route::post('/auth/logout', [AuthControllerSimple::class, 'logout']);
    Route::get('/auth/user', [AuthControllerSimple::class, 'user']);
    
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
        
        return response()->json([
            'success' => true,
            'data' => App\Models\User::all()
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
        
        $targetUser = App\Models\User::findOrFail($id);
        $targetUser->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Statut utilisateur mis à jour'
        ]);
    });

    Route::post('/upload-logo', function(Request $request) {
        try {
            if (!$request->hasFile('logo')) {
                return response()->json(['error' => 'Aucun fichier fourni'], 400);
            }
            
            $file = $request->file('logo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('logos', $fileName, 'public');

        return response()->json([
            'success' => true,
                'message' => 'Logo uploadé avec succès',
                'logo_url' => url('storage/' . $path)
        ]);
        } catch (Exception $e) {
        return response()->json([
                'error' => true,
                'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
});

        Route::get('/settings/{type}', [AdminController::class, 'getSettings']);
        Route::put('/settings/{type}', [AdminController::class, 'updateSettings']);
        
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
                'data' => [
                    'stats' => [
                        'total_users' => App\Models\User::count(),
                        'total_teachers' => App\Models\User::where('role', 'teacher')->count(),
                        'total_students' => App\Models\User::where('role', 'student')->count(),
                        'total_clubs' => App\Models\Club::count(),
                        'total_lessons' => App\Models\Lesson::count(),
                        'total_payments' => App\Models\Payment::count(),
                        'revenue_this_month' => App\Models\Payment::whereMonth('created_at', now()->month)->sum('amount'),
                    ]
                ]
            ]);
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
            
            if ($validator->fails()) {
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
                'message' => 'Settings updated successfully'
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

