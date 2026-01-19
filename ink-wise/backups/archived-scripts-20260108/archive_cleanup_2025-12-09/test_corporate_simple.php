<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing corporate products query...\n";

$products = \App\Models\Product::where('event_type', 'Corporate')
    ->where('product_type', 'Invitation')
    ->whereHas('uploads')
    ->with('materials')
    ->get();

echo "Products found: " . $products->count() . "\n";

if ($products->count() > 0) {
    $product = $products->first();
    echo "Product ID: " . $product->id . "\n";
    echo "Product name: " . $product->name . "\n";
    echo "Has images: " . ($product->images ? 'YES' : 'NO') . "\n";
    echo "Has uploads: " . ($product->uploads ? 'YES (' . $product->uploads->count() . ')' : 'NO') . "\n";

    // Test the attribute setting
    $product->setAttribute('product_images', $product->images);
    echo "product_images attribute set: " . (isset($product->product_images) ? 'YES' : 'NO') . "\n";
}

echo "Test completed!\n";
