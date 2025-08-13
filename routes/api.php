<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;

// Authentication routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [RegisterController::class, 'verifyEmail']);
    Route::post('/resend-verification', [RegisterController::class, 'resendVerification']);
    Route::post('/login', [LoginController::class, 'login']);
    
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/logout-all', [LoginController::class, 'logoutAll']);
        Route::get('/me', [LoginController::class, 'me']);
    });
});

// Protected routes that require email verification
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // User search routes
    Route::prefix('users')->group(function () {
        Route::get('/search', [UserSearchController::class, 'search']);
        Route::get('/suggestions', [UserSearchController::class, 'suggestions']);
    });
    
    // Friend management routes
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'index']);
        Route::get('/pending', [FriendController::class, 'pendingRequests']);
        Route::post('/request', [FriendController::class, 'sendRequest']);
        Route::post('/accept/{friendshipId}', [FriendController::class, 'acceptRequest']);
        Route::post('/decline/{friendshipId}', [FriendController::class, 'declineRequest']);
        Route::delete('/cancel/{friendshipId}', [FriendController::class, 'cancelRequest']);
        Route::delete('/remove/{userId}', [FriendController::class, 'removeFriend']);
    });
    
    // Chat management routes
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'store']);
        Route::get('/{chat}', [ChatController::class, 'show']);
        Route::delete('/{chat}', [ChatController::class, 'destroy']);
        Route::post('/{chat}/mark-read', [ChatController::class, 'markAsRead']);
        
        // Message routes nested under chats
        Route::prefix('{chat}/messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'store']);
            Route::get('/older', [MessageController::class, 'older']);
            Route::get('/{message}', [MessageController::class, 'show']);
            Route::put('/{message}', [MessageController::class, 'update']);
            Route::delete('/{message}', [MessageController::class, 'destroy']);
            Route::post('/{message}/mark-read', [MessageController::class, 'markAsRead']);
        });
    });
});