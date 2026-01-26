<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(268);
if ($order) {
    echo 'Order ID: ' . $order->id . PHP_EOL;
    echo 'Order Number: ' . $order->order_number . PHP_EOL;
    echo 'Total Amount: ' . $order->total_amount . PHP_EOL;
    echo 'Grand Total: ' . $order->grand_total . PHP_EOL;
    echo 'Subtotal Amount: ' . $order->subtotal_amount . PHP_EOL;
    echo 'Tax Amount: ' . $order->tax_amount . PHP_EOL;
    echo 'Shipping Fee: ' . $order->shipping_fee . PHP_EOL;
    echo 'Grand Total Amount (method): ' . $order->grandTotalAmount() . PHP_EOL;
    echo 'Summary Snapshot: ' . json_encode($order->summary_snapshot, JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'Metadata: ' . json_encode($order->metadata, JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'Order not found';
}