<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check notifications for user ID 7 (customer)
$userId = 7;
$notifications = \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $userId)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

echo "Notifications for User ID $userId (Customer):\n";
echo "=====================================\n";

foreach($notifications as $n) {
    echo 'ID: ' . $n->id . PHP_EOL;
    echo 'Type: ' . $n->type . PHP_EOL;
    echo 'Message: ' . ($n->data['message'] ?? 'No message') . PHP_EOL;
    echo 'Created: ' . $n->created_at . PHP_EOL;
    echo '---' . PHP_EOL;
}