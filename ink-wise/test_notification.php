<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::first();
echo 'Order ID: ' . $order->id . ', Status: ' . $order->status . ', User ID: ' . $order->user_id . PHP_EOL;

$user = $order->user;
if ($user) {
    echo 'User name: ' . $user->name . ', Email: ' . $user->email . PHP_EOL;
    echo 'User notifications count: ' . $user->notifications()->count() . PHP_EOL;
} else {
    echo 'No user found' . PHP_EOL;
}
