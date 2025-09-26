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

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

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
});

Route::middleware(['auth:sanctum', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard']);
});

// Routes publiques
Route::get('/activity-types', function() {
    return response()->json([
        'success' => true,
        'data' => [
            ['id' => 1, 'name' => 'Équitation', 'icon' => '🐎', 'description' => 'Sports équestres et monte à cheval'],
            ['id' => 2, 'name' => 'Natation', 'icon' => '🏊‍♀️', 'description' => 'Sports aquatiques et natation'],
            ['id' => 3, 'name' => 'Fitness', 'icon' => '💪', 'description' => 'Musculation et remise en forme'],
            ['id' => 4, 'name' => 'Sports collectifs', 'icon' => '⚽', 'description' => 'Football, basketball, volleyball'],
            ['id' => 5, 'name' => 'Arts martiaux', 'icon' => '🥋', 'description' => 'Karaté, judo, taekwondo'],
            ['id' => 6, 'name' => 'Danse', 'icon' => '💃', 'description' => 'Danse classique, moderne, hip-hop'],
            ['id' => 7, 'name' => 'Tennis', 'icon' => '🎾', 'description' => 'Tennis de table et tennis'],
            ['id' => 8, 'name' => 'Gymnastique', 'icon' => '🤸‍♀️', 'description' => 'Gymnastique artistique et rythmique'],
        ]
    ]);
});

Route::get('/disciplines', function() {
    return response()->json([
        'success' => true,
        'data' => [
            // Équitation (activity_type_id: 1)
            ['id' => 1, 'name' => 'Dressage', 'activity_type_id' => 1],
            ['id' => 2, 'name' => 'Saut d\'obstacles', 'activity_type_id' => 1],
            ['id' => 3, 'name' => 'Concours complet', 'activity_type_id' => 1],
            ['id' => 4, 'name' => 'Équitation western', 'activity_type_id' => 1],
            ['id' => 5, 'name' => 'Endurance', 'activity_type_id' => 1],
            ['id' => 6, 'name' => 'Voltige', 'activity_type_id' => 1],
            ['id' => 7, 'name' => 'Équitation de loisir', 'activity_type_id' => 1],
            
            // Natation (activity_type_id: 2)
            ['id' => 11, 'name' => 'Nage libre', 'activity_type_id' => 2],
            ['id' => 12, 'name' => 'Brasse', 'activity_type_id' => 2],
            ['id' => 13, 'name' => 'Papillon', 'activity_type_id' => 2],
            ['id' => 14, 'name' => 'Dos crawlé', 'activity_type_id' => 2],
            ['id' => 15, 'name' => 'Natation synchronisée', 'activity_type_id' => 2],
            ['id' => 16, 'name' => 'Water-polo', 'activity_type_id' => 2],
            ['id' => 17, 'name' => 'Aquagym', 'activity_type_id' => 2],
            
            // Fitness (activity_type_id: 3)
            ['id' => 21, 'name' => 'Musculation', 'activity_type_id' => 3],
            ['id' => 22, 'name' => 'CrossFit', 'activity_type_id' => 3],
            ['id' => 23, 'name' => 'Cardio-training', 'activity_type_id' => 3],
            ['id' => 24, 'name' => 'Yoga', 'activity_type_id' => 3],
            ['id' => 25, 'name' => 'Pilates', 'activity_type_id' => 3],
            ['id' => 26, 'name' => 'Zumba', 'activity_type_id' => 3],
            
            // Sports collectifs (activity_type_id: 4)
            ['id' => 31, 'name' => 'Football', 'activity_type_id' => 4],
            ['id' => 32, 'name' => 'Basketball', 'activity_type_id' => 4],
            ['id' => 33, 'name' => 'Volleyball', 'activity_type_id' => 4],
            ['id' => 34, 'name' => 'Handball', 'activity_type_id' => 4],
            ['id' => 35, 'name' => 'Rugby', 'activity_type_id' => 4],
            
            // Arts martiaux (activity_type_id: 5)
            ['id' => 41, 'name' => 'Karaté', 'activity_type_id' => 5],
            ['id' => 42, 'name' => 'Judo', 'activity_type_id' => 5],
            ['id' => 43, 'name' => 'Taekwondo', 'activity_type_id' => 5],
            ['id' => 44, 'name' => 'Boxe', 'activity_type_id' => 5],
            ['id' => 45, 'name' => 'Aïkido', 'activity_type_id' => 5],
            
            // Danse (activity_type_id: 6)
            ['id' => 51, 'name' => 'Danse classique', 'activity_type_id' => 6],
            ['id' => 52, 'name' => 'Danse moderne', 'activity_type_id' => 6],
            ['id' => 53, 'name' => 'Hip-hop', 'activity_type_id' => 6],
            ['id' => 54, 'name' => 'Salsa', 'activity_type_id' => 6],
            ['id' => 55, 'name' => 'Tango', 'activity_type_id' => 6],
            
            // Tennis (activity_type_id: 7)
            ['id' => 61, 'name' => 'Tennis de table', 'activity_type_id' => 7],
            ['id' => 62, 'name' => 'Tennis sur court', 'activity_type_id' => 7],
            ['id' => 63, 'name' => 'Badminton', 'activity_type_id' => 7],
            
            // Gymnastique (activity_type_id: 8)
            ['id' => 71, 'name' => 'Gymnastique artistique', 'activity_type_id' => 8],
            ['id' => 72, 'name' => 'Gymnastique rythmique', 'activity_type_id' => 8],
            ['id' => 73, 'name' => 'Trampoline', 'activity_type_id' => 8],
        ]
    ]);
});

Route::middleware(['auth:sanctum', 'club'])->prefix('club')->group(function () {
    Route::get('/dashboard', [ClubDashboardController::class, 'dashboard']);
    Route::get('/profile', [ClubController::class, 'getProfile']);
    Route::put('/profile', [ClubController::class, 'updateProfile']);
    Route::get('/custom-specialties', [ClubController::class, 'getCustomSpecialties']);
    Route::get('/teachers', [ClubController::class, 'getTeachers']);
    Route::get('/students', [ClubController::class, 'getStudents']);
});

// Routes pour les cours (lessons) - accessibles aux clubs, enseignants et étudiants
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/lessons', [App\Http\Controllers\Api\LessonController::class, 'index']);
    Route::post('/lessons', [App\Http\Controllers\Api\LessonController::class, 'store']);
    Route::get('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'show']);
    Route::put('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [App\Http\Controllers\Api\LessonController::class, 'destroy']);
});
