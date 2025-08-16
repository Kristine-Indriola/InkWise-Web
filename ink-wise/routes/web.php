<?php
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected route
Route::get('/dashboard', function () {
    return 'Welcome to your dashboard!';
})->middleware('auth');

