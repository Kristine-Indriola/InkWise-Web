<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing corporate invitations controller...\n";

try {
    $controller = app()->make('App\Http\Controllers\Customer\InvitationController');
    $products = $controller->corporateInvitations();

    echo "Products loaded: " . $products->count() . "\n";

    if ($products->count() > 0) {
        $firstProduct = $products->first();
        echo "First product ID: " . $firstProduct->id . "\n";
        echo "Has product_images attribute: " . (isset($firstProduct->product_images) ? 'YES' : 'NO') . "\n";
        echo "Has uploads: " . ($firstProduct->uploads ? 'YES (' . $firstProduct->uploads->count() . ')' : 'NO') . "\n";
        echo "Has ratings: " . ($firstProduct->ratings ? 'YES (' . $firstProduct->ratings->count() . ')' : 'NO') . "\n";
    }

    echo "Test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
