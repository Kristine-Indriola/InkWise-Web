<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Comparing Admin Data Sources:" . PHP_EOL;
echo "==============================" . PHP_EOL;

// Get admin user with all relationships
$admin = \App\Models\User::where('role', 'admin')->with(['staff', 'address'])->first();

echo "USERS TABLE DATA:" . PHP_EOL;
echo "  user_id: " . $admin->user_id . PHP_EOL;
echo "  name: '" . $admin->name . "'" . PHP_EOL;
echo "  email: " . $admin->email . PHP_EOL;
echo "  role: " . $admin->role . PHP_EOL;
echo "  status: " . $admin->status . PHP_EOL;
echo "  created_at: " . $admin->created_at . PHP_EOL;

echo PHP_EOL . "STAFF TABLE DATA (currently shown on admin profile):" . PHP_EOL;
if ($admin->staff) {
    echo "  staff_id: " . $admin->staff->staff_id . PHP_EOL;
    echo "  user_id: " . $admin->staff->user_id . PHP_EOL;
    echo "  first_name: '" . $admin->staff->first_name . "'" . PHP_EOL;
    echo "  last_name: '" . $admin->staff->last_name . "'" . PHP_EOL;
    echo "  role: '" . $admin->staff->role . "'" . PHP_EOL;
    echo "  contact_number: '" . $admin->staff->contact_number . "'" . PHP_EOL;
    echo "  address: '" . $admin->staff->address . "'" . PHP_EOL;
}

echo PHP_EOL . "ADDRESS TABLE DATA:" . PHP_EOL;
if ($admin->address) {
    echo "  street: '" . $admin->address->street . "'" . PHP_EOL;
    echo "  barangay: '" . $admin->address->barangay . "'" . PHP_EOL;
    echo "  city: '" . $admin->address->city . "'" . PHP_EOL;
    echo "  province: '" . $admin->address->province . "'" . PHP_EOL;
    echo "  postal_code: '" . $admin->address->postal_code . "'" . PHP_EOL;
    echo "  country: '" . $admin->address->country . "'" . PHP_EOL;
} else {
    echo "  No address data" . PHP_EOL;
}

echo PHP_EOL . "CURRENT ADMIN PROFILE DISPLAYS:" . PHP_EOL;
echo "  Name: " . ($admin->staff ? $admin->staff->first_name . ' ' . $admin->staff->last_name : $admin->name) . PHP_EOL;
echo "  Email: " . $admin->email . PHP_EOL;
echo "  Role: " . $admin->role . " (from users table)" . PHP_EOL;
echo "  Contact: " . ($admin->staff ? $admin->staff->contact_number : 'N/A') . PHP_EOL;
echo "  Address: " . ($admin->staff ? $admin->staff->address : 'N/A') . " (from staff table)" . PHP_EOL;

echo PHP_EOL . "QUESTION: Should admin profile show data from users table or staff table?" . PHP_EOL;
?>
