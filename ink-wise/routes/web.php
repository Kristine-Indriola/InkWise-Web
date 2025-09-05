<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Owner\HomeController;
use App\Http\Controllers\Owner\OwnerController;
//use App\Http\Controllers\OwnerLoginController;
//use App\Http\Controllers\Auth\AdminLoginController;
//use App\Http\Controllers\StaffAuthController;
//use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Auth\RoleLoginController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\customerProfileController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;






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
     // Show profile
   // Show profile
    Route::get('/profile', [AdminController::class, 'show'])->name('profile.show');

    // Edit profile
    Route::get('/profile/edit', [AdminController::class, 'edit'])->name('profile.edit');

    // Update profile
    Route::put('/profile/update', [AdminController::class, 'update'])->name('profile.update');
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
| customer ROUTES
|--------------------------------------------------------------------------
*/
//customer Dashboard/Home route
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Simple placeholders to avoid 404 during dev
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return 'Search for: ' . e($request->query('query', ''));
})->name('search');

// Dashboard page (works for both guest & logged in users)
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('customer.dashboard');

// Guest routes (register / login)
Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register.form');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register');

Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login.form');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');

Route::get('/customerprofile/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customerprofile.dashboard');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

//customer Profile pages
Route::middleware(['auth:customer'])->group(function () {
    //Route::get('/customer/profile', [customerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/customer/profile', [customerProfileController::class, 'update'])->name('customer.profile.update');
});
Route::get('/customer/my-orders', function () {
    return view('customerprofile.my_orders');
})->name('customer.my_orders');
Route::get('/customer/dshboard', function () {
    return view('customerprofile.dashboard');
})->name('customerprofile.dashboard');

// customer Templatehome category pages
Route::get('/templates/wedding', function () {
    return view('customertemplates.wedding');
})->name('templates.wedding');
Route::get('/templates/birthday', function () {
    return view('customertemplates.birthday');
})->name('templates.birthday');
Route::get('/templates/baptism', function () {
    return view('customertemplates.baptism');
})->name('templates.baptism');
Route::get('/templates/corporate', function () {
    return view('customertemplates.corporate');
})->name('templates.corporate');


//customer templates inviatations 
Route::get('/templates/wedding/invitations', function () {
    return view('customerInvitations.weddinginvite');
})->name('templates.wedding.invitations');

//customer templates giveaways 
Route::get('/templates/wedding/giveaways', function () {
    return view('customerGiveaways.weddinggive');
})->name('templates.wedding.giveaways');

// customer order and design pages
Route::get('/order/birthday', function () {
    return view('customertemplates.birthday');  // <-- points to your blade file
})->name('order.birthday');


// ----------------------------
// Temporary Google Redirect (Fix)
// ----------------------------
Route::get('/auth/google', function () {
    return 'Google login placeholder until controller is ready.';
})->name('google.redirect');

// ----------------------------
// customer ROUTES END
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
    Route::post('staff/{staff}/approve', [OwnerController::class, 'approve'])->name('staff.approve');
    Route::post('staff/{staff}/reject', [OwnerController::class, 'reject'])->name('staff.reject');
});


  

Route::middleware('auth')->prefix('staff')->name('staff.')->group(function () {

    Route::get('/dashboard', fn () => view('staff.dashboard'))->name('dashboard');
    Route::get('/assigned-orders', fn () => view('staff.assigned_orders'))->name('assigned.orders');
    Route::get('/order-list', fn () => view('staff.order_list'))->name('order.list');
    Route::get('/customer-profile', fn () => view('staff.customer_profile'))->name('customer.profile');
    Route::get('/notify-customers', fn () => view('staff.notify_customers'))->name('notify.customers');   
});