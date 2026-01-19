<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking corporate products...\n";

$products = \App\Models\Product::where('event_type', 'Corporate')
    ->where('product_type', 'Invitation')
    ->whereHas('uploads')
    ->with(['uploads', 'images'])
    ->get();

echo "Published corporate products: " . $products->count() . "\n";

foreach ($products as $product) {
    echo "ID: " . $product->id . ", Name: " . $product->name . ", Published: " . ($product->published_at ? 'YES' : 'NO') . "\n";
    echo "  Has uploads: " . ($product->uploads->count() > 0 ? 'YES (' . $product->uploads->count() . ')' : 'NO') . "\n";
    echo "  Has images: " . ($product->images ? 'YES' : 'NO') . "\n";
    echo "  Published at: " . ($product->published_at ?? 'NULL') . "\n";
    echo "\n";
}
