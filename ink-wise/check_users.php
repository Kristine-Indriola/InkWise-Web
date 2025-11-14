<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for users
$users = App\Models\User::all(['id', 'name', 'email', 'role']);

echo "Users in database:\n";
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$user->role}\n";
}

if ($users->isEmpty()) {
    echo "No users found. Need to create one.\n";
} else {
    echo "\nTo test the template creation, you need to login first.\n";
    echo "Use one of the emails above at: http://127.0.0.1:8000/login\n";
}
?>