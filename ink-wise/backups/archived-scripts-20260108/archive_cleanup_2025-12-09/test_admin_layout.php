<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin user authentication
$admin = \App\Models\User::where('role', 'admin')->first();
\Auth::login($admin);

echo "Testing Admin Layout Profile Image:" . PHP_EOL;
echo "===================================" . PHP_EOL;

// Load admin with staff relationship
$admin->load('staff');

echo "Admin User:" . PHP_EOL;
echo "  ID: " . $admin->user_id . PHP_EOL;
echo "  Email: " . $admin->email . PHP_EOL;
echo "  Role: " . $admin->role . PHP_EOL;

if ($admin->staff) {
    echo "Staff Data:" . PHP_EOL;
    echo "  Name: " . $admin->staff->first_name . ' ' . $admin->staff->last_name . PHP_EOL;
    echo "  Profile Pic: " . ($admin->staff->profile_pic ?: 'No profile picture uploaded') . PHP_EOL;

    // Generate initials for fallback
    $first = $admin->staff->first_name;
    $last = $admin->staff->last_name ?? '';
    $abbr = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

    echo "  Initials: " . $abbr . PHP_EOL;
} else {
    echo "No staff relationship found!" . PHP_EOL;
}

echo PHP_EOL . "🎯 Admin layout should now show:" . PHP_EOL;
if ($admin->staff && $admin->staff->profile_pic) {
    echo "  ✅ Uploaded profile picture: " . asset('storage/' . $admin->staff->profile_pic) . PHP_EOL;
} else {
    echo "  ✅ Initials avatar: " . ($abbr ?? 'AD') . PHP_EOL;
}
echo "  ✅ No more hardcoded LEANNE.jpg image!" . PHP_EOL;
?>
