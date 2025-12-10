<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking all users...\n\n";

// Check all users
$users = User::all();

echo "Found " . $users->count() . " users:\n\n";

foreach ($users as $user) {
    echo "User ID: " . $user->user_id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "Name: " . ($user->name ?? 'Not set') . "\n";

    if ($user->customer) {
        echo "Has Customer record: Yes\n";
        echo "Customer Name: " . $user->customer->first_name . " " . $user->customer->last_name . "\n";
    } else {
        echo "Has Customer record: No\n";
    }

    echo "---\n";
}

echo "\nChecking customer profiles...\n\n";

// Get customers with relationships
$customers = User::where('role', 'customer')
    ->with(['customer', 'address'])
    ->get();

echo "Found " . $customers->count() . " customers:\n\n";

foreach ($customers as $customer) {
    echo "User ID: " . $customer->user_id . "\n";
    echo "Email: " . $customer->email . "\n";
    echo "Name: " . ($customer->name ?? 'Not set') . "\n";

    if ($customer->customer) {
        echo "Customer Name: " . $customer->customer->first_name . " " . $customer->customer->last_name . "\n";
        echo "Contact: " . ($customer->customer->contact_number ?? 'Not set') . "\n";
    } else {
        echo "No customer record found\n";
    }

    if ($customer->address) {
        echo "Address: " . $customer->address->street . ", " . $customer->address->city . ", " . $customer->address->province . "\n";
    } else {
        echo "No address record found\n";
    }

    echo "---\n";
}
