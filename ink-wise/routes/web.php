<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// Public auth/controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TemplateController;

// Admin controllers
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;

// Owner auth
use App\Http\Controllers\OwnerLoginController;

/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
*/
Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.login');

Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->user();
    // TODO: Handle login or registration for Google user
});

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Pages (Default user)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});

/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit');
Route::get('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Protected
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [AdminTemplateController::class, 'index'])->name('index');
        Route::get('/create', [AdminTemplateController::class, 'create'])->name('create');
        Route::post('/', [AdminTemplateController::class, 'store'])->name('store');
        Route::get('/editor/{id?}', [AdminTemplateController::class, 'editor'])->name('editor');
    });
});


/*
|--------------------------------------------------------------------------
| Authenticated owner routes
| Require auth:owner
|--------------------------------------------------------------------------
*/
Route::get('owner/login', [OwnerLoginController::class, 'showLoginForm'])->name('owner.login');
Route::post('/owner/login', [OwnerLoginController::class, 'login'])->name('owner.login.submit');
Route::prefix('owner')->name('owner.')->middleware('auth:owner')->group(function () {
    Route::get('/home', fn () => view('owner.owner-home'))->name('home');
    Route::post('/logout', [OwnerLoginController::class, 'logout'])->name('logout');
});



