<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route login pour éviter l'erreur "Route [login] not defined"
Route::get('/login', function() {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated',
        'error' => 'Token manquant ou invalide'
    ], 401);
})->name('login');
