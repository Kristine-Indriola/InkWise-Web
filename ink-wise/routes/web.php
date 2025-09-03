<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Owner\HomeController;
//use App\Http\Controllers\OwnerLoginController;
use App\Http\Controllers\Auth\CustomerAuthController;
//use App\Http\Controllers\Auth\AdminLoginController;
//use App\Http\Controllers\StaffAuthController;
//use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Auth\RoleLoginController;






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
    // ✅ User Management 
Route::prefix('users')->name('users.')->group(function () { 
    Route::get('/', [UserManagementController::class, 'index'])->name('index'); 
    Route::get('/create', [UserManagementController::class, 'create'])->name('create'); 
    Route::post('/', [UserManagementController::class, 'store'])->name('store'); 
    Route::get('/{user_id}/edit', [UserManagementController::class, 'edit'])->name('edit'); // Edit form 
    Route::put('/{user_id}', [UserManagementController::class, 'update'])->name('update'); // Update user 
    Route::delete('/{user_id}', [UserManagementController::class, 'destroy'])->name('destroy'); // Delete user 
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
| COSTUMER ROUTES
|--------------------------------------------------------------------------
*/
//Costumer Dashboard/Home route
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Guest routes (register / login)

Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register.form');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register.submit');

Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login.form');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');

Route::get('/customer/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customer.dashboard');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');



/*Route::middleware('auth')->group(function () {
    Route::get('/categories', [TemplateController::class, 'categories'])->name('categories');
    Route::get('/templates/{category}', [TemplateController::class, 'templates'])->name('templates');
    Route::get('/template/preview/{id}', [TemplateController::class, 'preview'])->name('template.preview');
});*/

// COSTUMER Templatehome category pages
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

//COSTUMER templates inviatations 
Route::get('/templates/wedding/invitations', function () {
    return view('costumerInvitations.weddinginvite');
})->name('templates.wedding.invitations');

//COSTUMER templates giveaways 
Route::get('/templates/wedding/giveaways', function () {
    return view('costumerGiveaways.weddinggive');
})->name('templates.wedding.giveaways');

// COSTUMER order and design pages
Route::get('/order/birthday', function () {
    return view('costumertemplates.birthday');  // <-- points to your blade file
})->name('order.birthday');

// Costumer Dashboard (temporary page to preview)
//Route::view('/costumer-dashboard', 'costumerprofile.dashboard')->name('costumer.dashboard');

// (Optional) fake profile update – for now it just redirects back
Route::post('/costumer/profile', function (\Illuminate\Http\Request $request) {
    // TODO: save profile later
    return back()->with('status', 'Profile updated (demo).');
})->name('costumer.profile.update');

// Simple placeholders to avoid 404 during dev
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return 'Search for: ' . e($request->query('query', ''));
})->name('search');

// ----------------------------
// Temporary Google Redirect (Fix)
// ----------------------------
Route::get('/auth/google', function () {
    return 'Google login placeholder until controller is ready.';
})->name('google.redirect');

// ----------------------------
// COSTUMER ROUTES END
// ----------------------------


/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/
// login of all roles
Route::get('/login', [RoleLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [RoleLoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [RoleLoginController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| Owner Auth
|--------------------------------------------------------------------------
*/


Route::middleware('auth')->prefix('owner')->name('owner.')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/approve-staff', fn () => view('owner.owner-appstaff'))->name('approve-staff');
    Route::get('/order/workflow', fn () => view('owner.order-workflow'))->name('order.workflow');
    Route::get('/inventory/track', fn () => view('owner.inventory-track'))->name('inventory-track');
    Route::get('/transactions/view', fn () => view('owner.transactions-view'))->name('transactions-view');
    Route::get('/reports', fn () => view('owner.owner-reports'))->name('reports');
});
  

Route::middleware('auth')->prefix('staff')->name('staff.')->group(function () {

    Route::get('/dashboard', fn () => view('staff.dashboard'))->name('dashboard');
    Route::get('/assigned-orders', fn () => view('staff.assigned_orders'))->name('assigned.orders');
    Route::get('/order-list', fn () => view('staff.order_list'))->name('order.list');
    Route::get('/customer-profile', fn () => view('staff.customer_profile'))->name('customer.profile');
    Route::get('/notify-customers', fn () => view('staff.notify_customers'))->name('notify.customers');   
});