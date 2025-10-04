<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\MessageController;
//use App\Http\Controllers\OwnerLoginController;
//use App\Http\Controllers\Auth\AdminLoginController;
//use App\Http\Controllers\StaffAuthController;
//use App\Http\Controllers\Staff\StaffLoginController;

use App\Http\Controllers\StaffAssignedController;

use App\Http\Controllers\TemplateController;

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

use App\Http\Controllers\Customer\InvitationController;
use App\Http\Controllers\Customer\OrderFlowController;

use App\Http\Controllers\Customer\CustomerController;

use App\Http\Controllers\Owner\OwnerProductsController;
use App\Http\Controllers\Staff\StaffCustomerController;
use App\Http\Controllers\Staff\StaffOrderController;
use App\Http\Controllers\Staff\StaffMaterialController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Owner\OwnerInventoryController;
use App\Http\Controllers\Staff\StaffInventoryController;
use App\Http\Controllers\Admin\ReportsDashboardController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;

use App\Http\Controllers\Admin\OrderSummaryController;
use App\Models\Product;

use App\Http\Controllers\Admin\UserPasswordResetController;
use App\Models\User as AppUser;
use Illuminate\Notifications\DatabaseNotification;
use App\Http\Controllers\ProfileController;







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
    

    Route::get('/ordersummary/{order:order_number?}', [OrderSummaryController::class, 'show'])
        ->name('ordersummary.index');



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
        // Add new API routes
        Route::get('{id}/load-design', [AdminTemplateController::class, 'loadDesign'])->name('loadDesign');
        Route::delete('{id}/delete-element', [AdminTemplateController::class, 'deleteElement'])->name('deleteElement');
        Route::post('{id}/save-version', [AdminTemplateController::class, 'saveVersion'])->name('saveVersion');
        // Allow GET to redirect (avoid MethodNotAllowed when link is accidentally visited)
        Route::get('{id}/upload-to-product', function ($id) {
            return redirect()->route('admin.products.create.invitation', ['template_id' => $id]);
        });
        Route::post('{id}/upload-to-product', [AdminTemplateController::class, 'uploadToProduct'])->name('uploadToProduct');
    // Custom upload via the templates UI (front/back images)
    Route::post('custom-upload', [AdminTemplateController::class, 'customUpload'])->name('customUpload');
        // Asset search API: images, videos, elements
        Route::get('{id}/assets/search', [AdminTemplateController::class, 'searchAssets'])->name('searchAssets');
        Route::post('{id}/canvas-settings', [AdminTemplateController::class, 'updateCanvasSettings'])->name('updateCanvasSettings');
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

    Route::prefix('users/passwords')->name('users.passwords.')->group(function () {
        Route::get('/', [UserPasswordResetController::class, 'index'])->name('index');
        Route::post('/unlock', [UserPasswordResetController::class, 'unlock'])->name('unlock');
        Route::post('/lock', [UserPasswordResetController::class, 'lock'])->name('lock');
        Route::post('/{user}/send', [UserPasswordResetController::class, 'send'])->name('send');
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

    Route::prefix('products')->name('products.')->group(function () {
    Route::get('/create', [ProductController::class, 'createInvitation'])->name('create');
    // Show single product (AJAX slide panel)
    Route::get('/{id}/view', [ProductController::class, 'view'])->name('view');
    Route::post('/{id}/upload', [ProductController::class, 'upload'])->name('upload');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    // Index (product listing)
    Route::get('/', [ProductController::class, 'index'])->name('index');
    // Filter pages: inks and materials
    Route::get('/inks', [ProductController::class, 'inks'])->name('inks');
    Route::get('/materials', [ProductController::class, 'materials'])->name('materials');
    Route::get('/create/invitation', [ProductController::class, 'createInvitation'])->name('create.invitation');
    Route::get('/create/giveaway', [ProductController::class, 'createGiveaway'])->name('create.giveaway');
    Route::get('/create/envelope', [ProductController::class, 'createEnvelope'])->name('create.envelope');
    Route::post('/store', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index'); 
        // Optional: Add more customer routes (show/edit/delete) here later
    });

    // Chatbot management routes
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('index');
        Route::post('/', [ChatbotController::class, 'store'])->name('store');
        Route::put('/{qa}', [ChatbotController::class, 'update'])->name('update');
        Route::delete('/{qa}', [ChatbotController::class, 'destroy'])->name('destroy');
    });

    // Messages routes

    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{customer}', [MessageController::class, 'chatWithCustomer'])->name('messages.chat');
    Route::post('messages/{customer}', [MessageController::class, 'sendToCustomer'])->name('messages.send');
     Route::post('messages/{message}/reply', [MessageController::class, 'replyToMessage'])
        ->name('messages.reply');
    Route::get('messages/{message}/thread', [MessageController::class, 'thread'])
        ->name('messages.thread');
    Route::get('messages/unread-count', [MessageController::class, 'adminUnreadCount'])
        ->name('messages.unread-count');

     Route::get('reports', [ReportsDashboardController::class, 'index'])
         ->name('reports.index');

    // Optional: Sales export
    Route::get('reports/sales/export/{type}', [ReportsDashboardController::class, 'exportSales'])
         ->name('reports.sales.export');

    // Optional: Inventory export
    Route::get('reports/inventory/export/{type}', [ReportsDashboardController::class, 'exportInventory'])
         ->name('reports.inventory.export');

  

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
        $user = Auth::user();

        abort_unless($user instanceof AppUser, 403);

        /** @var AppUser $adminUser */
        $adminUser = $user;

        $notification = DatabaseNotification::query()
            ->where('notifiable_id', $adminUser->getKey())
            ->where('notifiable_type', $adminUser->getMorphClass())
            ->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    })->name('notifications.read');

    });

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
Route::get('/', fn () => view('customer.dashboard'))->name('dashboard');  // Public
Route::middleware('auth')->get('/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customer.dashboard');  // Protected
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return 'Search for: ' . e($request->query('query', ''));
})->name('search');

/** Auth (Register/Login/Logout) */
Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register.form');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login.form');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/customer/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customer.dashboard');

Route::post('/messages', [MessageController::class, 'storeFromContact'])->name('messages.store');

    Route::get('customer/chat/thread', [MessageController::class, 'customerChatThread'])->name('customer.chat.thread');
    Route::post('customer/chat/send', [MessageController::class, 'customerChatSend'])->name('customer.chat.send');
Route::get('customer/chat/unread-count', [MessageController::class, 'customerUnreadCount'])
        ->name('customer.chat.unread');

    Route::post('customer/chat/mark-read', [MessageController::class, 'customerMarkRead'])
        ->name('customer.chat.markread');


Route::get('/chatbot/qas', [ChatbotController::class, 'getQAs'])->name('chatbot.qas');
 Route::view('/chatbot', 'customer.chatbot')->name('chatbot');
 Route::post('/chatbot/reply', [App\Http\Controllers\ChatbotController::class, 'reply'])
    ->name('chatbot.reply');

        
/**Customer Profile Pages*/
Route::prefix('customerprofile')->group(function () {
    // Addresses

    Route::get('/addresses', [CustomerProfileController::class, 'addresses'])
        ->name('customer.profile.addresses');

    Route::post('/addresses', [CustomerProfileController::class, 'storeAddress'])
        ->name('customer.profile.addresses.store');

    Route::put('/addresses/{address}', [CustomerProfileController::class, 'updateAddress'])
        ->name('customer.profile.addresses.update');

    Route::delete('/addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])
        ->name('customer.profile.addresses.destroy');

   Route::get('/', [CustomerProfileController::class, 'index'])->name('customer.profile.index');
    Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
// Other pages
Route::get('/settings', fn () => view('customer.profile.settings'))->name('customer.profile.settings');
Route::get('/order', fn () => view('customer.profile.orderform'))->name('custome.rprofile.orderform');

});

Route::middleware('auth')->get('/customerprofile/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customerprofile.dashboard');  // Protected


// My Purchases

Route::get('/customer/my-orders', fn () => view('customer.profile.my_purchase'))->name('customer.my_purchase');


/** Profile & Addresses (Protected) */
/*Route::middleware(['auth:customer'])->prefix('customer/profile')->name('customer.profile.')->group(function () {
    Route::get('/', [CustomerProfileController::class, 'update'])->name('index');
    Route::get('/', [CustomerProfileController::class, 'edit'])
        ->name('edit');

Route::middleware('auth')->get('/customer/my-purchase', function () {
    return view('customer.profile.my_purchase');
})->name('customer.my_purchase');

// Settings (with optional tab)
Route::middleware('auth')->get('/customer/profile/settings', function (\Illuminate\Http\Request $request) {
    $tab = $request->query('tab', 'account');
    return view('customer.profile.settings', compact('tab'));
})->name('customerprofile.settings');


// My Purchases


Route::middleware('auth')->prefix('customer/profile')->name('customer.profile.')->group(function () {  // Protected group
    // Profile routes
    Route::get('/', [CustomerProfileController::class, 'index'])->name('index'); 
    Route::get('/edit', [CustomerProfileController::class, 'edit'])->name('edit');  // Matches view
    Route::put('/update', [CustomerProfileController::class, 'update'])->name('update');  // Add if missing

    // Address routes
    Route::get('/addresses', [CustomerProfileController::class, 'addresses'])->name('addresses');
    Route::post('/addresses/store', [CustomerProfileController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])->name('addresses.destroy');

});*/



    

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
    Route::get('/wedding/invitations', [InvitationController::class, 'weddingInvitations'])->name('templates.wedding.invitations');
    Route::get('/birthday/invitations', fn () => view('customer.Invitations.birthdayinvite'))->name('templates.birthday.invitations');
    Route::get('/corporate/invitations', fn () => view('customer.Invitations.corporateinvite'))->name('templates.corporate.invitations');
    Route::get('/baptism/invitations', fn () => view('customer.Invitations.baptisminvite'))->name('templates.baptism.invitations');

    // Giveaways
    Route::get('/wedding/giveaways', [InvitationController::class, 'weddingGiveaways'])->name('templates.wedding.giveaways');
    Route::get('/birthday/giveaways', fn () => view('customer.Giveaways.birthdaygive'))->name('templates.birthday.giveaways');
});

/** Product Preview & Design Editing*/
Route::get('/product/preview/{product}', function (Product $product) {
    $product->load([
        'template',
        'uploads',
        'images',
        'paperStocks',
        'addons',
        'colors',
        'bulkOrders',
        'materials'
    ]);

    return view('customer.Invitations.productpreview', compact('product'));
})->name('product.preview');
Route::get('/design/edit/{product?}', [OrderFlowController::class, 'edit'])->name('design.edit');
Route::post('/order/cart/items', [OrderFlowController::class, 'storeDesignSelection'])->name('order.cart.add');

/**Order Forms & Pages*/
Route::get('/order/review', [OrderFlowController::class, 'review'])->name('order.review');
Route::get('/order/finalstep', [OrderFlowController::class, 'finalStep'])->name('order.finalstep');
Route::post('/order/finalstep/save', [OrderFlowController::class, 'saveFinalStep'])->name('order.finalstep.save');
Route::get('/order/envelope', [OrderFlowController::class, 'envelope'])->name('order.envelope');
Route::post('/order/envelope', [OrderFlowController::class, 'storeEnvelope'])->name('order.envelope.store');
Route::delete('/order/envelope', [OrderFlowController::class, 'clearEnvelope'])->name('order.envelope.clear');
Route::get('/order/summary', [OrderFlowController::class, 'summary'])->name('order.summary');
Route::get('/order/summary.json', [OrderFlowController::class, 'summaryJson'])->name('order.summary.json');
Route::delete('/order/summary', [OrderFlowController::class, 'clearSummary'])->name('order.summary.clear');
Route::get('/order/giveaways', [OrderFlowController::class, 'giveaways'])->name('order.giveaways');
Route::post('/order/giveaways', [OrderFlowController::class, 'storeGiveaway'])->name('order.giveaways.store');
Route::delete('/order/giveaways', [OrderFlowController::class, 'clearGiveaway'])->name('order.giveaways.clear');
Route::get('/api/envelopes', [OrderFlowController::class, 'envelopeOptions'])->name('api.envelopes');
Route::get('/api/envelopes', [ProductController::class, 'getEnvelopes'])->name('api.envelopes');
Route::get('/api/giveaways', [OrderFlowController::class, 'giveawayOptions'])->name('api.giveaways');
// Temporary debug endpoint: lists resolved giveaway images (thumbnail + gallery)
Route::get('/debug/giveaways-images', [\App\Http\Controllers\Customer\OrderFlowController::class, 'debugGiveawayImages'])->name('debug.giveaways.images');
Route::get('/order/birthday', fn () => view('customer.templates.birthday'))->name('order.birthday');

Route::get('/checkout', [OrderFlowController::class, 'checkout'])->name('customer.checkout');
Route::post('/checkout/complete', [OrderFlowController::class, 'completeCheckout'])->name('checkout.complete');
Route::post('/checkout/cancel', [OrderFlowController::class, 'cancelCheckout'])->name('checkout.cancel');

/**Customer Upload Route*/
Route::middleware('auth')->post('/customer/upload/design', [CustomerAuthController::class, 'uploadDesign'])->name('customer.upload.design');

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
    Route::get('/products', [OwnerProductsController::class, 'index'])->name('products.index');
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
    // Staff routes - updated for order list functionality
    Route::get('/dashboard', fn () => view('staff.dashboard'))->name('dashboard');
    Route::get('/assigned-orders', [StaffAssignedController::class, 'index'])->name('assigned.orders');
    Route::get('/order-list', [StaffOrderController::class, 'index'])->name('order_list.index');
    Route::get('/order-list/{id}', [StaffOrderController::class, 'show'])->name('order_list.show');
    Route::put('/order-list/{id}', [StaffOrderController::class, 'update'])->name('order_list.update');
    Route::get('/notify-customers', fn () => view('staff.notify_customers'))->name('notify.customers');
    Route::get('/profile/edit', [StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [StaffProfileController::class, 'update'])->name('profile.update');
    //Route::post('/profile/update', [StaffProfileController::class, 'update'])->name('profile.update');

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'staffIndex'])->name('index');
        Route::get('unread-count', [MessageController::class, 'staffUnreadCount'])->name('unread-count');
        Route::get('/{message}/thread', [MessageController::class, 'thread'])->name('thread');
        Route::post('/{message}/reply', [MessageController::class, 'replyToMessage'])->name('reply');
    });

    Route::post('/orders/{order}/confirm', [StaffAssignedController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/update-status', [StaffAssignedController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('/customers', [StaffCustomerController::class, 'index'])
        ->name('customer_profile'); 

    Route::get('/materials/notification', [StaffMaterialController::class, 'notification'])
     ->name('materials.notification');

        

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [StaffInventoryController::class, 'index'])->name('index');
        Route::get('/{id}', [StaffInventoryController::class, 'show'])->name('show');
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
if (interface_exists('Laravel\\Socialite\\Contracts\\Factory')) {
    Route::get('/auth/google/redirect', function () {
        return app('Laravel\\Socialite\\Contracts\\Factory')->driver('google')->redirect();
    })->name('google.redirect');

    Route::get('/auth/google/callback', function () {
        $user = app('Laravel\\Socialite\\Contracts\\Factory')->driver('google')->user();
        // You can dump user info for testing
        // dd($user);
        return 'Google login successful (dev placeholder)';
    })->name('google.callback');
}

Route::middleware('auth')->get('/customer/profile', [CustomerProfileController::class, 'index'])->name('customer.profile.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';



