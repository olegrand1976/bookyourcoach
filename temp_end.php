});

// Routes Enseignant - Temporairement sans middleware pour debug
Route::prefix('teacher')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    Route::get('/lessons', [DashboardController::class, 'getLessons']);
    Route::post('/lessons', [DashboardController::class, 'createLesson']);
    Route::put('/lessons/{id}', [DashboardController::class, 'updateLesson']);
    Route::delete('/lessons/{id}', [DashboardController::class, 'deleteLesson']);
    Route::get('/availabilities', [DashboardController::class, 'getAvailabilities']);
    Route::post('/availabilities', [DashboardController::class, 'createAvailability']);
    Route::put('/availabilities/{id}', [DashboardController::class, 'updateAvailability']);
    Route::delete('/availabilities/{id}', [DashboardController::class, 'deleteAvailability']);
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/students', [DashboardController::class, 'getStudents']);
});


