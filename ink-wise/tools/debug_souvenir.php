<?php

use App\Models\Order;
use App\Services\OrderFlowService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$products = DB::table('products')
    ->select('id', 'name', 'product_type')
    ->whereNotNull('name')
    ->whereIn(DB::raw('LOWER(product_type)'), ['souvenir', 'souvenirs', 'giveaway', 'giveaways'])
    ->orderBy('name')
    ->get();

echo "Products:\n";
if ($products->isEmpty()) {
    echo "- (none found)\n";
}

foreach ($products as $product) {
    printf("- ID: %d, Name: %s, Type: %s\n", $product->id, $product->name, $product->product_type);
}

if ($products->isEmpty()) {
    $preview = DB::table('products')
        ->select('id', 'name', 'product_type')
        ->orderBy('product_type')
        ->limit(10)
        ->get();

    echo "\nSample products (first 10):\n";
    foreach ($preview as $row) {
        printf("- ID: %d, Name: %s, Type: %s\n", $row->id, $row->name, $row->product_type);
    }

    exit(0);
}

$likeMug = DB::table('products')
    ->select('id', 'name', 'product_type')
    ->where('name', 'like', '%mug%')
    ->orderBy('name')
    ->get();

if ($likeMug->isNotEmpty()) {
    echo "\nProducts with 'mug' in the name:\n";
    foreach ($likeMug as $row) {
        printf("- ID: %d, Name: %s, Type: %s\n", $row->id, $row->name, $row->product_type);
    }
}

$addonMugs = DB::table('product_addons')
    ->select('id', 'product_id', 'name')
    ->where('name', 'like', '%mug%')
    ->get();

if ($addonMugs->isNotEmpty()) {
    echo "\nAddons with 'mug' in the name:\n";
    foreach ($addonMugs as $addon) {
        printf("- ID: %d, Product %d, Name: %s\n", $addon->id, $addon->product_id, $addon->name);
    }
}

$recentGiveawayItem = DB::table('order_items')
    ->select('id', 'order_id', 'product_id', 'product_name', 'quantity', 'design_metadata')
    ->where('line_type', 'giveaway')
    ->orderByDesc('id')
    ->first();

if ($recentGiveawayItem) {
    echo "\nMost recent giveaway order item:\n";
    printf(
        "- Order Item %d (Order %d) Product %s (ID %s) Qty %s\n",
        $recentGiveawayItem->id,
        $recentGiveawayItem->order_id,
        $recentGiveawayItem->product_name,
        $recentGiveawayItem->product_id ?? 'null',
        $recentGiveawayItem->quantity
    );

    $metadata = json_decode($recentGiveawayItem->design_metadata ?? 'null', true);
    if ($metadata) {
        echo "  design_metadata: " . json_encode($metadata) . "\n";
    }

    $order = Order::with(['items' => function ($query) {
        $query->with(['product', 'addons']);
    }])->find($recentGiveawayItem->order_id);

    if ($order) {
        /** @var OrderFlowService $orderFlow */
        $orderFlow = app(OrderFlowService::class);
        $orderFlow->syncMaterialUsage($order);
        echo "  Synced material usage for order {$order->id}.\n";
    }
}

$materialNames = DB::table('materials')
    ->select('material_id', 'material_name', 'stock_qty')
    ->orderBy('material_name')
    ->get();

echo "\nMaterials (name => stock):\n";
foreach ($materialNames as $material) {
    printf("- %d => %s (stock %s)\n", $material->material_id, $material->material_name, $material->stock_qty);
}

$productIds = $products->pluck('id');

$productMaterials = DB::table('product_materials')
    ->select('id', 'product_id', 'material_id', 'item', 'qty')
    ->whereNull('order_id')
    ->whereIn('product_id', $productIds)
    ->orderBy('product_id')
    ->get();

echo "\nProduct Materials:\n";
foreach ($productMaterials as $row) {
    printf(
        "- Product %d => material_id: %s, item: %s, qty: %s\n",
        $row->product_id,
        $row->material_id ?? 'null',
        $row->item,
        $row->qty
    );
}

$addonRows = DB::table('product_addons')
    ->select('id', 'product_id', 'name', 'material_id')
    ->whereIn('product_id', $productIds)
    ->orderBy('product_id')
    ->get();

echo "\nAddons:\n";
foreach ($addonRows as $addon) {
    printf(
        "- Addon %d (product %d) => name: %s, material_id: %s\n",
        $addon->id,
        $addon->product_id,
        $addon->name,
        $addon->material_id ?? 'null'
    );
}
