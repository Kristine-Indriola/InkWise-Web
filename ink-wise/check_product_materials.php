<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items.paperStockSelection')->find(214);
if ($order) {
    foreach ($order->items as $item) {
        echo 'Item: ' . $item->product_name . ' (ID: ' . $item->product_id . ')' . PHP_EOL;

        $productMaterials = \App\Models\ProductMaterial::where('product_id', $item->product_id)
            ->whereNull('order_id')
            ->get();

        echo 'Product Materials:' . PHP_EOL;
        foreach ($productMaterials as $pm) {
            echo '  - ' . $pm->item . ': ' . $pm->qty . ' ' . ($pm->quantity_mode ?? 'per_unit') . PHP_EOL;
        }

        $paperStock = $item->paperStockSelection;
        if ($paperStock) {
            echo 'Paper Stock: ' . ($paperStock->name ?? $paperStock->material_name) . PHP_EOL;
            if ($paperStock->material) {
                echo '  Material: ' . $paperStock->material->material_name . PHP_EOL;
            }
        } else {
            echo 'No paper stock selected' . PHP_EOL;
        }

        echo PHP_EOL;
    }
}
?>