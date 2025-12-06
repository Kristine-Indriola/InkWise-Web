<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check if admins table still exists
    $adminsTable = DB::select("SHOW TABLES LIKE 'admins'");
    if (!empty($adminsTable)) {
        echo "❌ admins table still EXISTS" . PHP_EOL;
    } else {
        echo "✅ admins table has been DROPPED successfully" . PHP_EOL;
    }

    echo PHP_EOL;

    // Confirm admin data is still accessible through staff table
    $admin = \App\Models\User::where('role', 'admin')->with('staff')->first();
    if($admin && $admin->staff) {
        echo "✅ Admin data still accessible through staff table:" . PHP_EOL;
        echo "  Name: " . $admin->staff->first_name . ' ' . $admin->staff->last_name . PHP_EOL;
        echo "  Email: " . $admin->email . PHP_EOL;
        echo "  Contact: " . $admin->staff->contact_number . PHP_EOL;
    } else {
        echo "❌ Admin data not found!" . PHP_EOL;
    }

} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
