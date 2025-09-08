<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\CourseTypeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\Student\DashboardController;
use App\Http\Controllers\Api\Student\PreferencesController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\FinancialDashboardController;
use App\Http\Controllers\Api\ClubSettingsController;
use App\Http\Controllers\Api\GraphAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (sans middleware)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes pour les activités et disciplines (publiques)
Route::get('/activity-types', function() {
    $activities = App\Models\ActivityType::where('is_active', true)->get();
    return response()->json([
        'success' => true,
        'data' => $activities
    ]);
});

Route::get('/disciplines', function(Request $request) {
    $query = App\Models\Discipline::query();
    
    if ($request->has('activity_type_id')) {
        $query->where('activity_type_id', $request->activity_type_id);
    }
    
    $disciplines = $query->get();
    return response()->json([
        'success' => true,
        'data' => $disciplines
    ]);
});

// Route temporaire pour tester l'auth (sans auth)
Route::get('/auth/user-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $userData = $user->toArray();
    $userData['can_act_as_teacher'] = $user->canActAsTeacher();
    $userData['can_act_as_student'] = $user->canActAsStudent();
    $userData['is_admin'] = $user->isAdmin();
    
    return response()->json([
        'user' => $userData
    ]);
});

// Route temporaire pour mettre à jour le profil utilisateur (sans auth)
Route::put('/profile-test', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    // Mettre à jour les données utilisateur
    $user->update([
        'name' => $request->name,
        'phone' => $request->phone,
        'date_of_birth' => $request->date_of_birth,
    ]);
    
    return response()->json([
        'message' => 'Profil mis à jour avec succès',
        'user' => $user->fresh()
    ]);
});

// Route temporaire pour mettre à jour le profil du club (sans auth)
Route::put('/club/profile-test', function(Request $request) {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $club = $user->clubs()->first();
    if (!$club) {
        return response()->json(['error' => 'Club not found'], 404);
    }
    
    // Mettre à jour les données du club
    $club->update([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'website' => $request->website,
        'description' => $request->description,
        'address' => $request->address,
        'city' => $request->city,
        'postal_code' => $request->postal_code,
        'country' => $request->country,
        'is_active' => $request->is_active !== false
    ]);
    
    // Mettre à jour les activités du club
    if ($request->has('activity_types')) {
        $club->activityTypes()->sync($request->activity_types);
    }
    
    // Mettre à jour les disciplines du club
    if ($request->has('disciplines')) {
        $club->disciplines()->sync($request->disciplines);
    }
    
    return response()->json([
        'message' => 'Profil du club mis à jour avec succès',
        'club' => $club->fresh()
    ]);
});

// Route temporaire pour le profil utilisateur (sans auth)
Route::get('/profile-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $userData = $user->toArray();
    $userData['can_act_as_teacher'] = $user->canActAsTeacher();
    $userData['can_act_as_student'] = $user->canActAsStudent();
    $userData['is_admin'] = $user->isAdmin();
    
    // Ajouter les données du club si c'est un utilisateur club
    if ($user->role === 'club') {
        $club = $user->clubs()->first();
        if ($club) {
            $userData['club'] = $club->toArray();
        }
    }
    
    return response()->json([
        'user' => $userData
    ]);
});

// Route temporaire pour le dashboard club (sans auth)
Route::get('/club/dashboard-test', function() {
    $user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
    $club = $user->clubs()->first();
    
    $teacherUserIds = $club->users()->wherePivot('role', 'teacher')->pluck('users.id');
    $clubTeachers = App\Models\Teacher::whereIn('user_id', $teacherUserIds)->get();
    $teacherIds = $clubTeachers->pluck('id')->toArray();
    
    $totalLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->count();
    $completedLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'completed')->count();
    $pendingLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'pending')->count();
    $confirmedLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'confirmed')->count();
    $cancelledLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)->where('status', 'cancelled')->count();
    
    $totalRevenue = App\Models\Payment::whereHas('lesson', function($query) use ($teacherIds) {
        $query->whereIn('teacher_id', $teacherIds);
    })->where('status', 'succeeded')->sum('amount');
    
    // Calculs supplémentaires
    $monthlyRevenue = App\Models\Payment::whereHas('lesson', function($query) use ($teacherIds) {
        $query->whereIn('teacher_id', $teacherIds);
    })->where('status', 'succeeded')
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->sum('amount');
    
    $averageLessonPrice = $totalLessons > 0 ? round($totalRevenue / $totalLessons, 2) : 0;
    
    // Calcul du taux d'occupation (exemple simplifié)
    $totalSlots = $totalLessons + $pendingLessons; // Slots disponibles
    $occupiedSlots = $completedLessons + $confirmedLessons; // Slots occupés
    $occupancyRate = $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 1) : 0;
    
    $recentTeachers = $clubTeachers->take(5)->map(function ($teacher) {
        $user = $teacher->user;
        return [
            'id' => $teacher->id,
            'name' => $user ? $user->name : 'Enseignant ' . $teacher->id,
            'email' => $user ? $user->email : null,
            'phone' => $user ? $user->phone : null,
            'role' => 'teacher'
        ];
    });
    
    $recentStudents = $club->users()->wherePivot('role', 'student')->take(5)->get()->map(function ($student) {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
            'role' => 'student'
        ];
    });
    
    $recentLessons = App\Models\Lesson::whereIn('teacher_id', $teacherIds)
        ->with(['teacher.user', 'student.user', 'location'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($lesson) {
            $teacherName = $lesson->teacher && $lesson->teacher->user ? $lesson->teacher->user->name : 'Enseignant ' . $lesson->teacher_id;
            $studentName = $lesson->student && $lesson->student->user ? $lesson->student->user->name : 'Étudiant ' . $lesson->student_id;
            $locationName = $lesson->location ? $lesson->location->name : 'Lieu non défini';
            
            return [
                'id' => $lesson->id,
                'teacher_name' => $teacherName,
                'student_name' => $studentName,
                'location' => $locationName,
                'status' => $lesson->status,
                'created_at' => $lesson->created_at->format('d/m/Y H:i'),
                'start_time' => $lesson->start_time ? $lesson->start_time->format('d/m/Y H:i') : null,
                'end_time' => $lesson->end_time ? $lesson->end_time->format('d/m/Y H:i') : null
            ];
        });
    
    return response()->json([
        'success' => true,
        'data' => [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'email' => $club->email,
                'phone' => $club->phone,
                'address' => $club->address,
                'city' => $club->city,
                'postal_code' => $club->postal_code,
                'country' => $club->country,
                'description' => $club->description,
                'status' => $club->status,
                'is_active' => $club->is_active
            ],
            'stats' => [
                'total_teachers' => $clubTeachers->count(),
                'total_students' => $club->users()->wherePivot('role', 'student')->count(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'pending_lessons' => $pendingLessons,
                'confirmed_lessons' => $confirmedLessons,
                'cancelled_lessons' => $cancelledLessons,
                'total_revenue' => (float) $totalRevenue,
                'monthly_revenue' => (float) $monthlyRevenue,
                'average_lesson_price' => (float) $averageLessonPrice,
                'occupancy_rate' => (float) $occupancyRate
            ],
            'recentTeachers' => $recentTeachers,
            'recentStudents' => $recentStudents,
            'recentLessons' => $recentLessons
        ],
        'message' => 'Données du dashboard récupérées avec succès'
    ]);
});

// App settings publiques (pour le rebranding frontend)
Route::get('/app-settings/public', [AppSettingController::class, 'index']);

// Stripe webhook (pas d'authentification nécessaire)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // User routes
    Route::apiResource('users', UserController::class);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'currentUserProfile']);
    Route::put('/profile', [ProfileController::class, 'updateCurrentUserProfile']);
    Route::apiResource('profiles', ProfileController::class);

    // Lesson routes  
    Route::apiResource('lessons', LessonController::class);

    // Course Type routes
    Route::apiResource('course-types', CourseTypeController::class);

    // Location routes
    Route::apiResource('locations', LocationController::class);

    // Payment routes
    Route::apiResource('payments', PaymentController::class);
    Route::post('/stripe/create-payment-intent', [StripeWebhookController::class, 'createPaymentIntent']);

    // App Settings routes (admin only for write operations)
    Route::apiResource('app-settings', AppSettingController::class);
    Route::post('/app-settings/{appSetting}/activate', [AppSettingController::class, 'activate']);

    // Teacher routes
    Route::get('/teachers', [UserController::class, 'teachers']);
    Route::get('/teachers/{id}/availability', [UserController::class, 'teacherAvailability']);
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'getDashboardData'])->middleware('teacher');

    // Student routes
    Route::get('/students', [UserController::class, 'students']);
    Route::get('/students/{id}/lessons', [LessonController::class, 'studentLessons']);

    // Student Dashboard
    Route::get('/student/dashboard/stats', [DashboardController::class, 'getStats']);

    // Student routes
    Route::prefix('student')->middleware('student')->group(function () {
        Route::get('/available-lessons', [DashboardController::class, 'getAvailableLessons']);
        Route::get('/bookings', [DashboardController::class, 'getBookings']);
        Route::post('/bookings', [DashboardController::class, 'createBooking']);
        Route::put('/bookings/{id}/cancel', [DashboardController::class, 'cancelBooking']);
        Route::get('/available-teachers', [DashboardController::class, 'getAvailableTeachers']);
        Route::get('/teachers/{id}/lessons', [DashboardController::class, 'getTeacherLessons']);
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/search-lessons', [DashboardController::class, 'searchLessons']);
        Route::get('/lesson-history', [DashboardController::class, 'getLessonHistory']);
        Route::post('/bookings/{id}/rate', [DashboardController::class, 'rateLesson']);
        Route::get('/favorite-teachers', [DashboardController::class, 'getFavoriteTeachers']);
        Route::post('/favorite-teachers/{id}/toggle', [DashboardController::class, 'toggleFavoriteTeacher']);
        Route::get('/teachers', [DashboardController::class, 'getTeachers']);
        Route::get('/preferences', [DashboardController::class, 'getPreferences']);
        Route::post('/preferences', [DashboardController::class, 'savePreferences']);
        
        // Nouvelles routes pour les préférences avancées
        Route::get('/disciplines', [PreferencesController::class, 'getDisciplines']);
        Route::get('/preferences/advanced', [PreferencesController::class, 'getPreferences']);
        Route::put('/preferences/advanced', [PreferencesController::class, 'updatePreferences']);
        Route::post('/preferences/advanced', [PreferencesController::class, 'addPreference']);
        Route::delete('/preferences/advanced', [PreferencesController::class, 'removePreference']);
        Route::get('/disciplines/{id}/course-types', [PreferencesController::class, 'getCourseTypesByDiscipline']);
    });

    // Teacher routes
    Route::prefix('teacher')->middleware('teacher')->group(function () {
        Route::get('/lessons', [TeacherDashboardController::class, 'getLessons']);
        Route::post('/lessons', [TeacherDashboardController::class, 'createLesson']);
        Route::put('/lessons/{id}', [TeacherDashboardController::class, 'updateLesson']);
        Route::delete('/lessons/{id}', [TeacherDashboardController::class, 'deleteLesson']);
        Route::get('/availabilities', [TeacherDashboardController::class, 'getAvailabilities']);
        Route::post('/availabilities', [TeacherDashboardController::class, 'createAvailability']);
        Route::put('/availabilities/{id}', [TeacherDashboardController::class, 'updateAvailability']);
        Route::delete('/availabilities/{id}', [TeacherDashboardController::class, 'deleteAvailability']);
        Route::get('/stats', [TeacherDashboardController::class, 'getStats']);
        Route::get('/students', [TeacherDashboardController::class, 'getStudents']);
    });

    // Club routes
    Route::prefix('club')->middleware('club')->group(function () {
        Route::get('/dashboard', [ClubController::class, 'dashboard']);
        Route::get('/teachers', [ClubController::class, 'teachers']);
        Route::get('/students', [ClubController::class, 'students']);
        Route::post('/teachers', [ClubController::class, 'addTeacher']);
        Route::post('/students', [ClubController::class, 'addStudent']);
        Route::put('/profile', [ClubController::class, 'updateClub']);
        Route::get('/profile', [ClubController::class, 'getClubProfile']);
        
        // Dashboard financier
        Route::get('/financial/overview', [FinancialDashboardController::class, 'getOverview']);
        Route::get('/financial/revenue-by-discipline', [FinancialDashboardController::class, 'getRevenueByDiscipline']);
        Route::get('/financial/revenue-by-period', [FinancialDashboardController::class, 'getRevenueByPeriod']);
        Route::get('/financial/ancillary-revenue', [FinancialDashboardController::class, 'getAncillaryRevenue']);
        Route::get('/financial/profitability', [FinancialDashboardController::class, 'getProfitabilityAnalysis']);
        
        // Paramètres du club
        Route::get('/settings', [ClubSettingsController::class, 'index']);
        Route::get('/settings/category/{category}', [ClubSettingsController::class, 'getByCategory']);
        Route::put('/settings/{featureKey}', [ClubSettingsController::class, 'update']);
        Route::put('/settings/bulk', [ClubSettingsController::class, 'bulkUpdate']);
        Route::get('/settings/available-features', [ClubSettingsController::class, 'getAvailableFeatures']);
        Route::post('/settings/reset', [ClubSettingsController::class, 'resetToDefaults']);
        
        // Analyses graphiques Neo4j
        Route::get('/graph/dashboard', [GraphAnalyticsController::class, 'getDashboard']);
        Route::get('/graph/network-stats', [GraphAnalyticsController::class, 'getNetworkStats']);
        Route::get('/graph/top-teachers', [GraphAnalyticsController::class, 'getTopTeachers']);
        Route::get('/graph/skills-network', [GraphAnalyticsController::class, 'getSkillsNetwork']);
        Route::get('/graph/student-progress', [GraphAnalyticsController::class, 'getStudentProgress']);
        Route::get('/graph/recommendations', [GraphAnalyticsController::class, 'getRecommendations']);
        Route::post('/graph/teacher-matching', [GraphAnalyticsController::class, 'getTeacherMatching']);
        Route::post('/graph/teacher-performance', [GraphAnalyticsController::class, 'getTeacherPerformance']);
        Route::post('/graph/predict-success', [GraphAnalyticsController::class, 'predictStudentSuccess']);
        Route::get('/graph/visualization', [GraphAnalyticsController::class, 'getGraphVisualization']);
        Route::post('/graph/sync', [GraphAnalyticsController::class, 'syncAllData']);
        Route::get('/graph/status', [GraphAnalyticsController::class, 'getStatus']);
    });

    // Upload de fichiers
    Route::post('/upload/avatar', [FileUploadController::class, 'uploadAvatar'])->name('upload.avatar');
    Route::post('/upload/certificate', [FileUploadController::class, 'uploadCertificate'])->name('upload.certificate');
    Route::delete('/upload/{path}', [FileUploadController::class, 'deleteFile'])->where('path', '.*')->name('upload.delete');

    // Upload de logo (admin seulement)
    Route::post('/upload/logo', [FileUploadController::class, 'uploadLogo'])->name('upload.logo');

    // Administration (admin seulement)
    Route::prefix('admin')->middleware('admin')->group(function () {
        // Dashboard
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::get('/activities', [AdminController::class, 'getActivities']);

        // Users management
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

        // Settings management
        Route::get('/settings', [AdminController::class, 'getAllSettings']);
        Route::get('/settings/{type}', [AdminController::class, 'getSettings']);
        Route::put('/settings/{type}', [AdminController::class, 'updateSettings']);
        Route::post('/upload-logo', [AdminController::class, 'uploadLogo']);

        // Clubs management
        Route::get('/clubs', [AdminController::class, 'getClubs']);
        Route::post('/clubs', [AdminController::class, 'createClub']);
        Route::get('/clubs/{id}', [AdminController::class, 'getClub']);
        Route::put('/clubs/{id}', [AdminController::class, 'updateClub']);
        Route::delete('/clubs/{id}', [AdminController::class, 'deleteClub']);
        Route::post('/clubs/{id}/toggle-status', [AdminController::class, 'toggleClubStatus']);

        // System management
        Route::get('/system/status', [AdminController::class, 'getSystemStatus']);
        Route::post('/system/clear-cache', [AdminController::class, 'clearCache']);
    });

    // Legacy admin routes (to maintain compatibility)
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminDashboardController::class, 'users'])->name('admin.users');
    Route::put('/admin/users/{id}/status', [AdminDashboardController::class, 'updateUserStatus'])->name('admin.users.status');
});
