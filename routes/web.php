<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
//test
Route::get('/', function () {
    return redirect("/login");
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard route
    Route::get('dashboard', [App\Domain\Message\Controllers\MessageController::class, 'index'])->name('dashboard');
    
    // Message routes
    Route::prefix('message')->group(function () {
        Route::get('/check/{id}', [App\Domain\Message\Controllers\MessageController::class, 'checkMessageId']);
        Route::post('/', [App\Domain\Message\Controllers\MessageController::class, 'sendMessage']);
        Route::post('/{id}/markasread', [App\Domain\Message\Controllers\MessageController::class, 'markAsRead']);
        Route::post('/decrypt', [App\Domain\Message\Controllers\MessageController::class, 'decryptMessage']);
        Route::post('/expired', [App\Domain\Message\Controllers\MessageController::class, 'handleExpiredMessage']);
    });
    
    // Key management routes
    Route::prefix('keys')->group(function () {
        Route::post('/generate', [App\Domain\Message\Controllers\MessageController::class, 'generateKeys']);
    });
    
    // User routes for recipient selection
    Route::get('/user', [App\Domain\Message\Controllers\MessageController::class, 'getLoggedInUserId']);
    Route::get('/users', [App\Domain\Message\Controllers\MessageController::class, 'getUsers']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
