<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Session;

$order = App\Models\Order::find(24);
if ($order) {
    $service = app(App\Services\OrderFlowService::class);

    // Simulate summary page: recalculate + update session
    echo "=== SIMULATING ORDER SUMMARY PAGE ===\n";
    $service->recalculateOrderTotals($order);
    $order->refresh();

    $summary = $service->refreshSummary($order);
    $totals = $service->calculateTotalsFromSummary($summary);
    $summary = array_merge($summary, $totals);

    // Simulate view calculation
    $invitationSubtotal = (float) data_get($summary, 'subtotalAmount', 0);
    $extras = (array) data_get($summary, 'extras', []);
    $envelopeTotal = (float) ($extras['envelope'] ?? 0);
    $giveawayTotal = (float) ($extras['giveaway'] ?? 0);
    $paperExtras = (float) ($extras['paper'] ?? 0);

    $invitationItems = collect(data_get($summary, 'items', []))->filter(fn ($item) => is_array($item));
    if ($invitationItems->isEmpty() && !empty($summary)) {
        $invitationItems = collect([
            [
                'name' => data_get($summary, 'productName', 'Custom invitation'),
                'quantity' => data_get($summary, 'quantity', 0),
                'unitPrice' => data_get($summary, 'unitPrice') ?? data_get($summary, 'paperStockPrice'),
                'paperStockName' => data_get($summary, 'paperStockName') ?? data_get($summary, 'paperStock.name'),
                'paperStockPrice' => data_get($summary, 'paperStockPrice') ?? data_get($summary, 'paperStock.price'),
                'total' => $paperExtras,
            ],
        ]);
    }

    $extractTotal = static function ($line) {
        return (float) (
            data_get($line, 'total')
            ?? data_get($line, 'totalAmount')
            ?? data_get($line, 'total_amount')
            ?? data_get($line, 'total_price')
            ?? data_get($line, 'price')
            ?? 0
        );
    };

    $computeInvitationTotal = static function ($item) use ($extractTotal) {
        $rawTotal = $extractTotal($item);
        if ($rawTotal > 0) {
            return max(0, $rawTotal);
        }

        $qty = data_get($item, 'quantity') ?? 0;
        $paperPrice = data_get($item, 'paperStockPrice') ?? 0;

        return max(0, $qty * (float) $paperPrice);
    };

    $invitationTotalCalc = $invitationItems->sum(fn ($item) => $computeInvitationTotal($item));
    if ($invitationTotalCalc <= 0) {
        $invitationTotalCalc = $paperExtras;
    }

    $envelopeTotalCalc = 0; // No envelopes in this order
    $giveawayTotalCalc = 0; // No giveaways in this order

    $grandTotal = $invitationTotalCalc + $envelopeTotalCalc + $giveawayTotalCalc;

    echo "Order summary page grandTotal: " . $grandTotal . PHP_EOL;
    echo "Session summary totalAmount: " . ($summary['totalAmount'] ?? 'N/A') . PHP_EOL;

    // Now simulate checkout page
    echo "\n=== SIMULATING CHECKOUT PAGE ===\n";
    // Checkout also does recalculateOrderTotals and updateSessionSummary
    // So it should be the same as above
    echo "Checkout page should show same total: " . $grandTotal . PHP_EOL;

    // Check if there's any difference in the order data
    echo "\n=== ORDER DATA ===\n";
    echo "Order total_amount: " . $order->total_amount . PHP_EOL;
    echo "Order subtotal_amount: " . $order->subtotal_amount . PHP_EOL;

    $primaryItem = $service->primaryInvitationItem($order);
    if ($primaryItem) {
        echo "Primary item unit_price: " . $primaryItem->unit_price . PHP_EOL;
        echo "Primary item quantity: " . $primaryItem->quantity . PHP_EOL;
        echo "Primary item total: " . ($primaryItem->unit_price * $primaryItem->quantity) . PHP_EOL;

        $paperStock = $primaryItem->paperStockSelection;
        if ($paperStock) {
            echo "Paper stock name: " . $paperStock->name . PHP_EOL;
            echo "Paper stock price: " . $paperStock->price . PHP_EOL;
        }
    }

} else {
    echo 'Order 24 not found' . PHP_EOL;
}