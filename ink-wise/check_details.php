<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::find(24);
if ($order) {
    $primaryItem = $order->items()->where('line_type', 'invitation')->first();
    if ($primaryItem) {
        echo 'Primary item unit_price: ' . $primaryItem->unit_price . PHP_EOL;
        echo 'Primary item quantity: ' . $primaryItem->quantity . PHP_EOL;
        echo 'Primary item paperStockSelection: ' . json_encode($primaryItem->paperStockSelection) . PHP_EOL;

        if ($primaryItem->paperStockSelection) {
            echo 'Paper stock price: ' . $primaryItem->paperStockSelection->price . PHP_EOL;
        }
    }

    $metadata = $order->metadata;
    if (isset($metadata['giveaways'])) {
        foreach ($metadata['giveaways'] as $key => $giveaway) {
            echo 'Giveaway ' . $key . ': price=' . ($giveaway['price'] ?? 'N/A') . ', qty=' . ($giveaway['qty'] ?? 'N/A') . ', total=' . ($giveaway['total'] ?? 'N/A') . PHP_EOL;
        }
    }
} else {
    echo 'Order not found';
}