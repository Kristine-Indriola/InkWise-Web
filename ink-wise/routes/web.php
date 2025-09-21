<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
//use App\Http\Controllers\OwnerLoginController;
//use App\Http\Controllers\Auth\AdminLoginController;
//use App\Http\Controllers\StaffAuthController;
//use App\Http\Controllers\Staff\StaffLoginController;

use App\Http\Controllers\Admin\InkController;

use App\Http\Controllers\Owner\HomeController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Auth\RoleLoginController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MaterialsController;
use App\Http\Controllers\customerProfileController;
use App\Http\Controllers\Owner\OwnerStaffController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Staff\StaffCustomerController;
use App\Http\Controllers\Staff\StaffMaterialController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Owner\OwnerInventoryController;
use App\Http\Controllers\Staff\StaffInventoryController;
use App\Http\Controllers\Admin\ReportsDashboardController;
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
    Route::get('/profile', [AdminController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [AdminController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [AdminController::class, 'update'])->name('profile.update');
    Route::get('/admin/users/{id}', [UserManagementController::class, 'show'])
     ->name('admin.users.show'); 

     Route::get('/materials/notification', [MaterialController::class, 'notification'])
     ->name('admin.materials.notification');

     Route::get('/notifications', [AdminController::class, 'notifications'])
        ->name('notifications');
 
     Route::get('/admin/notifications', [AdminController::class, 'notifications'])
    ->name('admin.notifications')
    ->middleware('auth');
    


    // Templates 
    Route::prefix('templates')->name('templates.')->group(function () { 
        Route::get('/', [AdminTemplateController::class, 'index'])->name('index'); 
        Route::get('/create', [AdminTemplateController::class, 'create'])->name('create'); 
        Route::post('/', [AdminTemplateController::class, 'store'])->name('store'); 
        Route::get('/editor/{id?}', [AdminTemplateController::class, 'editor'])->name('editor');
        Route::delete('/{id}', [AdminTemplateController::class, 'destroy'])->name('destroy');
        // Move these two lines inside this group and fix the path:
        Route::post('{id}/save-canvas', [AdminTemplateController::class, 'saveCanvas'])->name('saveCanvas');
        Route::post('{id}/upload-preview', [AdminTemplateController::class, 'uploadPreview'])->name('uploadPreview');
    }); 
    // ✅ User Management 

Route::prefix('users')->name('users.')->group(function () { 
    Route::get('/', [UserManagementController::class, 'index'])->name('index'); 
    Route::get('/create', [UserManagementController::class, 'create'])->name('create'); 
    Route::post('/', [UserManagementController::class, 'store'])->name('store'); 
    Route::get('/{user_id}/edit', [UserManagementController::class, 'edit'])->name('edit'); // Edit form 
    Route::put('/{user_id}', [UserManagementController::class, 'update'])->name('update'); // Update user 
    Route::delete('/{user_id}', [UserManagementController::class, 'destroy'])->name('destroy'); // Delete user 
});

    });
  Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementController::class, 'create'])->name('create');
    Route::post('/', [UserManagementController::class, 'store'])->name('store');
    Route::get('/{user_id}', [UserManagementController::class, 'show'])->name('show'); // ✅ Added
    Route::get('/{user_id}/edit', [UserManagementController::class, 'edit'])->name('edit');
    Route::put('/{user_id}', [UserManagementController::class, 'update'])->name('update');
    Route::delete('/{user_id}', [UserManagementController::class, 'destroy'])->name('destroy');

});


     Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    // Materials routes
    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/', [MaterialController::class, 'index'])->name('index');
        Route::get('/create', [MaterialController::class, 'create'])->name('create');
        Route::post('/', [MaterialController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MaterialController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MaterialController::class, 'update'])->name('update');
        Route::delete('/{id}', [MaterialController::class, 'destroy'])->name('destroy');
    });
    // ✅ Inks resource route (move here, not nested)
    Route::resource('inks', \App\Http\Controllers\Admin\InkController::class)->except(['show']);

    
    // Fix: Rename the index route to 'products.index' (full name: 'admin.products.index' due to group prefix)
    Route::get('/products', [ProductController::class, 'invitation'])->name('products.index');
    
    // Add: Route for creating an invitation (full name: 'admin.products.create.invitation')
    Route::get('/products/create/invitation', [ProductController::class, 'createInvitation'])->name('products.create.invitation');
    
    // Add: Route for storing an invitation (full name: 'admin.products.store')
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index'); 
        // Optional: Add more customer routes (show/edit/delete) here later
    });

    // Messages routes
      Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index'); // ✅ admin.messages.index
        Route::get('/chat/{customerId}', [MessageController::class, 'chatWithCustomer'])->name('chat');
        Route::post('/send/{customerId}', [MessageController::class, 'sendToCustomer'])->name('send');
    });

     Route::get('reports', [ReportsDashboardController::class, 'index'])
         ->name('reports.reports');

    // Optional: Sales export
    Route::get('reports/sales/export/{type}', [ReportsDashboardController::class, 'exportSales'])
         ->name('reports.sales.export');

    // Optional: Inventory export
    Route::get('reports/inventory/export/{type}', [ReportsDashboardController::class, 'exportInventory'])
         ->name('reports.inventory.export');
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

Route::get('/verify-email/{token}', [VerificationController::class, 'verify'])
    ->name('verify.email');

    Route::patch('/notifications/{id}/read', function ($id) {
    $notification = auth()->user()->notifications()->findOrFail($id);
    $notification->markAsRead();

    return back()->with('success', 'Notification marked as read.');
})->name('notifications.read');
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
| CUSTOMER ROUTES
|--------------------------------------------------------------------------
*/

/**Dashboard & Home*/
Route::get('/', fn () => view('customer.dashboard'))->name('dashboard');
Route::get('/dashboard', fn () => view('customer.dashboard'))->name('customer.dashboard');
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return 'Search for: ' . e($request->query('query', ''));
})->name('search');

/** Auth (Register/Login/Logout) */
Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register.form');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login.form');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
Route::get('/customerprofile/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customerprofile.dashboard');


// My Purchases


Route::prefix('customer/profile')->name('customer.profile.')->group(function () {
    // Profile routes
    Route::get('/', [CustomerProfileController::class, 'index'])->name('index'); 
    Route::get('/edit', [CustomerProfileController::class, 'edit'])->name('edit'); 
    Route::get('/update', [CustomerProfileController::class, 'update'])->name('update');

    // Address routes
    Route::get('/addresses', [CustomerProfileController::class, 'addresses'])->name('addresses');
    Route::post('/addresses/store', [CustomerProfileController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])->name('addresses.destroy');

   // Route::get('/customer/my-orders', fn () => view('customer.profile.my_purchase'))->name('customer.my_purchase');

    
});
    


// Profile update (protected)
/*Route::middleware(['auth:customer'])->group(function () {
    Route::put('/customer/profile/update', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
});*/



/** Templates (Category Home & Invitations/Giveaways)*/
Route::prefix('templates')->group(function () {
    // Category Home
    Route::get('/wedding', fn () => view('customer.templates.wedding'))->name('templates.wedding');
    Route::get('/birthday', fn () => view('customer.templates.birthday'))->name('templates.birthday');
    Route::get('/baptism', fn () => view('customer.templates.baptism'))->name('templates.baptism');
    Route::get('/corporate', fn () => view('customer.templates.corporate'))->name('templates.corporate');

    // Invitations
    Route::get('/wedding/invitations', fn () => view('customer.Invitations.weddinginvite'))->name('templates.wedding.invitations');
    Route::get('/birthday/invitations', fn () => view('customer.Invitations.birthdayinvite'))->name('templates.birthday.invitations');
    Route::get('/corporate/invitations', fn () => view('customer.Invitations.corporateinvite'))->name('templates.corporate.invitations');
    Route::get('/baptism/invitations', fn () => view('customer.Invitations.baptisminvite'))->name('templates.baptism.invitations');

    // Giveaways
    Route::get('/wedding/giveaways', fn () => view('customer.Giveaways.weddinggive'))->name('templates.wedding.giveaways');
    Route::get('/birthday/giveaways', fn () => view('customer.Giveaways.birthdaygive'))->name('templates.birthday.giveaways');
});

/** Product Preview & Design Editing*/
Route::get('/product/preview', fn () => view('customer.Invitations.productpreview'))->name('productpreview');
Route::get('/design/edit', fn () => view('customer.Invitations.editing'))->name('design.edit');

/**Order Forms & Pages*/
Route::get('/order/form', fn () => view('customer.profile.orderform'))->name('order.form');
Route::get('/order/birthday', fn () => view('customer.templates.birthday'))->name('order.birthday');
/*
|--------------------------------------------------------------------------|
| CUSTOMER END                                      |
|--------------------------------------------------------------------------|
*/

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

    // Staff management (approved + pending)
    // Staff management (single page)
     Route::get('/profile', [OwnerController::class, 'show'])->name('profile.show');

    // Edit profile
    Route::get('/profile/edit', [OwnerController::class, 'edit'])->name('profile.edit');

    // Update profile
    Route::put('/profile/update', [OwnerController::class, 'update'])->name('profile.update');

    // Staff management (approved + pending)
    
    Route::get('/staff', [OwnerController::class, 'staffIndex'])->name('staff.index');

    Route::get('/staff/search', [OwnerStaffController::class, 'search'])->name('staff.search');




    // Approve/reject staff
    Route::post('/staff/{staff}/approve', [OwnerController::class, 'approveStaff'])->name('staff.approve');
    Route::post('/staff/{staff}/reject', [OwnerController::class, 'rejectStaff'])->name('staff.reject');


    // Other pagesgut
    Route::get('/order/workflow', fn () => view('owner.order-workflow'))->name('order.workflow');
    Route::get('/inventory', [OwnerInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/track', [OwnerInventoryController::class, 'track'])->name('inventory-track');
    Route::get('/transactions/view', fn () => view('owner.transactions-view'))->name('transactions-view');
    Route::get('/reports', fn () => view('owner.owner-reports'))->name('reports');

    
    
    Route::get('/owner/materials/low-stock', [OwnerInventoryController::class, 'track'])
    ->name('owner.materials.lowStock')
    ->defaults('status', 'low');

    Route::get('/owner/materials/out-stock', [OwnerInventoryController::class, 'track'])
    ->name('owner.materials.outStock')
    ->defaults('status', 'out');

    Route::get('/owner/inventory-track', [OwnerInventoryController::class, 'inventoryTrack'])
    ->name('owner.inventory-track');


});



  

Route::middleware('auth')->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', fn () => view('staff.dashboard'))->name('dashboard');
    Route::get('/assigned-orders', fn () => view('staff.assigned_orders'))->name('assigned.orders');
    Route::get('/order-list', fn () => view('staff.order_list'))->name('order.list');
    Route::get('/notify-customers', fn () => view('staff.notify_customers'))->name('notify.customers');
    Route::get('/profile/edit', [StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [StaffProfileController::class, 'update'])->name('profile.update');

    // ✅ fixed: remove the extra "staff" in URL and name
    Route::get('/customers', [StaffCustomerController::class, 'index'])
        ->name('customer_profile'); 

    Route::get('/materials/notification', [StaffMaterialController::class, 'notification'])
     ->name('staff.materials.notification');

        

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [StaffInventoryController::class, 'index'])->name('index');
        Route::get('/create', [StaffInventoryController::class, 'create'])->name('create');
        Route::post('/', [StaffInventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StaffInventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StaffInventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [StaffInventoryController::class, 'destroy'])->name('destroy');
    });

     Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/', [StaffMaterialController::class, 'index'])->name('index');
        Route::get('/create', [StaffMaterialController::class, 'create'])->name('create');
        Route::post('/', [StaffMaterialController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StaffMaterialController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StaffMaterialController::class, 'update'])->name('update');
        Route::delete('/{id}', [StaffMaterialController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------|
| Google OAuth (Temporary for Dev)                                        |
|--------------------------------------------------------------------------|
*/
Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.redirect');

Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->user();
    // You can dump user info for testing
    // dd($user);
    return 'Google login successful (dev placeholder)';
})->name('google.callback');

});

