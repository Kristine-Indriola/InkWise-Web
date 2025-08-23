<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public auth/controllers
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;

// Admin controllers
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Owner\HomeController;
use App\Http\Controllers\OwnerLoginController;
use App\Http\Controllers\CostumerAuthController;

// Admin controllers
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;



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

// Protected Customer Routes
Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});
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

Route::get('/owner/login', [OwnerLoginController::class, 'showLoginForm'])->name('owner.login');
Route::post('/owner/login', [OwnerLoginController::class, 'login'])->name('owner.login.submit');

Route::prefix('owner')->name('owner.')->middleware('auth:owner')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/approve-staff', fn () => view('owner.owner-appstaff'))->name('approve-staff');
    Route::get('/order/workflow', fn () => view('owner.order-workflow'))->name('order.workflow');
    Route::get('/inventory/track', fn () => view('owner.inventory-track'))->name('inventory-track');
    Route::get('/transactions/view', fn () => view('owner.transactions-view'))->name('transactions-view');
    Route::post('/logout', [OwnerLoginController::class, 'logout'])->name('logout');
});
  


Route::get('/order-list', function () {
    return view('Staff.order_list');
})->name('order.list');

Route::get('/customer-profile', function () {
    return view('Staff.customer_profile');
})->name('customer.profile');

Route::get('/notify-customers', function () {
    return view('Staff.notify_customers');
})->name('notify.customers');
