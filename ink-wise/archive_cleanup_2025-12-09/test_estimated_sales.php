<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

echo "=== Order Status Analysis ===\n\n";

// Get all distinct statuses
$statuses = Order::select('status')->distinct()->pluck('status');
echo "Available order statuses:\n";
foreach ($statuses as $status) {
    $count = Order::where('status', $status)->count();
    $total = Order::where('status', $status)->sum('total_amount');
    echo "  - {$status}: {$count} orders, ₱" . number_format($total, 2) . "\n";
}

echo "\n=== Incomplete Orders ===\n";
$incompleteStatuses = ['pending', 'processing', 'confirmed', 'ready_for_pickup', 'shipped'];
$incompleteOrders = Order::whereIn('status', $incompleteStatuses)->get();
echo "Count: " . $incompleteOrders->count() . "\n";
echo "Total Amount: ₱" . number_format($incompleteOrders->sum('total_amount'), 2) . "\n";

echo "\n=== NOT Completed Orders ===\n";
$notCompletedOrders = Order::where('status', '!=', 'completed')->get();
echo "Count: " . $notCompletedOrders->count() . "\n";
echo "Total Amount: ₱" . number_format($notCompletedOrders->sum('total_amount'), 2) . "\n";

echo "\n=== Sample Incomplete Orders ===\n";
$samples = Order::where('status', '!=', 'completed')->take(5)->get();
foreach ($samples as $order) {
    echo "Order #{$order->id}: Status={$order->status}, Amount=₱" . number_format($order->total_amount, 2) . "\n";
}
