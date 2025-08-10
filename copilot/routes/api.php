<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LessonController;

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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // User routes
    Route::apiResource('users', UserController::class);
    
    // Lesson routes  
    Route::apiResource('lessons', LessonController::class);
    
    // Teacher routes
    Route::get('/teachers', [UserController::class, 'teachers']);
    Route::get('/teachers/{id}/availability', [UserController::class, 'teacherAvailability']);
    
    // Student routes
    Route::get('/students', [UserController::class, 'students']);
    Route::get('/students/{id}/lessons', [LessonController::class, 'studentLessons']);
});
