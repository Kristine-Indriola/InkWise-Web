<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Get the first user to see what columns exist
    $user = App\Models\User::first();
    
    if ($user) {
        echo "First user data:\n";
        echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No users found in the database.\n";
        
        // Try to get table structure
        $columns = DB::select("DESCRIBE users");
        echo "Users table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column->Field} ({$column->Type})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Try to check if users table exists
    try {
        $tables = DB::select("SHOW TABLES");
        echo "Available tables:\n";
        foreach ($tables as $table) {
            $values = array_values((array)$table);
            echo "- " . $values[0] . "\n";
        }
    } catch (Exception $e2) {
        echo "Could not check database tables: " . $e2->getMessage() . "\n";
    }
}
?>