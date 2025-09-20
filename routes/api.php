<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\AuthControllerSimple;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\FileUploadController;
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
Route::post('/auth/forgot-password', [AuthControllerSimple::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthControllerSimple::class, 'resetPassword']);
//Route::get('/auth/user-test', [AuthControllerSimple::class, 'userTest']);

// Route user en dehors du groupe pour éviter les middlewares
Route::get('/auth/user', [AuthControllerSimple::class, 'user']);

// Routes pour le calendrier enseignant
Route::get('/teacher/calendar', function(Request $request) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        $calendarId = $request->query('calendar_id', 'personal');
        
        // Récupérer les événements selon le calendrier sélectionné
        if ($calendarId === 'personal') {
            $events = \DB::table('lessons')
                ->where('teacher_id', $user->id)
                ->where('club_id', null)
                ->orderBy('start_time')
                ->get();
        } else {
            $events = \DB::table('lessons')
                ->where('teacher_id', $user->id)
                ->where('club_id', $calendarId)
                ->orderBy('start_time')
                ->get();
        }
        
        return response()->json([
            'success' => true,
            'events' => $events
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/calendar: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

Route::get('/teacher/students', function(Request $request) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        // Récupérer les élèves de l'enseignant
        $students = \DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.teacher_id', $user->id)
            ->select('users.id', 'users.name', 'users.email')
            ->get();
        
        return response()->json([
            'success' => true,
            'students' => $students
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/students: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

Route::get('/teacher/clubs', function(Request $request) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        // Récupérer les clubs de l'enseignant
        $clubs = \DB::table('club_teachers')
            ->join('clubs', 'club_teachers.club_id', '=', 'clubs.id')
            ->where('club_teachers.teacher_id', $user->id)
            ->select('clubs.id', 'clubs.name', 'clubs.description')
            ->get();
        
        return response()->json([
            'success' => true,
            'clubs' => $clubs
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/clubs: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

Route::post('/teacher/lessons', function(Request $request) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        // Validation des données
        $request->validate([
            'title' => 'required|string|max:255',
            'student_id' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration' => 'required|integer|min:30|max:180',
            'type' => 'required|in:lesson,group,training,competition',
            'description' => 'nullable|string',
            'calendar_id' => 'required|string'
        ]);
        
        // Créer le cours
        $lessonId = \DB::table('lessons')->insertGetId([
            'title' => $request->title,
            'teacher_id' => $user->id,
            'student_id' => $request->student_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration' => $request->duration,
            'type' => $request->type,
            'description' => $request->description,
            'club_id' => $request->calendar_id !== 'personal' ? $request->calendar_id : null,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'lesson_id' => $lessonId,
            'message' => 'Cours créé avec succès'
        ], 201);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/lessons: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

Route::delete('/teacher/lessons/{id}', function(Request $request, $id) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        
        // Vérifier que le cours appartient à l'enseignant
        $lesson = \DB::table('lessons')->where('id', $id)->where('teacher_id', $user->id)->first();
        
        if (!$lesson) {
            return response()->json(['error' => 'Cours non trouvé'], 404);
        }
        
        // Supprimer le cours
        \DB::table('lessons')->where('id', $id)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cours supprimé avec succès'
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/lessons/{id}: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

Route::post('/teacher/calendar/sync-google', function(Request $request) {
    try {
        $token = $request->header('Authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $token = substr($token, 7);
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $user = $personalAccessToken->tokenable;
        $calendarId = $request->input('calendar_id', 'personal');
        
        // TODO: Implémenter la synchronisation avec Google Calendar
        // Pour l'instant, on simule la synchronisation
        
        \Log::info("Synchronisation Google Calendar pour l'enseignant {$user->id}, calendrier: {$calendarId}");
        
        return response()->json([
            'success' => true,
            'message' => 'Synchronisation Google Calendar en cours...'
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /teacher/calendar/sync-google: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur interne'], 500);
    }
});

// Routes pour l'intégration Google Calendar
Route::get('/google-calendar/auth-url', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'getAuthUrl']);
Route::post('/google-calendar/callback', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'handleCallback']);
Route::get('/google-calendar/calendars', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'getCalendars']);
Route::post('/google-calendar/sync-events', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'syncEvents']);
Route::post('/google-calendar/events', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'createEvent']);
Route::put('/google-calendar/events/{eventId}', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'updateEvent']);
Route::delete('/google-calendar/events/{eventId}', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'deleteEvent']);
Route::get('/google-calendar/events', [\App\Http\Controllers\Api\GoogleCalendarController::class, 'getEvents']);

// Route de diagnostic pour l'erreur 500
Route::get('/auth/user-debug', function(Request $request) {
    try {
        \Log::info('Route /auth/user-debug appelée');
        
        $token = $request->header('Authorization');
        \Log::info('Token reçu: ' . ($token ? 'Présent' : 'Absent'));
        
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['error' => 'Token manquant ou format invalide'], 401);
        }
        
        $token = substr($token, 7);
        \Log::info('Token nettoyé: ' . substr($token, 0, 10) . '...');
        
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken) {
            return response()->json(['error' => 'Token Sanctum non trouvé'], 401);
        }
        
        \Log::info('Token Sanctum trouvé pour user ID: ' . $personalAccessToken->tokenable_id);
        
        $user = $personalAccessToken->tokenable;
        \Log::info('User model récupéré: ' . $user->id);
        
        $userData = \DB::table('users')->where('id', $user->id)->first();
        
        if (!$userData) {
            return response()->json(['error' => 'User non trouvé en DB pour ID: ' . $user->id], 404);
        }
        
        return response()->json([
            'success' => true,
            'user_id' => $userData->id,
            'user_name' => $userData->name ?? 'Non renseigné',
            'user_email' => $userData->email,
            'user_role' => $userData->role,
            'user_phone' => $userData->phone ?? null,
            'user_city' => $userData->city ?? null,
            'user_country' => $userData->country ?? null
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans /auth/user-debug: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'error' => 'Erreur interne',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

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
    
    // Route de test pour le profil (utilisée par le frontend)
    Route::get('/profile-test', function() {
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
        
        // Récupérer les données de profil
        $profile = \DB::table('profiles')->where('user_id', $user->id)->first();
        
        $response = [
            'profile' => $profile ? [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'phone' => $profile->phone,
                'address' => $profile->address,
                'city' => $profile->city,
                'postal_code' => $profile->postal_code,
                'country' => $profile->country,
                'date_of_birth' => $profile->date_of_birth,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ] : null
        ];
        
        // Ajouter les données spécifiques au rôle
        if ($user->role === 'teacher') {
            $teacher = \DB::table('teachers')->where('user_id', $user->id)->first();
            $response['teacher'] = $teacher ? [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'specialties' => $teacher->specialties,
                'experience_years' => $teacher->experience_years,
                'certifications' => $teacher->certifications,
                'hourly_rate' => $teacher->hourly_rate,
                'bio' => $teacher->bio,
                'is_available' => $teacher->is_available,
                'created_at' => $teacher->created_at,
                'updated_at' => $teacher->updated_at,
            ] : null;
        }
        
        if ($user->role === 'student') {
            $student = \DB::table('students')->where('user_id', $user->id)->first();
            $response['student'] = $student ? [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'level' => $student->level,
                'course_preferences' => $student->course_preferences,
                'emergency_contact' => $student->emergency_contact,
                'medical_notes' => $student->medical_notes,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at,
            ] : null;
        }
        
        return response()->json($response, 200);
    });
    
    Route::put('/profile-test', function(Request $request) {
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            // Teacher specific
            'specialties' => 'nullable|string|max:500',
            'experience_years' => 'nullable|integer|min:0',
            'certifications' => 'nullable|string|max:500',
            'hourly_rate' => 'nullable|numeric|min:0',
            // Student specific
            'riding_level' => 'nullable|string|max:50',
            'course_preferences' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Mettre à jour les données utilisateur de base
        \DB::table('users')->where('id', $user->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'updated_at' => now(),
        ]);
        
        // Mettre à jour ou créer le profil
        $profileData = [
            'user_id' => $user->id,
            'phone' => $request->phone,
            'date_of_birth' => $request->birth_date,
            'updated_at' => now(),
        ];
        
        $existingProfile = \DB::table('profiles')->where('user_id', $user->id)->first();
        
        if ($existingProfile) {
            \DB::table('profiles')->where('user_id', $user->id)->update($profileData);
        } else {
            $profileData['created_at'] = now();
            \DB::table('profiles')->insert($profileData);
        }
        
        // Mettre à jour les données spécifiques au rôle
        if ($user->role === 'teacher') {
            $teacherData = [
                'user_id' => $user->id,
                'specialties' => $request->specialties,
                'experience_years' => $request->experience_years,
                'certifications' => $request->certifications,
                'hourly_rate' => $request->hourly_rate,
                'updated_at' => now(),
            ];
            
            $existingTeacher = \DB::table('teachers')->where('user_id', $user->id)->first();
            
            if ($existingTeacher) {
                \DB::table('teachers')->where('user_id', $user->id)->update($teacherData);
            } else {
                $teacherData['created_at'] = now();
                \DB::table('teachers')->insert($teacherData);
            }
        }
        
        if ($user->role === 'student') {
            $studentData = [
                'user_id' => $user->id,
                'level' => $request->riding_level,
                'course_preferences' => $request->course_preferences,
                'emergency_contact' => $request->emergency_contact,
                'updated_at' => now(),
            ];
            
            $existingStudent = \DB::table('students')->where('user_id', $user->id)->first();
            
            if ($existingStudent) {
                \DB::table('students')->where('user_id', $user->id)->update($studentData);
            } else {
                $studentData['created_at'] = now();
                \DB::table('students')->insert($studentData);
            }
        }
        
        return response()->json([
            'message' => 'Profile updated successfully'
        ], 200);
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


