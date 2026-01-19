<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\MessageController;
//use App.Http\Controllers\OwnerLoginController;
//use App.Http\Controllers\Auth\AdminLoginController;
//use App.Http\Controllers\StaffAuthController;
//use App.Http\Controllers\Staff\StaffLoginController;

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
use App\Http\Controllers\Admin\SiteContentController as AdminSiteContentController;
use App\Http\Controllers\Owner\SiteContentController as OwnerSiteContentController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\Owner\OwnerStaffController;
use App\Http\Controllers\Auth\CustomerAuthController;

use App\Http\Controllers\Customer\InvitationController;
use App\Http\Controllers\Customer\OrderFlowController;
use App\Http\Controllers\Customer\CartController;

use App\Http\Controllers\Customer\CustomerController;

use App\Http\Controllers\Owner\OwnerProductsController;
use App\Http\Controllers\Owner\OwnerOrderWorkflowController;
use App\Http\Controllers\Owner\OwnerTransactionsController;
use App\Http\Controllers\Owner\OwnerSalesReportsController;
use App\Http\Controllers\Owner\OwnerInventoryReportsController;
use App\Http\Controllers\Staff\StaffCustomerController;
use App\Http\Controllers\Staff\StaffOrderController;
use App\Http\Controllers\Staff\StaffMaterialController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffReviewController;
use App\Http\Controllers\Owner\OwnerRatingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Owner\OwnerInventoryController;
use App\Http\Controllers\Staff\StaffInventoryController;
use App\Http\Controllers\Admin\ReportsDashboardController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\PaymentController;

use App\Http\Controllers\Admin\OrderSummaryController;
use App\Models\Product;
use App\Models\Template;
use App\Services\OrderFlowService;

use App\Http\Controllers\Admin\UserPasswordResetController;
use App\Models\User as AppUser;
use App\Http\Controllers\FigmaController;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\VerifyEmailController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GraphicsProxyController;








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
    

    Route::get('/ordersummary', [OrderSummaryController::class, 'show'])
        ->name('ordersummary.index');
    Route::get('/ordersummary/{order}', [OrderSummaryController::class, 'show'])
        ->name('ordersummary.show');

    // Admin action: force material deduction for an order (admin-only)
    Route::post('/ordersummary/{order}/deduct-materials', [OrderSummaryController::class, 'deductMaterials'])
        ->name('ordersummary.deduct');

    // Admin orders list (table) - simple closure for listing orders in the admin UI
    Route::get('/orders', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Order::query()
            ->select(['id', 'order_number', 'customer_id', 'total_amount', 'order_date', 'status', 'payment_status', 'metadata', 'created_at'])
            ->where('archived', false)
            ->where(function ($q) {
                $q->where('payment_status', '!=', 'pending')
                  ->orWhereNull('payment_status');
            });

        // Date filters (apply to order_date, fallback to created_at via COALESCE)
        if ($request->filled('start_date')) {
            try {
                $start = \Carbon\Carbon::parse($request->query('start_date'))->startOfDay();
                $query->whereRaw('COALESCE(order_date, created_at) >= ?', [$start->toDateTimeString()]);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        if ($request->filled('end_date')) {
            try {
                $end = \Carbon\Carbon::parse($request->query('end_date'))->endOfDay();
                $query->whereRaw('COALESCE(order_date, created_at) <= ?', [$end->toDateTimeString()]);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        $orders = $query
            ->with(['customer', 'payments'])
            ->withCount('items')
            ->latest('order_date')
            ->latest()
            ->get()
            ->filter(function ($order) {
                // Compute payment totals from payments relationship
                $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
                $totalPaid = round($paidPayments->sum('amount'), 2);
                $grandTotal = (float) ($order->total_amount ?? 0);
                $balanceDue = max($grandTotal - $totalPaid, 0);
                
                // Include orders that have any paid payments (for testing and admin visibility)
                return $totalPaid > 0;
            })
            ->map(function ($order) {
                // Compute payment totals from payments relationship
                $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
                $totalPaid = round($paidPayments->sum('amount'), 2);
                $grandTotal = (float) ($order->total_amount ?? 0);
                $balanceDue = max($grandTotal - $totalPaid, 0);
                
                // Append computed payment summary for the view
                $order->payments_summary = collect([
                    'grand_total' => $grandTotal,
                    'total_paid' => $totalPaid,
                    'balance_due' => $balanceDue,
                ]);
                $order->total_paid = $totalPaid;
                
                return $order;
            });

        // Render the table view inside the ordersummary folder
        return view('admin.ordersummary.tables', ['orders' => $orders]);
    })->name('orders.index');

    // Archived orders
    Route::get('/orders/archived', function () {
        $orders = \App\Models\Order::query()
            ->select(['id', 'order_number', 'customer_id', 'total_amount', 'order_date', 'status', 'payment_status', 'metadata'])
            ->where('archived', true)
            ->with(['customer', 'payments', 'activities' => function ($query) {
                $query->latest()->limit(1); // Get the most recent activity
            }])
            ->withCount('items')
            ->latest('order_date')
            ->latest()
            ->get()
            ->map(function ($order) {
                // Compute payment totals from payments relationship
                $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
                $totalPaid = round($paidPayments->sum('amount'), 2);
                $grandTotal = (float) ($order->total_amount ?? 0);
                $balanceDue = max($grandTotal - $totalPaid, 0);
                
                // Append computed payment summary for the view
                $order->payments_summary = collect([
                    'grand_total' => $grandTotal,
                    'total_paid' => $totalPaid,
                    'balance_due' => $balanceDue,
                ]);
                $order->total_paid = $totalPaid;
                
                return $order;
            });

        // Render the archived table view
        return view('admin.ordersummary.archived', compact('orders'));
    })->name('orders.archived');

    // Delete an order (AJAX / API-friendly)
    Route::get('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'editStatus'])
        ->name('orders.status.edit');
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])
        ->name('orders.status.update');
    Route::get('/orders/{order}/payment', [\App\Http\Controllers\Admin\OrderController::class, 'editPayment'])
        ->name('orders.payment.edit');
    Route::put('/orders/{order}/payment', [\App\Http\Controllers\Admin\OrderController::class, 'updatePayment'])
        ->name('orders.payment.update');
    // Archive an order (AJAX / API-friendly)
    Route::patch('/orders/{order}/archive', [\App\Http\Controllers\Admin\OrderController::class, 'archive'])
        ->name('orders.archive');

    // Payments
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])
        ->name('payments.index');
    Route::get('/payments/export', [\App\Http\Controllers\Admin\PaymentController::class, 'export'])
        ->name('payments.export');
    Route::get('/payments/archived', [\App\Http\Controllers\Admin\PaymentController::class, 'archived'])
        ->name('payments.archived');


    // Templates 
    Route::prefix('templates')->name('templates.')->group(function () { 
        Route::get('/', [AdminTemplateController::class, 'index'])->name('index'); 
        Route::get('/uploaded', [AdminTemplateController::class, 'uploaded'])->name('uploaded');
        Route::get('/create', [AdminTemplateController::class, 'create'])->name('create'); 
        Route::get('/create/invitation', [AdminTemplateController::class, 'create'])->name('create.invitation');
        Route::post('/', [AdminTemplateController::class, 'store'])->name('store'); 
        Route::get('/{id}/edit', [AdminTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminTemplateController::class, 'update'])->name('update');
        Route::get('/editor/{id?}', [AdminTemplateController::class, 'editor'])->name('editor');
        Route::delete('/{id}', [AdminTemplateController::class, 'destroy'])->name('destroy');
        // Move these two lines inside this group and fix the path:
        Route::post('{id}/save-canvas', [AdminTemplateController::class, 'saveCanvas'])->name('saveCanvas');
        Route::post('{id}/save-design', [AdminTemplateController::class, 'saveDesign'])->name('saveDesign');
        Route::post('{id}/save-template', [AdminTemplateController::class, 'saveTemplate'])->name('saveTemplate');
        Route::post('{id}/test-save', [AdminTemplateController::class, 'testSave'])->name('testSave');
        Route::post('{id}/upload-preview', [AdminTemplateController::class, 'uploadPreview'])->name('uploadPreview');
        Route::post('{id}/autosave', [AdminTemplateController::class, 'autosave'])->name('autosave');
        // Add new API routes
        Route::get('{id}/load-design', [AdminTemplateController::class, 'loadDesign'])->name('loadDesign');
        Route::delete('{id}/delete-element', [AdminTemplateController::class, 'deleteElement'])->name('deleteElement');
        Route::post('{id}/save-version', [AdminTemplateController::class, 'saveVersion'])->name('saveVersion');
        // Allow GET to redirect (avoid MethodNotAllowed when link is accidentally visited)
        Route::get('{id}/upload-to-product', function ($id) {
            return redirect()->route('admin.products.create.invitation', ['template_id' => $id]);
        });
        Route::post('{id}/upload-to-product-uploads', [AdminTemplateController::class, 'uploadTemplate'])->name('uploadToProductUploads');
        Route::post('{id}/reback', [AdminTemplateController::class, 'reback'])->name('reback');
    // Session preview routes for staff (create -> preview -> save to templates)
    Route::post('preview', [AdminTemplateController::class, 'preview'])->name('preview');
    Route::post('preview/{preview}/save', [AdminTemplateController::class, 'savePreview'])->name('preview.save');
    Route::post('preview/{preview}/remove', [AdminTemplateController::class, 'removePreview'])->name('preview.remove');
    // Custom upload via the templates UI (front/back images)
    Route::post('custom-upload', [AdminTemplateController::class, 'customUpload'])->name('customUpload');
        // Asset search API: images, videos, elements
        Route::get('{id}/assets/search', [AdminTemplateController::class, 'searchAssets'])->name('searchAssets');
        Route::post('{id}/canvas-settings', [AdminTemplateController::class, 'updateCanvasSettings'])->name('updateCanvasSettings');
        // Add SVG save route
        Route::post('{id}/save-svg', [AdminTemplateController::class, 'saveSvg'])->name('saveSvg');
    });

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');

    Route::prefix('users/passwords')->name('users.passwords.')->group(function () {
        Route::get('/', [UserPasswordResetController::class, 'index'])->name('index');
        Route::post('/unlock', [UserPasswordResetController::class, 'unlock'])->name('unlock');
        Route::post('/lock', [UserPasswordResetController::class, 'lock'])->name('lock');
        Route::post('/{user}/send', [UserPasswordResetController::class, 'send'])->name('send');
        Route::put('/{user}/update', [UserPasswordResetController::class, 'update'])->name('update');
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
        Route::post('/{id}/restock', [MaterialController::class, 'restock'])->name('restock');
        Route::delete('/{id}', [MaterialController::class, 'destroy'])->name('destroy');
    });
    // ✅ Inks routes
    Route::post('inks/{ink}/restock', [\App\Http\Controllers\Admin\InkController::class, 'restock'])->name('inks.restock');
    Route::resource('inks', \App\Http\Controllers\Admin\InkController::class)->except(['show']);

    Route::prefix('products')->name('products.')->group(function () {
    Route::get('/create', [ProductController::class, 'createInvitation'])->name('create');
    // Show single product (AJAX slide panel)
    Route::get('/{id}/view', [ProductController::class, 'view'])->name('view');
    Route::post('/{id}/upload', [ProductController::class, 'upload'])->name('upload');
    Route::post('/{id}/unupload', [ProductController::class, 'unupload'])->name('unupload');
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
    // Add route for getting template data
    Route::get('/template/{id}/data', [ProductController::class, 'getTemplateData'])->name('template.data');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index'); 
        Route::get('/{id}', [AdminCustomerController::class, 'show'])->name('show'); 
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
    Route::get('messages/{customer}/json', [MessageController::class, 'getCustomerChatJson'])->name('messages.chat.json');
    Route::post('messages/{customer}', [MessageController::class, 'sendToCustomer'])->name('messages.send');
     Route::post('messages/{message}/reply', [MessageController::class, 'replyToMessage'])
        ->name('messages.reply');
    Route::get('messages/{message}/thread', [MessageController::class, 'thread'])
        ->name('messages.thread');
    Route::get('messages/unread-count', [MessageController::class, 'adminUnreadCount'])
        ->name('messages.unread-count');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsDashboardController::class, 'index'])->name('index');
        Route::get('/sales', [ReportsDashboardController::class, 'sales'])->name('sales');
        Route::get('/inventory', [ReportsDashboardController::class, 'inventory'])->name('inventory');
        Route::get('/pickup-calendar', [ReportsDashboardController::class, 'pickupCalendar'])->name('pickup-calendar');

        Route::get('/sales/export/{type}', [ReportsDashboardController::class, 'exportSales'])
            ->name('sales.export');

        Route::post('/sales/archive', [ReportsDashboardController::class, 'archive'])
            ->name('sales.archive');

        Route::get('/inventory/export/{type}', [ReportsDashboardController::class, 'exportInventory'])
            ->name('inventory.export');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('site-content', [AdminSiteContentController::class, 'edit'])->name('site-content.edit');
        Route::put('site-content', [AdminSiteContentController::class, 'update'])->name('site-content.update');
    });

    // Font Management Routes
    Route::prefix('fonts')->name('fonts.')->group(function () {
        Route::get('/', [App\Http\Controllers\FontController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\FontController::class, 'store'])->name('store');
        Route::get('/{font}', [App\Http\Controllers\FontController::class, 'show'])->name('show');
        Route::put('/{font}', [App\Http\Controllers\FontController::class, 'update'])->name('update');
        Route::delete('/{font}', [App\Http\Controllers\FontController::class, 'destroy'])->name('destroy');
        Route::post('/sync-google-fonts', [App\Http\Controllers\FontController::class, 'syncGoogleFonts'])->name('sync-google');
        Route::get('/categories', [App\Http\Controllers\FontController::class, 'categories'])->name('categories');
        Route::post('/{font}/usage', [App\Http\Controllers\FontController::class, 'recordUsage'])->name('usage');
        Route::get('/popular', [App\Http\Controllers\FontController::class, 'popular'])->name('popular');
    });

}); // closes the admin group

// Temporary debug route: return the current session order summary payload.
// Accessible only in local environment or when allow_debug=1 is provided.
Route::get('/debug/order-summary', [OrderFlowController::class, 'debugSessionSummary'])->name('debug.order_summary');

Route::patch('/admin/notifications/{id}/read', function ($id) {
    $user = Auth::user();

    abort_unless($user instanceof AppUser, 403);

    /** @var AppUser $adminUser */
    $adminUser = $user;

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $adminUser->getKey())
        ->where('notifiable_type', $adminUser->getMorphClass())
        ->findOrFail($id);

    $notification->markAsRead();

    if (request()->expectsJson()) {
        return response()->json(['status' => 'marked']);
    }

    return back()->with('success', 'Notification marked as read.');
})->middleware('auth')->name('notifications.read');

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
| CUSTOMER ROUTES
|--------------------------------------------------------------------------
*/

/**Dashboard & Home*/
Route::redirect('/', '/landingpage');
Route::get('/landingpage', fn () => view('customer.dashboard'))->name('dashboard');
Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/landingpage/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customer.dashboard');  // Protected
 Route::get('/search', function (\Illuminate\Http\Request $request) {
     return 'Search for: ' . e($request->query('query', ''));
 })->name('search');

/** Auth (Register/Login/Logout) */
Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register.form');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::post('/customer/register/send-code', [CustomerAuthController::class, 'sendVerificationCode'])->name('customer.register.send-code');
Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login.form');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::get('/customer/forgot-password', [App\Http\Controllers\Auth\CustomerPasswordResetController::class, 'create'])->name('customer.password.request');
Route::post('/customer/forgot-password', [App\Http\Controllers\Auth\CustomerPasswordResetController::class, 'store'])->name('customer.password.email');
Route::get('/customer/reset-password', [App\Http\Controllers\Auth\CustomerNewPasswordController::class, 'create'])->name('customer.password.reset');
Route::post('/customer/reset-password', [App\Http\Controllers\Auth\CustomerNewPasswordController::class, 'store'])->name('customer.password.store');

Route::redirect('/dashboard', '/landingpage/dashboard');
Route::redirect('/customer/dashboard', '/landingpage');
Route::get('/landingpage/guest', [CustomerAuthController::class, 'dashboard'])->name('customer.dashboard.guest');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/messages', [MessageController::class, 'storeFromContact'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('messages.store');

Route::middleware('auth')->group(function () {
    Route::get('customer/chat/thread', [MessageController::class, 'customerChatThread'])->name('customer.chat.thread');
    Route::post('customer/chat/send', [MessageController::class, 'customerChatSend'])->name('customer.chat.send');
    Route::get('customer/chat/unread-count', [MessageController::class, 'customerUnreadCount'])
        ->name('customer.chat.unread');
    Route::post('customer/chat/mark-read', [MessageController::class, 'customerMarkRead'])
        ->name('customer.chat.markread');
});


Route::get('/chatbot/qas', [ChatbotController::class, 'getQAs'])->name('chatbot.qas');
 Route::view('/chatbot', 'customer.chatbot')->name('chatbot');
 Route::post('/chatbot/reply', [App\Http\Controllers\ChatbotController::class, 'reply'])
    ->name('chatbot.reply');

        
Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->prefix('customerprofile')->name('customerprofile.')->group(function () {
    // Addresses

    Route::get('/addresses', [CustomerProfileController::class, 'addresses'])
        ->name('addresses');

    Route::post('/addresses', [CustomerProfileController::class, 'storeAddress'])
        ->name('addresses.store');

    Route::put('/addresses/{address}', [CustomerProfileController::class, 'updateAddress'])
        ->name('addresses.update');

    Route::delete('/addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])
        ->name('addresses.destroy');

   Route::get('/', [CustomerProfileController::class, 'index'])->name('index');
    Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('edit');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('update');
    Route::get('/change-password', [CustomerProfileController::class, 'showChangePasswordForm'])->name('change-password');
    Route::put('/change-password', [CustomerProfileController::class, 'changePassword'])->name('change-password.update');

    // Email verification routes for password change
    Route::get('/email-verification', [CustomerProfileController::class, 'showEmailVerification'])->name('email-verification');
    Route::post('/email-verification/send', [CustomerProfileController::class, 'sendVerificationEmail'])->name('email-verification.send');
    Route::get('/password-change-confirm', [CustomerProfileController::class, 'showPasswordChangeConfirm'])->name('password-change-confirm');

    // Settings route
    Route::get('/settings', function (\Illuminate\Http\Request $request) {
        $tab = $request->query('tab', 'account');
        return view('customer.profile.settings', compact('tab'));
    })->name('settings');
});

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/customerprofile/dashboard', [CustomerAuthController::class, 'dashboard'])->name('customerprofile.dashboard');  // Protected

// Customer Favorites (render favorites page)
Route::get('/customer/favorites', fn () => view('customer.profile.favorite'))->name('customer.favorites');

// Customer Notifications
Route::get('/customer/notifications', fn () => view('customer.profile.notifications'))->name('customer.notifications');
Route::get('/customer/notifications/{id}/read', function ($id) {
    $user = Auth::user();

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->where('notifiable_type', AppUser::class)
        ->findOrFail($id);

    $notification->markAsRead();

    $redirect = request('redirect');
    if ($redirect) {
        return redirect($redirect);
    }

    return back()->with('success', 'Notification marked as read.');
})->middleware('auth')->name('customer.notifications.read');


// My Purchases
Route::get('/customer/my-orders', fn () => view('customer.profile.my_purchase'))->name('customer.my_purchase');
// To Pay tab (lists orders pending payment)
Route::get('/customer/my-orders/topay', function () {
    $user = Auth::user();
    $orders = collect();
    
    if ($user && $user->customer) {
        $orders = \App\Models\Order::query()
            ->where('customer_id', $user->customer->customer_id)
            ->with(['items.product', 'items.paperStockSelection', 'payments'])
            ->latest()
            ->get()
            ->map(function ($order) {
                // Compute payment totals from payments relationship
                $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
                $totalPaid = round($paidPayments->sum('amount'), 2);
                $grandTotal = (float) ($order->total_amount ?? 0);
                $balanceDue = max($grandTotal - $totalPaid, 0);
                
                // Store computed values for easy access in view
                $order->computed_total_paid = $totalPaid;
                $order->computed_balance_due = $balanceDue;
                
                return $order;
            });
    }
    
    return view('customer.profile.purchase.topay', compact('orders'));
})->name('customer.my_purchase.topay');
Route::get('/customer/my-orders/inproduction', function () {
    $user = Auth::user();
    $orders = collect();
    
    if ($user && $user->customer) {
        $orders = \App\Models\Order::query()
            ->where('customer_id', $user->customer->customer_id)
            ->with(['items.product', 'items.paperStockSelection', 'payments'])
            ->latest()
            ->paginate(20);
        
        $orders->getCollection()->transform(function ($order) {
            // Compute payment totals from payments relationship
            $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
            $totalPaid = round($paidPayments->sum('amount'), 2);
            $grandTotal = (float) ($order->total_amount ?? 0);
            $balanceDue = max($grandTotal - $totalPaid, 0);
            
            // Store computed values for easy access in view
            $order->computed_total_paid = $totalPaid;
            $order->computed_balance_due = $balanceDue;
            
            return $order;
        });
    }
    
    return view('customer.profile.purchase.inproduction', compact('orders'));
})->name('customer.my_purchase.inproduction');
Route::get('/customer/my-orders/toship', fn () => view('customer.profile.purchase.toship'))->name('customer.my_purchase.toship');
Route::get('/customer/my-orders/toreceive', fn () => view('customer.profile.purchase.toreceive'))->name('customer.my_purchase.toreceive');
Route::get('/customer/my-orders/topickup', fn () => view('customer.profile.purchase.topickup'))->name('customer.my_purchase.topickup');
Route::get('/customer/my-orders/completed', function () {
    $user = Auth::user();
    $orders = collect();
    
    if ($user && $user->customer) {
        $orders = \App\Models\Order::query()
            ->where('customer_id', $user->customer->customer_id)
            ->where('status', 'completed')
            ->with(['items.product', 'items.paperStockSelection', 'payments'])
            ->latest()
            ->get()
            ->map(function ($order) {
                // Compute payment totals from payments relationship
                $paidPayments = $order->payments->filter(fn($p) => strtolower($p->status ?? '') === 'paid');
                $totalPaid = round($paidPayments->sum('amount'), 2);
                $grandTotal = (float) ($order->total_amount ?? 0);
                $balanceDue = max($grandTotal - $totalPaid, 0);
                
                // Store computed values for easy access in view
                $order->computed_total_paid = $totalPaid;
                $order->computed_balance_due = $balanceDue;
                
                return $order;
            });
    }
    
    return view('customer.profile.purchase.completed', compact('orders'));
})->name('customer.my_purchase.completed');
Route::get('/customer/my-orders/rate', [CustomerProfileController::class, 'rate'])->middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->name('customer.my_purchase.rate');
Route::get('/customer/pay-remaining-balance/{order}', function (\App\Models\Order $order) {
    // Ensure the order belongs to the authenticated user
    $customer = Auth::user();
    $customerId = $customer?->customer->customer_id ?? null;
    
    if (!$customerId || $order->customer_id !== $customerId) {
        abort(404, 'Order not found or does not belong to you.');
    }
    
    return view('customer.orderflow.pay-remaining-balance', compact('order'));
})->middleware('auth')->name('customer.pay.remaining.balance');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->post('/customer/orders/{order}/cancel', [CustomerProfileController::class, 'cancelOrder'])
    ->name('customer.orders.cancel');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->post('/customer/orders/{order}/confirm-received', [CustomerProfileController::class, 'confirmReceived'])
    ->name('customer.orders.confirm_received');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/customer/orders/{order}/details', [CustomerProfileController::class, 'showOrderDetails'])
    ->name('customer.orders.details');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/customer/orders/{order}/invoice', [CustomerProfileController::class, 'showInvoice'])
    ->name('customer.orders.invoice');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->post('/customer/order-ratings', [CustomerProfileController::class, 'storeRating'])
    ->name('customer.order-ratings.store');


/** Profile & Addresses (Protected) */
/*Route::middleware(['auth:customer'])->prefix('customer/profile')->name('customer.profile.')->group(function () {
    Route::get('/', [CustomerProfileController::class, 'update'])->name('index');
    Route::get('/', [CustomerProfileController::class, 'edit'])
        ->name('edit');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/customer/my-purchase', function () {
    return view('customer.profile.my_purchase');
})->name('customer.my_purchase');

// My Purchases


Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->prefix('customer/profile')->name('customer.profile.')->group(function () {  // Protected group
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
    Route::get('/birthday/invitations', [InvitationController::class, 'birthdayInvitations'])->name('templates.birthday.invitations');
    Route::get('/corporate/invitations', [InvitationController::class, 'corporateInvitations'])->name('templates.corporate.invitations');
    Route::get('/baptism/invitations', [InvitationController::class, 'baptismInvitations'])->name('templates.baptism.invitations');

    // Giveaways
    Route::get('/wedding/giveaways', [InvitationController::class, 'weddingGiveaways'])->name('templates.wedding.giveaways');
    Route::get('/birthday/giveaways', [InvitationController::class, 'birthdayGiveaways'])->name('templates.birthday.giveaways');
    Route::get('/corporate/giveaways', [InvitationController::class, 'corporateGiveaways'])->name('templates.corporate.giveaways');
    Route::get('/baptism/giveaways', [InvitationController::class, 'baptismGiveaways'])->name('templates.baptism.giveaways');
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
        'materials.material'
    ]);
    $product->setRelation('bulkOrders', collect());

    return view('customer.Invitations.productpreview', compact('product'));
})->name('product.preview');
Route::get('/design/studio/{template}', function (Template $template, Request $request) {
    /** @var OrderFlowService $orderFlow */
    $orderFlow = app(OrderFlowService::class);
    $productId = $request->query('product');

    $product = null;
    if ($productId) {
        $product = Product::with(['template'])->find($productId);
    }

    if (!$product) {
        $product = Product::query()
            ->with(['template'])
            ->where('template_id', $template->id)
            ->latest('updated_at')
            ->first();
    }

    if ($product) {
        $product->setRelation('template', $template);
    }

    $summaryKey = 'order_summary_payload';
    $orderKey = 'current_order_id';
    $summary = session($summaryKey);
    if (!is_array($summary)) {
        $summary = [];
    }

    $defaultQuantity = null;

    if ($product) {
        $defaultQuantity = $summary['quantity'] ?? $orderFlow->defaultQuantityFor($product);
        $unitPrice = $orderFlow->unitPriceFor($product);
        $subtotal = round($unitPrice * $defaultQuantity, 2);
        $taxAmount = 0.0;
        $shippingFee = 0.0;
        $images = $orderFlow->resolveProductImages($product);
        $designMetadata = $orderFlow->buildDesignMetadata($product);

        $storedDraft = $orderFlow->loadDesignDraft($product, Auth::user());

        if ($storedDraft) {
            if (!empty($storedDraft['design'])) {
                $designMetadata = $storedDraft['design'];
                $summary['metadata']['design'] = $summary['metadata']['design'] ?? $storedDraft['design'];
            }

            if (empty($summary['placeholders']) && !empty($storedDraft['placeholders'])) {
                $summary['placeholders'] = $storedDraft['placeholders'];
            }

            if (empty($summary['previewImages']) && !empty($storedDraft['preview_images'])) {
                $summary['previewImages'] = $storedDraft['preview_images'];
            }

            if (empty($summary['previewImage']) && !empty($storedDraft['preview_image'])) {
                $summary['previewImage'] = $storedDraft['preview_image'];
                $summary['invitationImage'] = $storedDraft['preview_image'];
            }

            if (empty($summary['orderId']) && !empty($storedDraft['order_id'])) {
                $summary['orderId'] = $storedDraft['order_id'];
            }

            if (empty($summary['order_item_id']) && !empty($storedDraft['order_item_id'])) {
                $summary['order_item_id'] = $storedDraft['order_item_id'];
            }

            if (empty($summary['orderStatus']) && !empty($storedDraft['status'])) {
                $summary['orderStatus'] = $storedDraft['status'];
            }
        }

        $shouldResetSummary = ($summary['productId'] ?? null) !== $product->id;

        if ($shouldResetSummary) {
            $summary = [
                'orderId' => null,
                'orderNumber' => null,
                'orderStatus' => 'draft',
                'paymentStatus' => null,
                'productId' => $product->id,
                'productName' => $product->name ?? 'Custom Invitation',
                'quantity' => $defaultQuantity,
                'unitPrice' => $unitPrice,
                'subtotalAmount' => $subtotal,
                'taxAmount' => $taxAmount,
                'shippingFee' => $shippingFee,
                'totalAmount' => round($subtotal + $taxAmount + $shippingFee, 2),
                'previewImages' => $images['all'] ?? [],
                'previewImage' => $images['front'] ?? null,
                'invitationImage' => $images['front'] ?? null,
                'paperStockId' => null,
                'paperStockName' => null,
                'paperStockPrice' => null,
                'addons' => [],
                'addonIds' => [],
                'metadata' => [
                    'design' => $designMetadata,
                ],
                'placeholders' => $designMetadata['placeholders'] ?? [],
                'extras' => [
                    'paper' => 0,
                    'addons' => 0,
                    'envelope' => 0,
                    'giveaway' => 0,
                ],
            ];
        } else {
            $summary['productId'] = $product->id;
            $summary['productName'] = $product->name ?? 'Custom Invitation';
            $summary['quantity'] = $summary['quantity'] ?? $defaultQuantity;
            $summary['unitPrice'] = $summary['unitPrice'] ?? $unitPrice;
            $summary['subtotalAmount'] = $summary['subtotalAmount'] ?? $subtotal;
            $summary['taxAmount'] = $summary['taxAmount'] ?? $taxAmount;
            $summary['shippingFee'] = $summary['shippingFee'] ?? $shippingFee;
            $summary['totalAmount'] = $summary['totalAmount'] ?? round(($summary['subtotalAmount'] ?? $subtotal) + ($summary['taxAmount'] ?? $taxAmount) + ($summary['shippingFee'] ?? $shippingFee), 2);

            $summary['metadata'] = is_array($summary['metadata'] ?? null) ? $summary['metadata'] : [];
            if (empty($summary['metadata']['design'])) {
                $summary['metadata']['design'] = $designMetadata;
            }

            if (empty($summary['previewImages'])) {
                $summary['previewImages'] = $images['all'] ?? [];
            }

            if (empty($summary['previewImage']) && !empty($images['front'])) {
                $summary['previewImage'] = $images['front'];
            }

            if (empty($summary['invitationImage']) && !empty($summary['previewImage'])) {
                $summary['invitationImage'] = $summary['previewImage'];
            }

            if (empty($summary['placeholders']) && !empty($designMetadata['placeholders'])) {
                $summary['placeholders'] = $designMetadata['placeholders'];
            }

            if (!isset($summary['extras']) || !is_array($summary['extras'])) {
                $summary['extras'] = [
                    'paper' => 0,
                    'addons' => 0,
                    'envelope' => 0,
                    'giveaway' => 0,
                ];
            }
        }

        session()->put($summaryKey, $summary);
        session()->forget($orderKey);
    }

    return view('customer.order.studio', [
        'product' => $product,
        'template' => $template,
        'defaultQuantity' => $defaultQuantity,
        'orderSummary' => $summary,
    ]);
})->name('design.studio')
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer');
Route::get('/design/edit/{product?}', [OrderFlowController::class, 'edit'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('design.edit');
Route::post('/order/cart/items', [OrderFlowController::class, 'storeDesignSelection'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.cart.add');
Route::any('/design/autosave', [OrderFlowController::class, 'autosaveDesign'])
    // ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.design.autosave');
Route::get('/design/load-autosave', [OrderFlowController::class, 'loadAutosave'])
    // ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.design.load-autosave');
Route::post('/design/save-to-review', [OrderFlowController::class, 'saveToReview'])
    // ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.design.save-to-review');
Route::post('/design/upload-image', [OrderFlowController::class, 'uploadDesignImage'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.design.upload-image');
Route::post('/design/save-template', [OrderFlowController::class, 'saveAsTemplate'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer,staff')
    ->name('order.design.save-template');

/**Order Forms & Pages*/
Route::get('/order/review', [OrderFlowController::class, 'review'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.review');
Route::post('/order/review/design', [OrderFlowController::class, 'saveReviewDesign'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.review.design');
Route::post('/order/review/continue', [OrderFlowController::class, 'continueReview'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.review.continue');
Route::get('/order/finalstep', [OrderFlowController::class, 'finalStep'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.finalstep');
Route::get('/order/addtocart', [OrderFlowController::class, 'addToCart'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.addtocart');
Route::post('/order/finalstep/save', [OrderFlowController::class, 'saveFinalStep'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.finalstep.save');
Route::get('/order/envelope', [OrderFlowController::class, 'envelope'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.envelope');
Route::post('/order/envelope', [OrderFlowController::class, 'storeEnvelope'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.envelope.store');
Route::delete('/order/envelope', [OrderFlowController::class, 'clearEnvelope'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.envelope.clear');
Route::get('/order/summary', [OrderFlowController::class, 'summary'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.summary');
Route::get('/order/summary.json', [OrderFlowController::class, 'summaryJson'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.summary.json');
Route::post('/order/summary/sync', [OrderFlowController::class, 'syncSummary'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.summary.sync');
Route::post('/order/summary/update-quantity', [OrderFlowController::class, 'updateQuantity'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.summary.update-quantity');
Route::delete('/order/summary', [OrderFlowController::class, 'clearSummary'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.summary.clear');
Route::get('/order/giveaways', [OrderFlowController::class, 'giveaways'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.giveaways');
Route::post('/order/giveaways', [OrderFlowController::class, 'storeGiveaway'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.giveaways.store');
Route::delete('/order/giveaways', [OrderFlowController::class, 'clearGiveaway'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('order.giveaways.clear');
Route::get('/api/envelopes', [OrderFlowController::class, 'envelopeOptions'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('api.envelopes');
// Route::get('/api/envelopes', [ProductController::class, 'getEnvelopes'])->name('api.envelopes');
Route::get('/api/giveaways', [OrderFlowController::class, 'giveawayOptions'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('api.giveaways');
// Temporary debug endpoint: lists resolved giveaway images (thumbnail + gallery)
Route::get('/debug/giveaways-images', [OrderFlowController::class, 'debugGiveawayImages'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('debug.giveaways.images');
Route::get('/order/birthday', fn () => view('customer.templates.birthday'))->name('order.birthday');

Route::get('/checkout', [OrderFlowController::class, 'checkout'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('customer.checkout');
Route::post('/checkout/complete', [OrderFlowController::class, 'completeCheckout'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('checkout.complete');
Route::post('/checkout/cancel', [OrderFlowController::class, 'cancelCheckout'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer')
    ->name('checkout.cancel');
Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->get('/order/{order}/pay-remaining-balance', [OrderFlowController::class, 'payRemainingBalance'])->name('order.pay.remaining.balance');

Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->group(function () {
    Route::get('/customer/cart', [CartController::class, 'index'])->name('customer.cart');
    Route::patch('/order/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('customer.cart.update');
    Route::delete('/order/cart/items/{cartItem}', [CartController::class, 'removeItem'])->name('customer.cart.remove');
    // Provide a friendly GET handler so users who accidentally navigate to the create endpoint
    // (for example via a bad link or direct browser navigation) receive clear guidance
    // instead of a server error about unsupported methods.
    Route::get('/payments/gcash', function () {
        if (request()->expectsJson()) {
            return response()->json(['message' => 'GCash payments must be started via POST from the checkout flow.'], 405);
        }

        return redirect()->route('customer.checkout')
            ->with('error', 'Please start GCash payments using the Place Order button on checkout.');
    })->middleware(\App\Http\Middleware\RoleMiddleware::class . ':customer');

    Route::post('/payments/gcash', [PaymentController::class, 'createGCashPayment'])->name('payment.gcash.create');
    Route::get('/payments/gcash/return', [PaymentController::class, 'handleGCashReturn'])->name('payment.gcash.return');
});
Route::post('/payments/gcash/webhook', [PaymentController::class, 'webhook'])->name('payment.gcash.webhook');

/**Customer Upload Route*/
Route::middleware(\App\Http\Middleware\RoleMiddleware::class.':customer')->post('/customer/upload/design', [CustomerAuthController::class, 'uploadDesign'])->name('customer.upload.design');

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
Route::match(['GET', 'POST'], '/logout', [RoleLoginController::class, 'logout'])->name('logout');


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
    Route::get('/order/workflow', [OwnerOrderWorkflowController::class, 'index'])->name('order.workflow');
    Route::get('/order/workflow/data', [OwnerOrderWorkflowController::class, 'data'])->name('order.workflow.data');
    Route::get('/order/archived', [OwnerOrderWorkflowController::class, 'archived'])->name('order.archived');
    Route::get('/orders/{order}', [OwnerOrderWorkflowController::class, 'show'])->name('orders.show');
    Route::get('/pickup/calendar', [OwnerOrderWorkflowController::class, 'pickupCalendar'])->name('pickup.calendar');
    Route::get('/inventory', [OwnerInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/track', [OwnerInventoryController::class, 'track'])->name('inventory-track');
    Route::get('/products', [OwnerProductsController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [OwnerProductsController::class, 'show'])->name('products.show');
    Route::get('/transactions/view', [OwnerTransactionsController::class, 'index'])->name('transactions-view');
    Route::get('/transactions/export', [OwnerTransactionsController::class, 'export'])->name('transactions-export');
    Route::get('/transactions/archived', [OwnerTransactionsController::class, 'archived'])->name('transactions.archived');
    Route::patch('/transactions/{payment}/archive', [OwnerTransactionsController::class, 'archive'])->name('transactions.archive');
    Route::get('/ratings', [OwnerRatingsController::class, 'index'])->name('ratings.index');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('site-content', [OwnerSiteContentController::class, 'edit'])->name('site-content.edit');
        Route::put('site-content', [OwnerSiteContentController::class, 'update'])->name('site-content.update');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', fn () => redirect()->route('owner.reports.sales'))->name('index');
        Route::get('/sales', [OwnerSalesReportsController::class, 'index'])->name('sales');
        Route::get('/inventory', [OwnerInventoryReportsController::class, 'index'])->name('inventory');
    });

    
    
    Route::get('/owner/materials/low-stock', [OwnerInventoryController::class, 'track'])
    ->name('owner.materials.lowStock')
    ->defaults('status', 'low');

    Route::get('/owner/materials/out-stock', [OwnerInventoryController::class, 'track'])
    ->name('owner.materials.outStock')
    ->defaults('status', 'out');

    Route::get('/owner/inventory-track', [OwnerInventoryController::class, 'inventoryTrack'])
    ->name('owner.inventory-track');


});



  

Route::prefix('staff')->name('staff.')->middleware(\App\Http\Middleware\RoleMiddleware::class.':staff')->group(function () {
    // Staff routes - updated for order list functionality
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/assigned-orders', [StaffAssignedController::class, 'index'])->name('assigned.orders');
    Route::get('/order-list', [StaffOrderController::class, 'index'])->name('order_list.index');
    Route::get('/order-list/{id}', [StaffOrderController::class, 'show'])->name('order_list.show');
    Route::put('/order-list/{id}', [StaffOrderController::class, 'update'])->name('order_list.update');
    Route::get('/orders/{id}/summary', [StaffOrderController::class, 'summary'])->name('orders.summary');
    Route::get('/orders/{id}/status', [StaffOrderController::class, 'editStatus'])->name('orders.status.edit');
    Route::put('/orders/{id}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::get('/orders/{order}/payment', [StaffOrderController::class, 'editPayment'])->name('orders.payment.edit');
    Route::put('/orders/{order}/payment', [StaffOrderController::class, 'updatePayment'])->name('orders.payment.update');
    Route::patch('/orders/{order}/archive', [StaffOrderController::class, 'archive'])->name('orders.archive');
    Route::get('/orders/archived', [StaffOrderController::class, 'archived'])->name('orders.archived');
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
    Route::get('/customers/{id}', [StaffCustomerController::class, 'show'])
        ->name('customer_profile.show'); 

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
        Route::post('/{id}/restock', [StaffMaterialController::class, 'restock'])->name('restock');
        Route::delete('/{id}', [StaffMaterialController::class, 'destroy'])->name('destroy');
    });

    // Staff Templates routes
    Route::prefix('templates')->name('templates.')->group(function () { 
        Route::get('/', [App\Http\Controllers\Admin\TemplateController::class, 'index'])->name('index'); 
        Route::get('/uploaded', [App\Http\Controllers\Admin\TemplateController::class, 'uploaded'])->name('uploaded');
        Route::get('/create', [App\Http\Controllers\Admin\TemplateController::class, 'create'])->name('create'); 
        Route::get('/create/invitation', [App\Http\Controllers\Admin\TemplateController::class, 'create'])->name('create.invitation');
        Route::get('/create/giveaway', [App\Http\Controllers\Admin\TemplateController::class, 'create'])->name('create.giveaway');
        Route::get('/create/envelope', [App\Http\Controllers\Admin\TemplateController::class, 'create'])->name('create.envelope');
        Route::post('/', [App\Http\Controllers\Admin\TemplateController::class, 'store'])->name('store'); 
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\TemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\TemplateController::class, 'update'])->name('update');
        Route::get('/editor/{id?}', [App\Http\Controllers\Admin\TemplateController::class, 'editor'])->name('editor');
        Route::delete('/{id}', [App\Http\Controllers\Admin\TemplateController::class, 'destroy'])->name('destroy');
        // Move these two lines inside this group and fix the path:
        Route::post('{id}/save-canvas', [App\Http\Controllers\Admin\TemplateController::class, 'saveCanvas'])->name('saveCanvas');
        Route::post('{id}/save-design', [App\Http\Controllers\Admin\TemplateController::class, 'saveDesign'])->name('saveDesign');
        Route::post('{id}/save-template', [App\Http\Controllers\Admin\TemplateController::class, 'saveTemplate'])->name('saveTemplate');
        Route::post('{id}/test-save', [App\Http\Controllers\Admin\TemplateController::class, 'testSave'])->name('testSave');
        Route::post('{id}/upload-preview', [App\Http\Controllers\Admin\TemplateController::class, 'uploadPreview'])->name('uploadPreview');
        Route::post('{id}/autosave', [App\Http\Controllers\Admin\TemplateController::class, 'autosave'])->name('autosave');
        // Add new API routes
        Route::get('{id}/load-design', [App\Http\Controllers\Admin\TemplateController::class, 'loadDesign'])->name('loadDesign');
        Route::delete('{id}/delete-element', [App\Http\Controllers\Admin\TemplateController::class, 'deleteElement'])->name('deleteElement');
        Route::post('{id}/save-version', [App\Http\Controllers\Admin\TemplateController::class, 'saveVersion'])->name('saveVersion');
        // Allow GET to redirect (avoid MethodNotAllowed when link is accidentally visited)
        Route::get('{id}/upload-to-product', function ($id) {
            return redirect()->route('admin.products.create.invitation', ['template_id' => $id]);
        });
        Route::post('{id}/upload-to-product-uploads', [App\Http\Controllers\Admin\TemplateController::class, 'uploadTemplate'])->name('uploadToProductUploads');
    // Custom upload via the templates UI (front/back images)
    Route::post('custom-upload', [App\Http\Controllers\Admin\TemplateController::class, 'customUpload'])->name('customUpload');
        // Asset search API: images, videos, elements
        Route::get('{id}/assets/search', [App\Http\Controllers\Admin\TemplateController::class, 'searchAssets'])->name('searchAssets');
        Route::post('{id}/canvas-settings', [App\Http\Controllers\Admin\TemplateController::class, 'updateCanvasSettings'])->name('updateCanvasSettings');
        // Add SVG save route
        Route::post('{id}/save-svg', [App\Http\Controllers\Admin\TemplateController::class, 'saveSvg'])->name('saveSvg');
        // Session preview routes for staff (create -> preview -> save to templates)
        Route::post('preview', [App\Http\Controllers\Admin\TemplateController::class, 'preview'])->name('preview');
        Route::post('preview/{preview}/save', [App\Http\Controllers\Admin\TemplateController::class, 'savePreview'])->name('preview.save');
        Route::post('preview/{preview}/remove', [App\Http\Controllers\Admin\TemplateController::class, 'removePreview'])->name('preview.remove');

        // Figma Integration Routes for staff templates
        Route::post('figma/analyze', [\App\Http\Controllers\FigmaController::class, 'analyze'])->name('figma.analyze');
        Route::post('figma/preview', [\App\Http\Controllers\FigmaController::class, 'preview'])->name('figma.preview');
        Route::post('figma/import', [\App\Http\Controllers\FigmaController::class, 'import'])->name('figma.import');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/pickup-calendar', [App\Http\Controllers\Admin\ReportsDashboardController::class, 'pickupCalendar'])->name('pickup-calendar');
    });

    Route::get('/reviews', [StaffReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [StaffReviewController::class, 'reply'])->name('reviews.reply');
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



Route::get('/customerprofile/email-confirm/{token}', [CustomerProfileController::class, 'confirmEmail'])->name('customerprofile.email-confirm');

Route::get('/auth/password-change/verify/{token}', [CustomerProfileController::class, 'confirmEmail'])->name('password.change.verify');

Route::get('/unauthorized', function () {
    return view('errors.unauthorized');
})->name('unauthorized');

require __DIR__.'/auth.php';

// Graphics proxy routes (search endpoints used by client-side graphics panel)
Route::get('/graphics/svgrepo', [GraphicsProxyController::class, 'svgrepo'])->name('graphics.svgrepo');
Route::get('/graphics/unsplash', [GraphicsProxyController::class, 'unsplash'])->name('graphics.unsplash');

if (app()->environment('local')) {
    Route::get('/storage/{path}', function (string $path) {
        $relativePath = ltrim($path, '/');
        $disk = Storage::disk('public');

        if (!$disk->exists($relativePath)) {
            abort(404);
        }

        return $disk->response($relativePath);
    })->where('path', '.*');
}







