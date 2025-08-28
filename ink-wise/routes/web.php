<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// Public auth/controllers
use App\Http\Controllers\AuthController;
//use App\Http\Controllers\TemplateController;

// Admin controllers
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MaterialController;



// Owner auth
use App\Http\Controllers\OwnerLoginController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Customer\CustomerController;


/*
|--------------------------------------------------------------------------
| Role-based Dashboards
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Admin Protected
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () { 
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard'); 

    // Templates 
    Route::prefix('templates')->name('templates.')->group(function () { 
    Route::get('/', [AdminTemplateController::class, 'index'])->name('index'); 
    Route::get('/create', [AdminTemplateController::class, 'create'])->name('create'); 
    Route::post('/', [AdminTemplateController::class, 'store'])->name('store'); 
    Route::get('/editor/{id?}', [AdminTemplateController::class, 'editor'])->name('editor'); }); 
    
    // ✅ User Management 
    Route::prefix('users')->name('users.')->group(function () { 
        Route::get('/', [UserManagementController::class, 'index'])->name('index'); 
        Route::get('/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('create'); 
        Route::post('/', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('store'); 
        Route::get('/{id}/edit', [UserManagementController::class, 'edit'])->name('edit'); // Edit form 
        Route::put('/{id}', [UserManagementController::class, 'update'])->name('update'); // Update user 
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('destroy'); // Delete user 
        });

     Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
    });

     Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/', [MaterialController::class, 'index'])->name('index');
        Route::get('/create', [MaterialController::class, 'create'])->name('create');
        Route::post('/', [MaterialController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MaterialController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MaterialController::class, 'update'])->name('update');
        Route::delete('/{id}', [MaterialController::class, 'destroy'])->name('destroy');
    });


});




Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/owner/home', [OwnerController::class, 'index'])->name('owner.owner-home');
});

/*Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [StaffController::class, 'index'])->name('staff.dashboard');
});

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');
});*/

Route::get('/unauthorized', function () {
    return view('errors.unauthorized');
})->name('unauthorized');


/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
*/
Route::get('/auth/google/redirect', fn () => Socialite::driver('google')->redirect())->name('google.login');
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
/*Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});*/


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
| Owner Auth
|--------------------------------------------------------------------------
*/
Route::get('owner/login', [OwnerLoginController::class, 'showLoginForm'])->name('owner.login');
Route::post('/owner/login', [OwnerLoginController::class, 'login'])->name('owner.login.submit');

Route::prefix('owner')->name('owner.')->middleware('auth:owner')->group(function () {
    Route::get('/home', fn () => view('owner.owner-home'))->name('home');
    Route::post('/logout', [OwnerLoginController::class, 'logout'])->name('logout');
});
    