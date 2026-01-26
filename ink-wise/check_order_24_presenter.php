<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(24);
$presented = \App\Support\Admin\OrderSummaryPresenter::make($order);
echo 'Order Summary Items:' . PHP_EOL;
foreach ($presented['items'] as $item) {
    echo 'Item ID: ' . $item['id'] . ', Name: ' . $item['name'] . ', Unit Price: ' . $item['unit_price'] . ', Line Type: ' . $item['line_type'] . PHP_EOL;
}