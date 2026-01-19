<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(268);
$payments = $order->payments;
echo 'Number of payments: ' . $payments->count() . PHP_EOL;
foreach ($payments as $payment) {
    echo 'Payment ID: ' . $payment->id . ', Amount: ' . $payment->amount . ', Status: ' . $payment->status . PHP_EOL;
}