<?php

use App\Models\CustomerOrder;
use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Services\OrderFlowService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var OrderFlowService $service */
$service = app(OrderFlowService::class);

$product = Product::query()->whereRaw('LOWER(product_type) = ?', ['invitation'])->orderByDesc('id')->first();

if (!$product) {
    echo "No invitation product found.\n";
    exit(0);
}

$customerOrder = CustomerOrder::query()->orderBy('id')->first();
if (!$customerOrder) {
    $customerOrder = CustomerOrder::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '09171234567',
        'company' => 'InkWise QA',
        'address' => '123 Sample Street',
        'city' => 'Makati',
        'province' => 'Metro Manila',
        'postal_code' => '1200',
    ]);
}

$order = Order::create([
    'customer_order_id' => $customerOrder->id,
    'order_number' => 'INV-TEST-' . now()->format('His'),
    'status' => 'pending',
    'subtotal_amount' => 0,
    'tax_amount' => 0,
    'shipping_fee' => 0,
    'total_amount' => 0,
]);

$unitPrice = $service->unitPriceFor($product);
$summary = [
    'productId' => $product->id,
    'productName' => $product->name,
    'quantity' => 50,
    'unitPrice' => $unitPrice,
    'subtotalAmount' => $unitPrice * 50,
    'taxAmount' => 0,
    'shippingFee' => 0,
    'totalAmount' => $unitPrice * 50,
    'metadata' => [
        'final_step' => [
            'preview_selections' => [],
        ],
    ],
];

$service->initializeOrderFromSummary($order, $summary);

$order->refresh();
$service->syncMaterialUsage($order);

$order->refresh();

echo "Order {$order->order_number} totals: subtotal={$order->subtotal_amount} total={$order->total_amount}\n";

$usage = ProductMaterial::query()
    ->where('order_id', $order->id)
    ->where('source_type', 'custom')
    ->get();

echo "Usage records (custom):\n";
foreach ($usage as $row) {
    $material = Material::find($row->material_id);
    $materialName = $material?->material_name ?? '(unknown)';
    echo "- material {$row->material_id} ({$materialName}) required={$row->quantity_required} used={$row->quantity_used}\n";
}

echo "\nMaterial stock levels snapshot:\n";
$materials = Material::query()->whereIn('material_name', ['Matte', 'Glossy Premuim', 'Textured linen'])->get();
foreach ($materials as $material) {
    echo "- {$material->material_name}: stock_qty={$material->stock_qty}\n";
}

// Clean up test order to avoid polluting data
$order->delete();
