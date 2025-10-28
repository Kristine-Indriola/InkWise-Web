<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$orders = \App\Models\Order::orderBy('id', 'desc')->limit(10)->get(['id','order_number','status','user_id','customer_id','created_at']);
if ($orders->isEmpty()) {
    echo "NO_ORDERS\n";
    exit(0);
}
foreach ($orders as $o) {
    printf("%s | id=%s | status=%s | user_id=%s | customer_id=%s | created_at=%s\n", $o->order_number ?? ('#'.$o->id), $o->id, $o->status, $o->user_id, $o->customer_id, $o->created_at);
}
