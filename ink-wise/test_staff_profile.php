<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing staff profile functionality...\n";

try {
    // Test getting a staff user
    $user = \App\Models\User::with('staff')->whereHas('staff')->first();

    if ($user) {
        echo "Found user: " . $user->name . " (ID: " . $user->id . ")\n";
        echo "Has staff relationship: " . ($user->staff ? 'YES' : 'NO') . "\n";

        if ($user->staff) {
            echo "Staff profile_pic: " . ($user->staff->profile_pic ?? 'NULL') . "\n";
            echo "Staff address: " . ($user->staff->address ?? 'NULL') . "\n";
        }

        // Test the controller methods
        $controller = app()->make('App\Http\Controllers\StaffProfileController');

        // Test edit method
        $editResponse = $controller->edit();
        echo "Edit method works: " . (is_object($editResponse) ? 'YES' : 'NO') . "\n";

        echo "Test completed successfully!\n";
    } else {
        echo "No staff users found in database.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
