<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with(['items.paperStockSelection.paperStock'])->find(24);
if ($order) {
    echo 'Order found: ' . $order->id . PHP_EOL;
    foreach ($order->items as $item) {
        echo 'Item: ' . $item->id . ', Line Type: ' . $item->line_type . ', Unit Price: ' . $item->unit_price . PHP_EOL;
        if ($item->paperStockSelection) {
            echo '  Paper Stock: ' . ($item->paperStockSelection->paperStock ? $item->paperStockSelection->paperStock->name : 'N/A') . ', Price: ' . $item->paperStockSelection->price . PHP_EOL;
        } else {
            echo '  No paper stock selection' . PHP_EOL;
        }
    }
} else {
    echo 'Order not found';
}