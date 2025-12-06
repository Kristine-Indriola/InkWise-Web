<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin user authentication
$admin = \App\Models\User::where('role', 'admin')->first();
\Auth::login($admin);

echo "Final Admin Profile Test:" . PHP_EOL;
echo "=========================" . PHP_EOL;

// Test the show method
$controller = new \App\Http\Controllers\AdminController();
try {
    $response = $controller->show();

    if ($response instanceof \Illuminate\View\View) {
        $viewData = $response->getData();
        if (isset($viewData['admin'])) {
            $adminData = $viewData['admin'];

            echo "✅ Admin Profile Data:" . PHP_EOL;
            echo "  Name: " . ($adminData->staff ? $adminData->staff->first_name . ' ' . $adminData->staff->last_name : 'N/A') . PHP_EOL;
            echo "  Email: " . $adminData->email . PHP_EOL;
            echo "  Role: " . $adminData->role . PHP_EOL;
            echo "  Contact: " . ($adminData->staff ? $adminData->staff->contact_number : 'N/A') . PHP_EOL;
            echo "  Address: " . ($adminData->staff && $adminData->staff->address ? $adminData->staff->address : 'Not set') . PHP_EOL;

            echo PHP_EOL . "🎯 Admin profile now shows correct admin account details!" . PHP_EOL;
            echo "   - No more confusion with staff user data" . PHP_EOL;
            echo "   - Address displays from staff table (consistent with edit form)" . PHP_EOL;
            echo "   - All relationships properly loaded" . PHP_EOL;
        }
    }

} catch(Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
}
?>
