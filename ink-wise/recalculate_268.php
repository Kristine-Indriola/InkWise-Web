<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(268);
if ($order) {
    $orderFlowService = app(\App\Services\OrderFlowService::class);
    $orderFlowService->recalculateOrderTotals($order);
    echo 'Recalculated totals for order 268' . PHP_EOL;
    echo 'New Total Amount: ' . $order->fresh()->total_amount . PHP_EOL;
    echo 'New Subtotal Amount: ' . $order->fresh()->subtotal_amount . PHP_EOL;
} else {
    echo 'Order not found';
}