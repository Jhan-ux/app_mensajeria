<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('chat.index');
    });

    Route::get('/chat', function () {
        return view('chat.app');
    })->name('chat.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // API Routes for Chat SPA
    Route::prefix('api')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');
        Route::post('/heartbeat', [UserController::class, 'heartbeat'])->name('api.heartbeat');
        Route::get('/users/search', [UserController::class, 'search'])->middleware('throttle:search')->name('api.users.search');
        Route::post('/profile', [UserController::class, 'updateProfile'])->name('api.profile.update');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('api.conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('api.conversations.store');
        Route::get('/conversations/{id}', [ConversationController::class, 'show'])->name('api.conversations.show');

        Route::get('/conversations/{id}/messages', [MessageController::class, 'index'])->name('api.messages.index');
        Route::post('/conversations/{id}/messages', [MessageController::class, 'store'])->middleware('throttle:messages')->name('api.messages.store');
        Route::post('/conversations/{id}/read', [MessageController::class, 'markAsRead'])->name('api.messages.read');
        Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('api.messages.destroy');
    });
});
