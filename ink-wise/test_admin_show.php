<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin user authentication
$admin = \App\Models\User::where('role', 'admin')->first();
\Auth::login($admin);

echo "Testing Admin Profile Show Page:" . PHP_EOL;
echo "=================================" . PHP_EOL;

// Test the show method
$controller = new \App\Http\Controllers\AdminController();
try {
    // The show method should now return a view instead of redirecting
    $response = $controller->show();

    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Show method returns a view (not redirecting)" . PHP_EOL;

        // Check if view data is passed correctly
        $viewData = $response->getData();
        if (isset($viewData['admin'])) {
            $adminData = $viewData['admin'];
            echo "✅ Admin data passed to view:" . PHP_EOL;
            echo "  - User ID: " . $adminData->user_id . PHP_EOL;
            echo "  - Email: " . $adminData->email . PHP_EOL;
            echo "  - Role: " . $adminData->role . PHP_EOL;

            if ($adminData->staff) {
                echo "  - Staff Data:" . PHP_EOL;
                echo "    * Name: " . $adminData->staff->first_name . ' ' . $adminData->staff->last_name . PHP_EOL;
                echo "    * Contact: " . $adminData->staff->contact_number . PHP_EOL;
                echo "    * Role: " . $adminData->staff->role . PHP_EOL;
            } else {
                echo "  ❌ No staff data found!" . PHP_EOL;
            }
        } else {
            echo "❌ Admin data not passed to view" . PHP_EOL;
        }
    } else {
        echo "❌ Show method not returning a view" . PHP_EOL;
    }

    echo PHP_EOL . "🎯 Admin profile show page should now display all admin details from staff table!" . PHP_EOL;

} catch(Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
}
?>
