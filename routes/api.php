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
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;

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

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

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
