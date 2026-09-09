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

    // Recuperación de cuenta mediante preguntas de seguridad
    Route::post('/recovery/questions', [AuthController::class, 'getRecoveryQuestions'])->middleware('throttle:recovery')->name('recovery.questions');
    Route::post('/recovery/reset', [AuthController::class, 'resetPasswordWithSecurityQuestions'])->middleware('throttle:recovery')->name('recovery.reset');
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
        Route::post('/security-questions', [UserController::class, 'updateSecurityQuestions'])->name('api.security.update');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('api.conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('api.conversations.store');
        Route::get('/conversations/{id}', [ConversationController::class, 'show'])->name('api.conversations.show');

        // Messages & Interactions
        Route::get('/conversations/{id}/messages', [MessageController::class, 'index'])->name('api.messages.index');
        Route::post('/conversations/{id}/messages', [MessageController::class, 'store'])->middleware('throttle:messages')->name('api.messages.store');
        Route::post('/conversations/{id}/read', [MessageController::class, 'markAsRead'])->name('api.messages.read');
        Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('api.messages.destroy');
        Route::post('/messages/{id}/react', [MessageController::class, 'toggleReaction'])->name('api.messages.react');
        Route::post('/messages/{id}/pin', [MessageController::class, 'togglePin'])->name('api.messages.pin');
        Route::post('/messages/{id}/view-once', [MessageController::class, 'consumeViewOnce'])->name('api.messages.view_once');

        // Conversation management & Search
        Route::get('/conversations/{id}/search', [ConversationController::class, 'searchMessages'])->name('api.conversations.search');
        Route::post('/conversations/{id}/ephemeral', [ConversationController::class, 'setEphemeralTimer'])->name('api.conversations.ephemeral');
        Route::post('/conversations/{id}/members', [ConversationController::class, 'addMember'])->name('api.conversations.members.add');
        Route::delete('/conversations/{id}/members/{userId}', [ConversationController::class, 'removeMember'])->name('api.conversations.members.remove');
        Route::post('/conversations/{id}/leave', [ConversationController::class, 'leaveGroup'])->name('api.conversations.leave');
        Route::put('/conversations/{id}/info', [ConversationController::class, 'updateInfo'])->name('api.conversations.info');

        // Danger Zone: Total Purge
        Route::delete('/account/destroy', [UserController::class, 'destroyAccount'])->name('api.account.destroy');
    });
});
