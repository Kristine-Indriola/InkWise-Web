<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Services\OrderFlowService;

echo "Testing material deduction fix for order 214...\n\n";

// Get the order
$order = Order::find(214);
if (!$order) {
    echo "Order 214 not found!\n";
    exit(1);
}

echo "Order 214 found. Status: {$order->status}\n";

// Get the OrderFlowService
$orderFlowService = app(OrderFlowService::class);

// Check order payment status
echo "Order payment status: {$order->payment_status}\n";

// Temporarily set payment status to 'paid' to force material deduction
$originalPaymentStatus = $order->payment_status;
$order->payment_status = 'paid';
$order->save();

// Sync material usage (this will recalculate and update existing records)
echo "Syncing material usage for order 214...\n";
$orderFlowService->syncMaterialUsage($order);

// Restore original payment status
$order->payment_status = $originalPaymentStatus;
$order->save();

echo "Material deduction completed.\n\n";

// Check the results
echo "Checking material usage results:\n";
$materialUsages = \App\Models\ProductMaterial::where('order_id', 214)
    ->with('material')
    ->get();

foreach ($materialUsages as $usage) {
    echo "- {$usage->material->material_name}: {$usage->quantity_used} sheets (Source: {$usage->source_type}, Source ID: {$usage->source_id})\n";
}

echo "\nTest completed.\n";