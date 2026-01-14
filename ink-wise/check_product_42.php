<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

// Find products using template 42
$products = Product::where('template_id', 42)->get();

echo "Products using template 42:\n";
foreach ($products as $product) {
    echo "  Product ID: {$product->id}\n";
    echo "  Name: {$product->name}\n";
    echo "  Image: " . ($product->image ?? 'NULL') . "\n";
    echo "---\n";
}

if ($products->isEmpty()) {
    echo "No products found using template 42\n";
    
    // Check if there's a product with name 'md cmsdms'
    $product = Product::where('name', 'md cmsdms')->first();
    if ($product) {
        echo "\nFound product 'md cmsdms':\n";
        echo "  ID: {$product->id}\n";
        echo "  Template ID: " . ($product->template_id ?? 'NULL') . "\n";
        echo "  Image: " . ($product->image ?? 'NULL') . "\n";
        
        if ($product->template) {
            echo "  Template ID: {$product->template->id}\n";
            echo "  Template Name: {$product->template->name}\n";
            echo "  Template preview_front: " . ($product->template->preview_front ?? 'NULL') . "\n";
            echo "  Template preview: " . ($product->template->preview ?? 'NULL') . "\n";
            
            $meta = $product->template->metadata;
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?: [];
            }
            echo "  Template svg_path: " . ($meta['svg_path'] ?? 'NULL') . "\n";
            echo "  Template json_path: " . ($meta['json_path'] ?? 'NULL') . "\n";
            
            // Check if files exist
            $previewFront = $product->template->preview_front;
            if ($previewFront) {
                echo "  preview_front exists: " . (Storage::disk('public')->exists($previewFront) ? 'YES' : 'NO') . "\n";
            }
        }
    }
}

echo "\n=== Done ===\n";
