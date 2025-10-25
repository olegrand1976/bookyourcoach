<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\ClubDashboardController;
use App\Http\Controllers\Api\ClubOpenSlotController;
use App\Http\Controllers\Api\SubscriptionController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
            ]);
        });
        Route::get('/debug-user', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
                'role' => $request->user()->role,
                'isAuthenticated' => Auth::check(),
            ]);
        });
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});

Route::middleware(['auth:sanctum', 'teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard']);
    Route::get('/lessons', [App\Http\Controllers\Api\LessonController::class, 'index']);
    Route::post('/lessons', [App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::delete('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'destroy']);
    Route::get('/lesson-replacements', [App\Http\Controllers\Api\LessonReplacementController::class, 'index']);
    Route::post('/lesson-replacements', [App\Http\Controllers\Api\LessonReplacementController::class, 'store']);
    Route::post('/lesson-replacements/{id}/respond', [App\Http\Controllers\Api\LessonReplacementController::class, 'respond']);
    Route::delete('/lesson-replacements/{id}', [App\Http\Controllers\Api\LessonReplacementController::class, 'cancel']);
    Route::get('/teachers', [App\Http\Controllers\Api\TeacherController::class, 'index']); // Liste des autres enseignants
    Route::get('/students', [App\Http\Controllers\Api\TeacherController::class, 'getStudents']); // Liste des élèves
    Route::get('/clubs', [App\Http\Controllers\Api\TeacherController::class, 'getClubs']); // Liste des clubs
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

Route::middleware(['auth:sanctum', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
});

// Routes publiques
Route::get('/activity-types', function() {
    return response()->json([
        'success' => true,
        'data' => [
            ['id' => 1, 'name' => 'Équitation', 'icon' => 'horse', 'description' => 'Sports équestres et monte à cheval'],
            ['id' => 2, 'name' => 'Natation', 'icon' => 'swimmer', 'description' => 'Sports aquatiques et natation'],
            ['id' => 3, 'name' => 'Fitness', 'icon' => 'dumbbell', 'description' => 'Musculation et remise en forme'],
            ['id' => 4, 'name' => 'Sports collectifs', 'icon' => 'futbol', 'description' => 'Football, basketball, volleyball'],
            ['id' => 5, 'name' => 'Arts martiaux', 'icon' => 'fist-raised', 'description' => 'Karaté, judo, taekwondo'],
            ['id' => 6, 'name' => 'Danse', 'icon' => 'music', 'description' => 'Danse classique, moderne, hip-hop'],
            ['id' => 7, 'name' => 'Tennis', 'icon' => 'table-tennis', 'description' => 'Tennis de table et tennis'],
            ['id' => 8, 'name' => 'Gymnastique', 'icon' => 'child', 'description' => 'Gymnastique artistique et rythmique'],
        ]
    ]);
});

// Disciplines - Route publique (utilisée par le profil club)
Route::get('/disciplines', [App\Http\Controllers\Api\DisciplineController::class, 'index']);
Route::get('/disciplines/{id}', [App\Http\Controllers\Api\DisciplineController::class, 'show']);
Route::get('/disciplines/by-activity/{activityTypeId}', [App\Http\Controllers\Api\DisciplineController::class, 'byActivityType']);

Route::middleware(['auth:sanctum', 'club'])->prefix('club')->group(function () {
    Route::get('/dashboard', [ClubDashboardController::class, 'dashboard']);
    Route::get('/profile', [ClubController::class, 'getProfile']);
    Route::put('/profile', [ClubController::class, 'updateProfile']);
    Route::get('/custom-specialties', [ClubController::class, 'getCustomSpecialties']);
    Route::get('/teachers', [ClubController::class, 'getTeachers']);
    Route::get('/students', [ClubController::class, 'getStudents']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    // Créneaux ouverts
    Route::get('/open-slots', [ClubOpenSlotController::class, 'index']);
    Route::post('/open-slots', [ClubOpenSlotController::class, 'store']);
    Route::get('/open-slots/{id}', [ClubOpenSlotController::class, 'show']);
    Route::put('/open-slots/{id}', [ClubOpenSlotController::class, 'update']);
    Route::delete('/open-slots/{id}', [ClubOpenSlotController::class, 'destroy']);
    // Gestion des types de cours pour les créneaux
    Route::put('/open-slots/{id}/course-types', [ClubOpenSlotController::class, 'updateCourseTypes']);
    // Planning avancé (suggestions, statistiques, vérifications)
    Route::post('/planning/suggest-optimal-slot', [App\Http\Controllers\Api\ClubPlanningController::class, 'suggestOptimalSlot']);
    Route::post('/planning/check-availability', [App\Http\Controllers\Api\ClubPlanningController::class, 'checkAvailability']);
    Route::get('/planning/statistics', [App\Http\Controllers\Api\ClubPlanningController::class, 'getStatistics']);
    // Abonnements
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
    Route::post('/subscriptions/assign', [SubscriptionController::class, 'assignToStudent']);
    Route::get('/students/{studentId}/subscriptions', [SubscriptionController::class, 'studentSubscriptions']);
    // Analyse prédictive IA
    Route::get('/predictive-analysis', [App\Http\Controllers\Api\PredictiveAnalysisController::class, 'getAnalysis']);
    Route::get('/predictive-analysis/alerts', [App\Http\Controllers\Api\PredictiveAnalysisController::class, 'getCriticalAlerts']);
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

// Routes pour les types de cours - accessibles à tous les utilisateurs authentifiés
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/course-types', [App\Http\Controllers\Api\CourseTypeController::class, 'index']);
});

// Routes pour les cours (lessons) - accessibles aux clubs, enseignants et étudiants
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/lessons', [App\Http\Controllers\Api\LessonController::class, 'index']);
    Route::post('/lessons', [App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::get('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'show']);
    Route::put('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'destroy']);
});

// Routes de debug (accessibles à tous les utilisateurs authentifiés)
Route::middleware(['auth:sanctum'])->prefix('debug')->group(function () {
    Route::get('/course-types-filtering', [App\Http\Controllers\Api\DebugController::class, 'checkCourseTypesFiltering']);
    Route::get('/slot/{id}', [App\Http\Controllers\Api\DebugController::class, 'checkSlot']);
});
