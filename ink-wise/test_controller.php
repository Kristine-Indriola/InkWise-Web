<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing corporateInvitations controller method...\n";

try {
    $controller = app()->make('App\Http\Controllers\Customer\InvitationController');

    // Get the view response
    $response = $controller->corporateInvitations();

    // Extract data from the view
    $viewData = $response->getData();

    if (isset($viewData['products'])) {
        $products = $viewData['products'];
        echo "Products in view: " . $products->count() . "\n";

        if ($products->count() > 0) {
            $firstProduct = $products->first();
            echo "First product ID: " . $firstProduct->id . "\n";
            echo "First product name: " . $firstProduct->name . "\n";
            echo "Has product_images attribute: " . (isset($firstProduct->product_images) ? 'YES' : 'NO') . "\n";
            echo "Has uploads: " . ($firstProduct->uploads ? 'YES (' . $firstProduct->uploads->count() . ')' : 'NO') . "\n";
        }
    } else {
        echo "No products found in view data\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
