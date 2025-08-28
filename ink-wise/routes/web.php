<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// Public auth/controllers
use App\Http\Controllers\CostumerAuthController;
use App\Http\Controllers\TemplateController;

// Admin controllers
/*use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;

// Owner auth
use App\Http\Controllers\OwnerLoginController;


/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
*/
/*Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.login');

Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->user();
    // TODO: Handle login or registration for Google user
});*/


/*
|--------------------------------------------------------------------------
| Customer (Costumer) Side
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Dashboard / Home
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');
/*
|--------------------------------------------------------------------------
| Costumer Auth (floating modals)
|--------------------------------------------------------------------------
*/
// Authentication routes
Route::get('/costumer/login', [CostumerAuthController::class, 'showLoginForm'])->name('costumer.login.form');
Route::post('/costumer/login', [CostumerAuthController::class, 'login'])->name('costumer.login');

Route::get('/costumer/register', [CostumerAuthController::class, 'showRegisterForm'])->name('costumer.register.form');
Route::post('/costumer/register', [CostumerAuthController::class, 'register'])->name('costumer.register');

Route::post('/costumer/logout', [CostumerAuthController::class, 'logout'])->name('costumer.logout');

// Authenticated-only routes
Route::middleware('auth')->group(function () {
    Route::post('/costumer-logout', [CostumerAuthController::class, 'logout'])->name('costumer.logout');
});

// Templatehome category pages
Route::get('/templates/wedding', function () {
    return view('costumertemplates.wedding');
})->name('templates.wedding');

Route::get('/templates/birthday', function () {
    return view('costumertemplates.birthday');
})->name('templates.birthday');

Route::get('/templates/baptism', function () {
    return view('costumertemplates.baptism');
})->name('templates.baptism');

Route::get('/templates/corporate', function () {
    return view('costumertemplates.corporate');
})->name('templates.corporate');

//costumer templates inviatations 
Route::get('/templates/wedding/invitations', function () {
    return view('costumerInvitations.weddinginvite');
})->name('templates.wedding.invitations');

//costumer templates giveaways 
Route::get('/templates/wedding/giveaways', function () {
    return view('costumerGiveaways.weddinggive');
})->name('templates.wedding.giveaways');

// Simple placeholders to avoid 404 during dev
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return 'Search for: ' . e($request->query('query', ''));
})->name('search');

Route::get('/order', function () {
    return 'Order page placeholder';
})->name('order');

Route::get('/design/{id}', function ($id) {
    return 'Design preview placeholder for ID: ' . e($id);
})->name('design.show');

// ----------------------------
// Temporary Google Redirect (Fix)
// ----------------------------
Route::get('/auth/google', function () {
    return 'Google login placeholder until controller is ready.';
})->name('google.redirect');


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



