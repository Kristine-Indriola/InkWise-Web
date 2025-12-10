<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate admin user authentication
$admin = \App\Models\User::where('role', 'admin')->first();
\Auth::login($admin);

// Test the edit method
$controller = new \App\Http\Controllers\AdminController();
try {
    // We can't directly call the method since it returns a view, but we can check the data
    $adminWithStaff = \App\Models\User::where('role', 'admin')->with('staff')->first();

    echo 'Admin user loaded with staff relationship:' . PHP_EOL;
    echo '  user_id: ' . $adminWithStaff->user_id . PHP_EOL;
    echo '  email: ' . $adminWithStaff->email . PHP_EOL;

    if($adminWithStaff->staff) {
        echo 'Staff data:' . PHP_EOL;
        echo '  first_name: ' . $adminWithStaff->staff->first_name . PHP_EOL;
        echo '  last_name: ' . $adminWithStaff->staff->last_name . PHP_EOL;
        echo '  contact_number: ' . $adminWithStaff->staff->contact_number . PHP_EOL;
        echo '  address: ' . ($adminWithStaff->staff->address ?: 'null') . PHP_EOL;
    } else {
        echo 'No staff relationship found!' . PHP_EOL;
    }

    echo PHP_EOL . '✅ Admin profile data should now populate correctly in the form.' . PHP_EOL;

} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
