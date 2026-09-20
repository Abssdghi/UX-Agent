<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/history', [ChatController::class, 'history'])->name('chat.history');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat/finalize', [ChatController::class, 'finalize'])->name('chat.finalize');
Route::post('/chat/reset', [ChatController::class, 'reset'])->name('chat.reset');