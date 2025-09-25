    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\AdminController::class, 'dashboard']);
    
    // Gestion des utilisateurs
    Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateUser']);
    Route::post('/users/{id}/reset-password', [\App\Http\Controllers\Api\AdminController::class, 'resetPassword']);
    Route::patch('/users/{id}/role', [\App\Http\Controllers\Api\AdminController::class, 'updateRole']);
    Route::put('/users/{id}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateStatus']);
    Route::patch('/users/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleStatus']);
    
    // Statistiques
    Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
    
    // Paramètres
    Route::get('/settings', [\App\Http\Controllers\Api\AdminController::class, 'settings']);
    Route::put('/settings', [\App\Http\Controllers\Api\AdminController::class, 'updateSettings']);
    Route::get('/settings/{type}', [\App\Http\Controllers\Api\AdminController::class, 'getSettingsByType']);
    Route::put('/settings/{type}', [\App\Http\Controllers\Api\AdminController::class, 'updateSettingsByType']);
    
    // Clubs
    Route::post('/clubs', [\App\Http\Controllers\Api\AdminController::class, 'storeClub']);
    
    // Maintenance
    Route::post('/maintenance', [\App\Http\Controllers\Api\AdminController::class, 'maintenance']);
    Route::post('/cache/clear', [\App\Http\Controllers\Api\AdminController::class, 'clearCache']);
    
    // Logs d'audit
    Route::get('/audit-logs', [\App\Http\Controllers\Api\AdminController::class, 'auditLogs']);
    
});
