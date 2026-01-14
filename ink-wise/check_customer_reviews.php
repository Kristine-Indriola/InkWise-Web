<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CustomerReview;
use App\Models\Template;

// Find recent customer reviews for template 43
$reviews = CustomerReview::where('template_id', 43)
    ->orderBy('updated_at', 'desc')
    ->take(5)
    ->get();

echo "Recent CustomerReviews for template 43:\n";
foreach ($reviews as $review) {
    echo "---\n";
    echo "ID: {$review->id}\n";
    echo "Template ID: {$review->template_id}\n";
    echo "Customer ID: " . ($review->customer_id ?? 'NULL') . "\n";
    echo "design_svg: " . (empty($review->design_svg) ? 'EMPTY' : 'LENGTH=' . strlen($review->design_svg)) . "\n";
    echo "preview_image: " . ($review->preview_image ?? 'NULL') . "\n";
    echo "review_text: " . ($review->review_text ?? 'NULL') . "\n";
    echo "Updated: " . $review->updated_at . "\n";
    
    if (!empty($review->design_svg)) {
        echo "First 200 chars of design_svg:\n" . substr($review->design_svg, 0, 200) . "\n";
    }
}

if ($reviews->isEmpty()) {
    echo "No customer reviews found for template 43\n";
}
