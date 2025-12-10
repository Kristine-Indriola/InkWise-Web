<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Staff;

echo "Checking existing records...\n";
$conf = User::where('email','owner@test.com')->first();
if ($conf) {
    echo "FOUND owner@test.com -> user_id=" . $conf->user_id . "\n";
} else {
    echo "NO existing owner@test.com\n";
}

$u = User::find(2);
if (!$u) {
    echo "user_id=2 NOT FOUND\n";
    exit(1);
}

echo "user_id=2 -> email=" . ($u->email ?? 'NULL') . "\n";

$s = Staff::find(8879);
if ($s) {
    echo "staff 8879 -> user_id=" . ($s->user_id ?? 'NULL') . ", role=" . ($s->role ?? 'NULL') . "\n";
} else {
    echo "staff 8879 NOT FOUND\n";
    exit(1);
}

if ($conf && $conf->user_id != 2) {
    echo "Conflict: owner@test.com already owned by user_id=" . $conf->user_id . ". Aborting.\n";
    exit(1);
}

// Perform updates
$u->email = 'owner@test.com';
$u->save();
echo "Updated users.user_id=2 email -> owner@test.com\n";

$s->user_id = 2;
$s->save();
echo "Updated staff id 8879 user_id -> 2\n";

// Final verification
echo "Final check:\n";
$u = User::find(2);
echo "user_id=2 -> email=" . ($u->email ?? 'NULL') . "\n";
$s = Staff::find(8879);
echo "staff 8879 -> user_id=" . ($s->user_id ?? 'NULL') . ", role=" . ($s->role ?? 'NULL') . "\n";

echo "Done.\n";
