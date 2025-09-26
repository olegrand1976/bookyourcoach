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
            ['id' => 1, 'name' => 'Équitation de loisir'],
            ['id' => 2, 'name' => 'Dressage'],
            ['id' => 3, 'name' => 'Saut d\'obstacles'],
            ['id' => 4, 'name' => 'Concours complet'],
            ['id' => 5, 'name' => 'Équitation western'],
            ['id' => 6, 'name' => 'Endurance'],
            ['id' => 7, 'name' => 'Voltige'],
        ]
    ]);
});

Route::get('/disciplines', function() {
    return response()->json([
        'success' => true,
        'data' => [
            ['id' => 1, 'name' => 'Dressage'],
            ['id' => 2, 'name' => 'Saut d\'obstacles'],
            ['id' => 3, 'name' => 'Concours complet'],
            ['id' => 4, 'name' => 'Équitation western'],
            ['id' => 5, 'name' => 'Endurance'],
            ['id' => 6, 'name' => 'Voltige'],
            ['id' => 7, 'name' => 'Équitation de loisir'],
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
