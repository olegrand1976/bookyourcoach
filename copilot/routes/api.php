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

    // Student routes
    Route::get('/students', [UserController::class, 'students']);
    Route::get('/students/{id}/lessons', [LessonController::class, 'studentLessons']);
});
