<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check if admins table exists
    $adminsTable = DB::select("SHOW TABLES LIKE 'admins'");
    if (!empty($adminsTable)) {
        echo "✅ admins table EXISTS" . PHP_EOL;

        // Check admins table structure
        $adminsColumns = DB::select('DESCRIBE admins');
        echo "Admins table columns:" . PHP_EOL;
        foreach($adminsColumns as $column) {
            echo '  ' . $column->Field . ' (' . $column->Type . ')' . PHP_EOL;
        }
    } else {
        echo "❌ admins table does NOT exist" . PHP_EOL;
    }

    echo PHP_EOL;

    // Check staff table structure
    $staffColumns = DB::select('DESCRIBE staff');
    echo "Staff table columns:" . PHP_EOL;
    foreach($staffColumns as $column) {
        echo '  ' . $column->Field . ' (' . $column->Type . ')' . PHP_EOL;
    }

    echo PHP_EOL;

    // Check admin user and staff relationship
    $admin = \App\Models\User::where('role', 'admin')->with('staff')->first();
    echo "Admin user data:" . PHP_EOL;
    echo "  User ID: " . $admin->user_id . PHP_EOL;
    echo "  Email: " . $admin->email . PHP_EOL;
    echo "  Role: " . $admin->role . PHP_EOL;

    if($admin->staff) {
        echo "  Staff Data (where admin profile is stored):" . PHP_EOL;
        echo "    Staff ID: " . $admin->staff->staff_id . PHP_EOL;
        echo "    First Name: " . $admin->staff->first_name . PHP_EOL;
        echo "    Last Name: " . $admin->staff->last_name . PHP_EOL;
        echo "    Contact: " . $admin->staff->contact_number . PHP_EOL;
        echo "    Role: " . $admin->staff->role . PHP_EOL;
    }

} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
