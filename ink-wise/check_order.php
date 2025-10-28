<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::where('status', 'pending')->latest()->first();
if ($order) {
    echo 'Order ID: ' . $order->id . PHP_EOL;
    echo 'Order Number: ' . $order->order_number . PHP_EOL;
    echo 'Subtotal: ₱' . number_format($order->subtotal_amount, 2) . PHP_EOL;
    echo 'Tax: ₱' . number_format($order->tax_amount, 2) . PHP_EOL;
    echo 'Shipping: ₱' . number_format($order->shipping_fee, 2) . PHP_EOL;
    echo 'Total: ₱' . number_format($order->total_amount, 2) . PHP_EOL;
    echo 'Status: ' . $order->status . PHP_EOL;

    $payments = $order->payments;
    echo 'Payments: ' . $payments->count() . PHP_EOL;
    foreach ($payments as $payment) {
        echo '  - ₱' . number_format($payment->amount, 2) . ' (' . $payment->status . ')' . PHP_EOL;
    }
} else {
    echo 'No pending orders found' . PHP_EOL;
}