<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ----------------------------
// Public Dashboard Preview
// ----------------------------
Route::get('/', function () {
    return view('dashboard'); // Dashboard Blade: resources/views/dashboard.blade.php
})->name('dashboard'); // <--- Named route fixes navigation links

// ----------------------------
// Register, Login & Logout
// ----------------------------
// Guest-only routes
Route::middleware('guest')->group(function () {
    // Show Register Form
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    // Handle Register Form Submission
    Route::post('/register', [AuthController::class, 'register']);

    // Show Login Form
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    // Handle Login Form Submission
    Route::post('/login', [AuthController::class, 'login']);
});

// ----------------------------
// Protected Routes (requires login)
// ----------------------------
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Order Page
    Route::get('/order', function () {
        return view('order'); // Blade: resources/views/order.blade.php
    })->name('order');

    // Design Page (Dynamic ID)
    Route::get('/design/{id}', function ($id) {
        return view('design', compact('id')); // Blade: resources/views/design.blade.php
    })->name('design');

    // Optional: Profile Page (used in dropdown)
    Route::get('/profile', function () {
        return view('profile'); // Blade: resources/views/profile.blade.php
    })->name('profile.edit');
});
