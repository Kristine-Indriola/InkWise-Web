<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(268);
if ($order) {
    $orderFlowService = app(\App\Services\OrderFlowService::class);
    $primaryItem = $orderFlowService->primaryInvitationItem($order);
    if ($primaryItem) {
        $summarySnapshot = $orderFlowService->buildSummarySnapshot($order, $primaryItem);
        $order->update(['summary_snapshot' => $summarySnapshot]);
        echo 'Updated summary snapshot for order 268' . PHP_EOL;
        echo 'New summary snapshot: ' . json_encode($summarySnapshot, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo 'No primary item found';
    }
} else {
    echo 'Order not found';
}