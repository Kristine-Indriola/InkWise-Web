<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with(['items.product.paperStocks'])->find(24);
foreach ($order->items as $item) {
    echo 'Item: ' . $item->id . ', Product: ' . $item->product->name . PHP_EOL;
    if ($item->product->paperStocks) {
        echo '  Paper Stocks:' . PHP_EOL;
        foreach ($item->product->paperStocks as $ps) {
            echo '    ID: ' . $ps->id . ', Name: ' . $ps->name . ', Price: ' . $ps->price . PHP_EOL;
        }
    } else {
        echo '  No paper stocks' . PHP_EOL;
    }
}