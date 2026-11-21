<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendRequestController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserSearchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('chat.index')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {

    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');

    Route::get('/chat/{user}', [ChatController::class, 'show'])
        ->name('chat.show');

    Route::post('/chat/{user}/messages', [MessageController::class, 'store'])
        ->name('messages.store');

    Route::get('/users/search', [UserSearchController::class, 'index'])
        ->name('users.search');

    Route::post('/friend-request/{user}', [FriendRequestController::class, 'store'])
        ->name('friend-request.store');

    Route::patch('/friend-request/{friendship}/accept', [FriendRequestController::class, 'accept'])
        ->name('friend-request.accept');

    Route::delete('/friend-requests/{friendship}/decline', [FriendRequestController::class, 'decline'])
        ->name('friend-requests.decline');

    Route::delete('/friend-requests/{friendship}/cancel', [FriendRequestController::class, 'cancel'])
        ->name('friend-requests.cancel');

    Route::delete('/friends/{friendship}', [FriendRequestController::class, 'destroy'])
        ->name('friends.destroy');
});

require __DIR__.'/settings.php';
