<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::find(24);
if ($order) {
    $items = $order->items;
    echo 'Order items count: ' . $items->count() . PHP_EOL;
    foreach ($items as $item) {
        echo 'Item ID: ' . $item->id . ', type: ' . $item->line_type . ', unit_price: ' . $item->unit_price . ', quantity: ' . $item->quantity . PHP_EOL;
    }
}