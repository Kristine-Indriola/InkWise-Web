<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update the staff record for user ID 3
$staff = App\Models\Staff::where('user_id', 3)->first();

if ($staff) {
    // Change the name to just "STAFF"
    $staff->first_name = 'STAFF';
    $staff->middle_name = null;
    $staff->last_name = ''; // Empty string instead of null
    $staff->role = 'STAFF';
    $staff->save();

    // Also update the user name
    $user = App\Models\User::find(3);
    if ($user) {
        $user->name = 'STAFF';
        $user->save();
    }

    echo "Staff record updated successfully.\n";
    echo "New name: STAFF\n";
    echo "New role: STAFF\n";
} else {
    echo "Staff record not found for user ID 3.\n";
}
?>
