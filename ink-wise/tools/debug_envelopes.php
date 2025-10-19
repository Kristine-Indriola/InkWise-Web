<?php

use App\Models\Order;
use App\Services\OrderFlowService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Envelope Products and Materials\n";

$envelopeProducts = DB::table('products')
    ->select('id', 'name', 'product_type')
    ->whereNotNull('name')
    ->whereRaw('LOWER(product_type) = ?', ['envelope'])
    ->orderBy('name')
    ->get();

if ($envelopeProducts->isEmpty()) {
    echo "- No envelope products found.\n";
    exit(0);
}

foreach ($envelopeProducts as $product) {
    printf("- Product %d: %s (%s)\n", $product->id, $product->name, $product->product_type);

    $envelope = DB::table('product_envelopes')
        ->select('id', 'envelope_material_name', 'material_id', 'price_per_unit')
        ->where('product_id', $product->id)
        ->first();

    if (!$envelope) {
        echo "  • No product_envelopes entry\n";
        continue;
    }

    printf("  • Envelope id %d, material_id %s, name %s, price %.2f\n",
        $envelope->id,
        $envelope->material_id !== null ? (string) $envelope->material_id : 'null',
        $envelope->envelope_material_name ?? '(null)',
        (float) $envelope->price_per_unit
    );

    if ($envelope->material_id) {
        $material = DB::table('materials')->where('material_id', $envelope->material_id)->first();
        if ($material) {
            $inventory = DB::table('inventory')->where('material_id', $material->material_id)->first();
            printf(
                "  • Linked material: %s (material stock %s, inventory stock %s)\n",
                $material->material_name,
                $material->stock_qty,
                $inventory?->stock_level
            );
        }
    }
}

echo "\nMost recent envelope order item\n";
$recentEnvelope = DB::table('order_items')
    ->select('id', 'order_id', 'product_id', 'product_name', 'quantity', 'design_metadata')
    ->where('line_type', 'envelope')
    ->orderByDesc('id')
    ->first();

if (!$recentEnvelope) {
    echo "- No envelope order items found.\n";
    exit(0);
}

printf("- Order item %d for order %d product %s (id %s) qty %d\n",
    $recentEnvelope->id,
    $recentEnvelope->order_id,
    $recentEnvelope->product_name,
    $recentEnvelope->product_id ?? 'null',
    $recentEnvelope->quantity
);

$metadata = json_decode($recentEnvelope->design_metadata ?? 'null', true);
if ($metadata) {
    echo '  design_metadata: ' . json_encode($metadata) . "\n";
}

$order = Order::with('items')->find($recentEnvelope->order_id);
if ($order) {
    /** @var OrderFlowService $service */
    $service = app(OrderFlowService::class);
    $service->syncMaterialUsage($order);
    echo "Synced material usage for order {$order->id}.\n";

    $usageRows = DB::table('product_materials')
        ->where('order_id', $order->id)
        ->orderBy('material_id')
        ->get();

    foreach ($usageRows as $row) {
        $rowArray = (array) $row;
        $summary = Arr::only($rowArray, [
            'id', 'material_id', 'order_item_id', 'source_type', 'qty', 'quantity_mode', 'quantity_required', 'quantity_used', 'item'
        ]);
        echo '  usage: ' . json_encode($summary) . "\n";
    }
}
