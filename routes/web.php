<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
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
});

require __DIR__.'/settings.php';
