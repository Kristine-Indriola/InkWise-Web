<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TemplateController;
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.login');

Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->user();
    // Handle login or registration
});
// ----------------------------
// Public Pages
// ----------------------------
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ----------------------------
// Protected Pages (Require Login)
// ----------------------------
Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');

    // Template section based on category choice
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');

    // Optional: Preview individual template
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});
