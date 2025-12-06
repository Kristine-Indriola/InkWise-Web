<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing staff profile field connections...\n";

try {
    // Test getting a staff user
    $user = \App\Models\User::with('staff')->whereHas('staff')->first();

    if ($user) {
        echo "Found user: " . $user->name . " (ID: " . $user->id . ")\n";
        echo "User email: " . $user->email . "\n";
        echo "User phone: " . ($user->phone ?? 'NULL') . "\n";

        if ($user->staff) {
            echo "\nStaff table data:\n";
            echo "Staff ID: " . $user->staff->staff_id . "\n";
            echo "First Name: '" . ($user->staff->first_name ?? 'NULL') . "'\n";
            echo "Middle Name: '" . ($user->staff->middle_name ?? 'NULL') . "'\n";
            echo "Last Name: '" . ($user->staff->last_name ?? 'NULL') . "'\n";
            echo "Contact Number: '" . ($user->staff->contact_number ?? 'NULL') . "'\n";
            echo "Address: '" . ($user->staff->address ?? 'NULL') . "'\n";
            echo "Role: '" . ($user->staff->role ?? 'NULL') . "'\n";
            echo "Profile Pic: '" . ($user->staff->profile_pic ?? 'NULL') . "'\n";
            echo "Status: '" . ($user->staff->status ?? 'NULL') . "'\n";

            // Test name construction
            $constructedName = '';
            if ($user->staff->first_name) {
                $constructedName = trim($user->staff->first_name . ' ' . ($user->staff->middle_name ? $user->staff->middle_name . ' ' : '') . $user->staff->last_name);
            }
            echo "\nConstructed full name: '" . $constructedName . "'\n";
            echo "Matches user.name: " . ($user->name === $constructedName ? 'YES' : 'NO') . "\n";
        }

        echo "\nTest completed successfully!\n";
    } else {
        echo "No staff users found in database.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
