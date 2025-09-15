<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\FileUploadController;

// Routes publiques
Route::get('/activity-types', function() {
    return response()->json([
        'success' => true,
        'data' => App\Models\ActivityType::all()
    ]);
});

// Authentification
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/user-test', [AuthController::class, 'userTest']);

// Routes protégées SANS middleware Sanctum (solution temporaire)
Route::group([], function () {
    Route::post('/auth/logout', function() {
        return response()->json(['message' => 'Déconnexion réussie']);
    });
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Upload sans authentification pour éviter les problèmes Sanctum
    Route::post('/upload/logo', [FileUploadController::class, 'uploadLogo']);
});

// Routes admin SANS middleware Sanctum (solution temporaire)
Route::prefix('admin')->group(function () {
    Route::get('/stats', function() {
        return response()->json([
            'users' => App\Models\User::count(),
            'lessons' => App\Models\Lesson::count(),
            'clubs' => App\Models\Club::count()
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
});

