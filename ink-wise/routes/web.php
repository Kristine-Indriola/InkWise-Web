<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;

// ----------------------------
// Dashboard route
// ----------------------------
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard')->middleware('auth');



Route::get('/', function () {
    return view('dashboard'); // or dashboard.blade.php if that's your homepage
});

// ----------------------------
// Google Login Routes
// ----------------------------
Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// ----------------------------
// Authentication Routes
// ----------------------------
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
