<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for staff
$staff = App\Models\Staff::all();

echo "Staff in database:\n";
foreach ($staff as $s) {
    echo "ID: {$s->staff_id}, User ID: {$s->user_id}, First: {$s->first_name}, Last: {$s->last_name}, Email: " . (isset($s->user->email) ? $s->user->email : 'N/A') . "\n";
}

if ($staff->isEmpty()) {
    echo "No staff found.\n";
}
?>
