<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;

$s = Staff::find(8879);
if ($s) {
    echo "staff 8879 -> user_id=" . var_export($s->user_id, true) . ", role=" . var_export($s->role, true) . PHP_EOL;
} else {
    echo "staff 8879 NOT FOUND\n";
}
