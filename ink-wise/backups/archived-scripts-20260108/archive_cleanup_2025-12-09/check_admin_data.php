<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking Admin Profile Data:" . PHP_EOL;
echo "============================" . PHP_EOL;

// Get admin user
$admin = \App\Models\User::where('role', 'admin')->with('staff')->first();

echo "Admin User:" . PHP_EOL;
echo "  User ID: " . $admin->user_id . PHP_EOL;
echo "  Email: " . $admin->email . PHP_EOL;
echo "  Role: " . $admin->role . PHP_EOL;
echo "  Name field: '" . $admin->name . "'" . PHP_EOL;

if ($admin->staff) {
    echo PHP_EOL . "Linked Staff Record:" . PHP_EOL;
    echo "  Staff ID: " . $admin->staff->staff_id . PHP_EOL;
    echo "  User ID: " . $admin->staff->user_id . PHP_EOL;
    echo "  First Name: '" . $admin->staff->first_name . "'" . PHP_EOL;
    echo "  Last Name: '" . $admin->staff->last_name . "'" . PHP_EOL;
    echo "  Role: '" . $admin->staff->role . "'" . PHP_EOL;
    echo "  Contact: '" . $admin->staff->contact_number . "'" . PHP_EOL;
} else {
    echo "No staff record linked!" . PHP_EOL;
}

echo PHP_EOL . "All Staff Records with role='admin':" . PHP_EOL;
$adminStaff = \App\Models\Staff::where('role', 'admin')->get();
foreach ($adminStaff as $staff) {
    echo "  Staff ID: " . $staff->staff_id . ", User ID: " . $staff->user_id . ", Name: " . $staff->first_name . " " . $staff->last_name . ", Role: " . $staff->role . PHP_EOL;
}

echo PHP_EOL . "All Staff Records:" . PHP_EOL;
$allStaff = \App\Models\Staff::all();
foreach ($allStaff as $staff) {
    echo "  Staff ID: " . $staff->staff_id . ", User ID: " . $staff->user_id . ", Name: " . $staff->first_name . " " . $staff->last_name . ", Role: " . $staff->role . PHP_EOL;
}
?>
