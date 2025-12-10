<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing corporate invitations route...\n";

try {
    // Simulate a request to the route
    $request = Illuminate\Http\Request::create('/templates/corporate/invitations', 'GET');
    $response = app()->handle($request);

    echo "Response status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() == 200) {
        $content = $response->getContent();

        // Check if the content contains the product
        if (strpos($content, 'Elegant Floral') !== false) {
            echo "✓ Product 'Elegant Floral' found in response\n";
        } else {
            echo "✗ Product 'Elegant Floral' NOT found in response\n";
        }

        // Check if there are invitation cards
        if (strpos($content, 'invitation-card') !== false) {
            echo "✓ Invitation cards found in response\n";
        } else {
            echo "✗ No invitation cards found in response\n";
        }

        // Check for empty state
        if (strpos($content, 'No corporate invitations yet') !== false) {
            echo "✗ Empty state message found (should not be shown)\n";
        } else {
            echo "✓ No empty state message (good)\n";
        }
    } else {
        echo "Route returned status " . $response->getStatusCode() . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
