<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(24);
echo 'Order Summary Snapshot:' . PHP_EOL;
print_r($order->summary_snapshot);
echo PHP_EOL . 'Order Metadata:' . PHP_EOL;
print_r($order->metadata);
echo PHP_EOL . 'Design Metadata for Item 50:' . PHP_EOL;
$item = $order->items->find(50);
if ($item) {
    print_r($item->design_metadata);
}