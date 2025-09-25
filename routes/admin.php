<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;

// Routes admin avec middlewares appropriés et contrôleur propre
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPassword']);
    Route::patch('/users/{id}/role', [AdminController::class, 'updateRole']);
    Route::put('/users/{id}/status', [AdminController::class, 'updateStatus']);
    Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleStatus']);
    
    // Statistiques
    Route::get('/stats', [AdminController::class, 'stats']);
    
    // Paramètres
    Route::get('/settings', [AdminController::class, 'settings']);
    Route::put('/settings', [AdminController::class, 'updateSettings']);
    Route::get('/settings/{type}', [AdminController::class, 'getSettingsByType']);
    Route::put('/settings/{type}', [AdminController::class, 'updateSettingsByType']);
    
    // Clubs
    Route::post('/clubs', [AdminController::class, 'storeClub']);
    
    // Maintenance
    Route::post('/maintenance', [AdminController::class, 'maintenance']);
    Route::post('/cache/clear', [AdminController::class, 'clearCache']);
    
    // Logs d'audit
    Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    
});
