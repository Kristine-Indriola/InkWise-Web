<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Owner\HomeController;
use App\Http\Controllers\OwnerLoginController;
use App\Http\Controllers\CostumerAuthController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MaterialController;
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
/*Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.login');

Route::get('/auth/google/redirect', fn () => Socialite::driver('google')->redirect())->name('google.login');
Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->user();
    // TODO: Handle login or registration for Google user
});*/


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
/*Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});*/


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
| Owner Auth
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
  

/*Route::get('/Staff/login', [StaffLoginController::class, 'showLoginForm'])->name('Staff.login');
Route::post('/Staff/login', [StaffLoginController::class, 'login'])->name('Staff.login.submit');
Route::post('/Staff/logout', [StaffLoginController::class, 'logout'])->name('taff.logout');
*/
Route::prefix('staff')->name('staff.')->middleware('auth:staff')->group(function () {
    Route::get('/dashboard', fn () => view('Staff.dashboard'))->name('dashboard');
    Route::get('/assigned-orders', fn () => view('Staff.assigned_orders'))->name('assigned.orders');
    Route::get('/order-list', fn () => view('Staff.order_list'))->name('order.list');
    Route::get('/customer-profile', fn () => view('Staff.customer_profile'))->name('customer.profile');
    Route::get('/notify-customers', fn () => view('Staff.notify_customers'))->name('notify.customers');   
});

