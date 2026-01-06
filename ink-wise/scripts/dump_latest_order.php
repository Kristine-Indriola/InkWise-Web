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

echo 'Order #' . ($order->order_number ?? $order->id) . PHP_EOL;
foreach ($order->items as $item) {
    $total = $item->subtotal ?? ($item->unit_price * $item->quantity);
    echo implode(' | ', [
        $item->line_type,
        $item->product_name,
        $item->quantity,
        number_format((float) $total, 2),
    ]) . PHP_EOL;
}

$totalAmount = $order->total_amount ?? $order->items->sum(fn ($item) => ($item->subtotal ?? 0));
echo 'Order total: ' . number_format((float) $totalAmount, 2) . PHP_EOL;
