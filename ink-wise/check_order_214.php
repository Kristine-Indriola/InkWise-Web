<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items')->find(214);
if ($order) {
    echo 'Order ID: ' . $order->id . PHP_EOL;
    echo 'Order Number: ' . $order->order_number . PHP_EOL;
    echo 'Status: ' . $order->status . PHP_EOL;
    echo 'Total Amount: ₱' . number_format($order->total_amount, 2) . PHP_EOL;
    echo PHP_EOL . 'Order Items:' . PHP_EOL;

    foreach ($order->items as $item) {
        echo '- ' . $item->product_name . ' (Qty: ' . $item->quantity . ', Subtotal: ₱' . number_format($item->subtotal, 2) . ')' . PHP_EOL;
    }

    echo PHP_EOL . 'Product Materials Deducted:' . PHP_EOL;
    $materials = \App\Models\ProductMaterial::where('order_id', 214)
        ->where('source_type', 'custom')
        ->where('quantity_used', '>', 0)
        ->with('material')
        ->get();

    foreach ($materials as $material) {
        echo '- ' . ($material->material->material_name ?? 'Unknown') . ': ' . $material->quantity_used . ' ' . $material->unit . PHP_EOL;
    }
} else {
    echo 'Order not found';
}
?>