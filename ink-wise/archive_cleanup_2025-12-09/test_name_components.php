<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing staff profile name components...\n";

try {
    // Test getting a staff user
    $user = \App\Models\User::with('staff')->whereHas('staff')->first();

    if ($user) {
        echo "Found user: " . $user->name . " (ID: " . $user->id . ")\n";
        echo "Has staff relationship: " . ($user->staff ? 'YES' : 'NO') . "\n";

        if ($user->staff) {
            echo "Staff first_name: '" . ($user->staff->first_name ?? 'NULL') . "'\n";
            echo "Staff middle_name: '" . ($user->staff->middle_name ?? 'NULL') . "'\n";
            echo "Staff last_name: '" . ($user->staff->last_name ?? 'NULL') . "'\n";

            // Test name construction
            $constructedName = '';
            if ($user->staff->first_name) {
                $constructedName = trim($user->staff->first_name . ' ' . ($user->staff->middle_name ? $user->staff->middle_name . ' ' : '') . $user->staff->last_name);
            }
            echo "Constructed name: '" . $constructedName . "'\n";
            echo "User name matches: " . ($user->name === $constructedName ? 'YES' : 'NO') . "\n";
        }

        echo "Test completed successfully!\n";
    } else {
        echo "No staff users found in database.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
