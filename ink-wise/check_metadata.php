<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::find(24);
if ($order) {
    $service = app(App\Services\OrderFlowService::class);
    $summary = $service->buildSummary($order);

    echo 'Summary giveaways: ' . json_encode(data_get($summary, 'giveaways'), JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'Summary giveaway: ' . json_encode(data_get($summary, 'giveaway'), JSON_PRETTY_PRINT) . PHP_EOL;

    // Check what the view logic would find
    $hasGiveaway = !empty(data_get($summary, 'giveaway'));
    echo 'hasGiveaway: ' . ($hasGiveaway ? 'true' : 'false') . PHP_EOL;

    $giveawayItems = collect(data_get($summary, 'giveaways', []))->filter(fn ($item) => is_array($item));
    echo 'giveawayItems from giveaways: ' . $giveawayItems->count() . PHP_EOL;

    if ($giveawayItems->isEmpty()) {
        $rawGiveaway = data_get($summary, 'giveaway');
        echo 'rawGiveaway: ' . json_encode($rawGiveaway) . PHP_EOL;
        if ($hasGiveaway && is_array($rawGiveaway)) {
            $giveawayItems = collect([$rawGiveaway]);
            echo 'giveawayItems from giveaway: ' . $giveawayItems->count() . PHP_EOL;
        }
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

    $giveawayTotalCalc = $giveawayItems->sum(fn ($item) => $extractTotal($item));
    echo 'giveawayTotalCalc: ' . $giveawayTotalCalc . PHP_EOL;

    $extras = (array) data_get($summary, 'extras', []);
    $giveawayTotal = (float) ($extras['giveaway'] ?? 0);
    echo 'giveawayTotal from extras: ' . $giveawayTotal . PHP_EOL;

    if ($giveawayTotalCalc <= 0) {
        $giveawayTotalCalc = $giveawayTotal;
    }
    echo 'final giveawayTotalCalc: ' . $giveawayTotalCalc . PHP_EOL;

} else {
    echo 'Order not found';
}