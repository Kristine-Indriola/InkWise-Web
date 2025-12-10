<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;

$staff = Staff::find(8879);
if ($staff) {
    echo "Staff ID: 8879 -> user_id = " . var_export($staff->user_id, true) . PHP_EOL;
    echo "role = " . var_export($staff->role, true) . PHP_EOL;
} else {
    echo "Staff ID 8879 not found\n";
}
