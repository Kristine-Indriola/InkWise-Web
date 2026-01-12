<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::find(105);
echo "Order ID: " . $order->id . "\n";
echo "Customer ID: " . $order->customer_id . "\n\n";

// Use the presenter to see what items come through
$presented = \App\Support\Admin\OrderSummaryPresenter::make($order);
echo "Presented items:\n";

foreach ($presented['items'] as $idx => $item) {
    echo "  Item $idx:\n";
    echo "    ID: " . ($item['id'] ?? 'N/A') . "\n";
    echo "    Name: " . ($item['name'] ?? 'N/A') . "\n";
    echo "    product_id: " . ($item['product_id'] ?? 'N/A') . "\n";
    echo "    template_id: " . ($item['template_id'] ?? 'N/A') . "\n";
    
    // Check if there's a matching CustomerReview
    $templateId = $item['template_id'] ?? null;
    $customerId = $order->customer_id;
    
    if ($templateId && $customerId) {
        $review = \App\Models\CustomerReview::query()
            ->where('template_id', $templateId)
            ->where('customer_id', $customerId)
            ->whereNotNull('design_svg')
            ->where('design_svg', '!=', '')
            ->latest('updated_at')
            ->first();
        
        if ($review) {
            echo "    FOUND CustomerReview ID: " . $review->id . " with SVG (" . strlen($review->design_svg) . " chars)\n";
        } else {
            echo "    No CustomerReview found for template_id=$templateId, customer_id=$customerId\n";
        }
    } else {
        echo "    No template_id or customer_id to look up\n";
    }
    echo "---\n";
}
