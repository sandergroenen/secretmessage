<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect("/login");
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard route
    Route::get('dashboard', [App\Http\Controllers\MessageController::class, 'index'])->name('dashboard');
    
    // Message routes
    Route::prefix('messages')->group(function () {
        Route::get('/', [App\Http\Controllers\MessageController::class, 'getMessages']);
        Route::post('/', [App\Http\Controllers\MessageController::class, 'sendMessage']);
        Route::post('/{id}/read', [App\Http\Controllers\MessageController::class, 'markAsRead']);
        Route::post('/decrypt', [App\Http\Controllers\MessageController::class, 'decryptMessage']);
    });
    
    // Key management routes
    Route::prefix('keys')->group(function () {
        Route::post('/generate', [App\Http\Controllers\MessageController::class, 'generateKeys']);
    });
    
    // User routes for recipient selection
    Route::get('/users', [App\Http\Controllers\MessageController::class, 'getUsers']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
