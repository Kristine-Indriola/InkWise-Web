<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productMaterials = \App\Models\ProductMaterial::where('product_id', 64)
    ->whereNull('order_id')
    ->with('material')
    ->get();

echo 'ProductMaterial records for product 64:' . PHP_EOL;
foreach ($productMaterials as $pm) {
    echo 'ID: ' . $pm->id . ', Item: ' . $pm->item . ', Qty: ' . $pm->qty . ', Material: ' . ($pm->material->material_name ?? 'null') . ', Material ID: ' . $pm->material_id . PHP_EOL;
}

echo PHP_EOL . 'Order ProductMaterial records for order 214:' . PHP_EOL;
$orderMaterials = \App\Models\ProductMaterial::where('order_id', 214)
    ->with('material')
    ->get();

foreach ($orderMaterials as $om) {
    echo 'ID: ' . $om->id . ', Source: ' . $om->source_type . ', Material: ' . ($om->material->material_name ?? 'null') . ', Qty Used: ' . $om->quantity_used . ', Qty Required: ' . $om->quantity_required . PHP_EOL;
}
?>