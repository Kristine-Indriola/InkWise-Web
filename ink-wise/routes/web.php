<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Owner\HomeController;
//use App\Http\Controllers\OwnerLoginController;
//use App\Http\Controllers\Auth\AdminLoginController;
//use App\Http\Controllers\StaffAuthController;
//use App\Http\Controllers\Staff\StaffLoginController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Controllers\VerificationController;
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
use App\Http\Controllers\AddressController;







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
  Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementController::class, 'create'])->name('create');
    Route::post('/', [UserManagementController::class, 'store'])->name('store');
    Route::get('/{user_id}', [UserManagementController::class, 'show'])->name('show'); // ✅ Added
    Route::get('/{user_id}/edit', [UserManagementController::class, 'edit'])->name('edit');
    Route::put('/{user_id}', [UserManagementController::class, 'update'])->name('update');
    Route::delete('/{user_id}', [UserManagementController::class, 'destroy'])->name('destroy');

});

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

// Customer Profile pages
Route::middleware(['auth:customer'])->group(function () {
    // Addresses
    Route::get('/customerprofile/addresses', [AddressController::class, 'index'])->name('customerprofile.addresses');
    Route::post('/customerprofile/addresses', [AddressController::class, 'store'])->name('customerprofile.addresses.store');
    Route::post('/customerprofile/addresses/{address}/delete', [AddressController::class, 'destroy'])->name('customerprofile.addresses.destroy');
    Route::post('/customerprofile/addresses/{address}/update', [AddressController::class, 'update'])->name('customerprofile.addresses.update');
    // Other customer-only pages
    Route::get('/customer/my-orders', function () {
        return view('customerprofile.my_purchase');
    })->name('customer.my_purchase');
    Route::get('/customerprofile/order', function () {
        return view('customerprofile.orderform');
    })->name('customerprofile.orderform');
    Route::get('/customerprofile/settings', function () {
        return view('customerprofile.settings');
    })->name('customerprofile.settings');
    
});
Route::get('/customerprofile/profile', [CustomerProfileController::class, 'edit'])->name('customerprofile.profile');





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
Route::get('/templates/birthday/invitations', function () {
    return view('customerInvitations.birthdayinvite');
})->name('templates.birthday.invitations');
Route::get('/templates/corporate/invitations', function () {
    return view('customerInvitations.corporateinvite');
})->name('templates.corporate.invitations');
Route::get('/templates/baptism/invitations', function () {
    return view('customerInvitations.baptisminvite');
})->name('templates.baptism.invitations');


Route::get('/product/preview', function () {
    return view('customerInvitations.productpreview');
})->name('productpreview');
// Design editing route
Route::get('/design/edit', function () {
    return view('customerInvitations.editing');
})->name('design.edit');
Route::get('/order/form', function () {
    return view('customerprofile.orderform');
})->name('order.form');

//customer templates giveaways 
Route::get('/templates/wedding/giveaways', function () {
    return view('customerGiveaways.weddinggive');
})->name('templates.wedding.giveaways');
Route::get('/templates/birthday/giveaways', function () {
    return view('customerGiveaways.birthdaygive');
})->name('templates.birthday.giveaways');


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
