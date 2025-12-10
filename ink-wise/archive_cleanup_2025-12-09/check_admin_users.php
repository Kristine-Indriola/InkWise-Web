<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::where('role', 'admin')->get(['user_id', 'name', 'email', 'role', 'status']);
echo 'Admin users: ' . $users->count() . PHP_EOL;

foreach($users as $user) {
    echo 'ID: ' . $user->user_id . ', Name: ' . $user->name . ', Email: ' . $user->email . ', Status: ' . $user->status . PHP_EOL;
    $staff = $user->staff;
    if($staff) {
        echo '  Staff: ' . $staff->first_name . ' ' . $staff->last_name . ' (' . $staff->role . ')' . PHP_EOL;
    } else {
        echo '  No staff record found' . PHP_EOL;
    }
}
?>
