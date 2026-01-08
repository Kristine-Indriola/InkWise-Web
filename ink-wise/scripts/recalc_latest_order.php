<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::with('items')->latest()->first();

if (! $order) {
    echo "No orders found" . PHP_EOL;
    exit(0);
}

app(App\Services\OrderFlowService::class)->recalculateOrderTotals($order);

$order->refresh();

echo 'Order #' . ($order->order_number ?? $order->id) . PHP_EOL;
echo 'Subtotal: ' . number_format((float) $order->subtotal_amount, 2) . PHP_EOL;
echo 'Shipping: ' . number_format((float) ($order->shipping_fee ?? 0), 2) . PHP_EOL;
echo 'Total: ' . number_format((float) $order->total_amount, 2) . PHP_EOL;
