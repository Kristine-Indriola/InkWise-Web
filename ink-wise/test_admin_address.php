<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin user authentication
$admin = \App\Models\User::where('role', 'admin')->first();
\Auth::login($admin);

echo "Testing Admin Profile with Address Data:" . PHP_EOL;
echo "=========================================" . PHP_EOL;

// Test the show method
$controller = new \App\Http\Controllers\AdminController();
try {
    $response = $controller->show();

    if ($response instanceof \Illuminate\View\View) {
        $viewData = $response->getData();
        if (isset($viewData['admin'])) {
            $adminData = $viewData['admin'];

            echo "✅ Admin data loaded with relationships:" . PHP_EOL;
            echo "  - User ID: " . $adminData->user_id . PHP_EOL;
            echo "  - Email: " . $adminData->email . PHP_EOL;
            echo "  - Role: " . $adminData->role . PHP_EOL;

            if ($adminData->staff) {
                echo "  - Staff Data:" . PHP_EOL;
                echo "    * Name: " . $adminData->staff->first_name . ' ' . $adminData->staff->last_name . PHP_EOL;
                echo "    * Contact: " . $adminData->staff->contact_number . PHP_EOL;
                echo "    * Role: " . $adminData->staff->role . PHP_EOL;
            }

            if ($adminData->address) {
                echo "  - Address Data (now loaded!):" . PHP_EOL;
                echo "    * Street: " . $adminData->address->street . PHP_EOL;
                echo "    * Barangay: " . $adminData->address->barangay . PHP_EOL;
                echo "    * City: " . $adminData->address->city . PHP_EOL;
                echo "    * Province: " . $adminData->address->province . PHP_EOL;
                echo "    * Postal Code: " . $adminData->address->postal_code . PHP_EOL;
                echo "    * Country: " . $adminData->address->country . PHP_EOL;
            } else {
                echo "  ❌ Address data not loaded" . PHP_EOL;
            }
        }
    }

    echo PHP_EOL . "🎯 Admin profile should now show complete address information!" . PHP_EOL;

} catch(Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
}
?>
