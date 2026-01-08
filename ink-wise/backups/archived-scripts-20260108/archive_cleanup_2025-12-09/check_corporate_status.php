<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Corporate products: " . \App\Models\Product::where('event_type', 'Corporate')->count() . PHP_EOL;
$corporateProducts = \App\Models\Product::where('event_type', 'Corporate')->get();
foreach ($corporateProducts as $product) {
    echo "- {$product->name} (ID: {$product->id}, Published: " . ($product->published_at ? 'Yes' : 'No') . ", Has uploads: " . ($product->uploads && $product->uploads->count() > 0 ? 'Yes' : 'No') . ")" . PHP_EOL;
    if ($product->uploads) {
        foreach ($product->uploads as $upload) {
            echo "  - Upload ID: {$upload->id}, Filename: {$upload->filename}" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Testing the exact query used in controller:" . PHP_EOL;
$products = \App\Models\Product::where('event_type', 'Corporate')
                              ->where('product_type', 'Invitation')
                              ->whereHas('uploads')
                              ->get();
echo "Products found by controller query: " . $products->count() . PHP_EOL;
foreach ($products as $product) {
    echo "- {$product->name} (ID: {$product->id})" . PHP_EOL;
    if ($product->images) {
        echo "  - Images: Front: " . ($product->images->front ?: 'null') . ", Back: " . ($product->images->back ?: 'null') . ", Preview: " . ($product->images->preview ?: 'null') . PHP_EOL;
    } else {
        echo "  - No images" . PHP_EOL;
    }
}
