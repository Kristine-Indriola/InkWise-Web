<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;

$staffId = 8879;
$staff = Staff::find($staffId);
if ($staff) {
    $old = $staff->user_id;
    $staff->user_id = 2;
    $staff->save();
    echo "Updated staff ID {$staffId} user_id from " . var_export($old, true) . " to 2\n";
    exit(0);
}

echo "Staff ID {$staffId} not found\n";
exit(1);
