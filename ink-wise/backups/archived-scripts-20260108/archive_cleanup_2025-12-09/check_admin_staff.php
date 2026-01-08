<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->with('staff')->first();
echo 'Admin user:' . PHP_EOL;
echo '  user_id: ' . $admin->user_id . PHP_EOL;
echo '  name: ' . $admin->name . PHP_EOL;
echo '  email: ' . $admin->email . PHP_EOL;
echo '  role: ' . $admin->role . PHP_EOL;
echo '  status: ' . $admin->status . PHP_EOL;

if($admin->staff) {
    echo 'Staff relationship:' . PHP_EOL;
    echo '  staff_id: ' . $admin->staff->staff_id . PHP_EOL;
    echo '  user_id: ' . $admin->staff->user_id . PHP_EOL;
    echo '  first_name: ' . $admin->staff->first_name . PHP_EOL;
    echo '  middle_name: ' . $admin->staff->middle_name . PHP_EOL;
    echo '  last_name: ' . $admin->staff->last_name . PHP_EOL;
    echo '  contact_number: ' . $admin->staff->contact_number . PHP_EOL;
    echo '  role: ' . $admin->staff->role . PHP_EOL;
    echo '  profile_pic: ' . $admin->staff->profile_pic . PHP_EOL;
} else {
    echo 'No staff relationship found!' . PHP_EOL;
}
?>
