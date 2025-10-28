<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing database connection...\n";

try {
    // Test basic database connection
    $pdo = DB::connection()->getPdo();
    echo "Database connection successful!\n";

    // Check products table
    $productCount = DB::table('products')->count();
    echo "Total products: $productCount\n";

    if ($productCount > 0) {
        $products = DB::table('products')->select('id', 'name', 'product_type')->get();
        echo "Products:\n";
        foreach ($products as $product) {
            echo "  - ID: {$product->id}, Name: {$product->name}, Type: {$product->product_type}\n";
        }
    }

    // Check giveaway products
    $giveawayCount = DB::table('products')->where('product_type', 'Giveaway')->count();
    echo "Giveaway products: $giveawayCount\n";

    // Check materials table
    $materialCount = DB::table('materials')->count();
    echo "Total materials: $materialCount\n";

    // Check product_materials table
    $productMaterialCount = DB::table('product_materials')->count();
    echo "Product-material relationships: $productMaterialCount\n";

    if ($giveawayCount > 0) {
        echo "\nSample giveaway product:\n";
        $giveaway = DB::table('products')
            ->join('product_materials', 'products.id', '=', 'product_materials.product_id')
            ->join('materials', 'product_materials.material_id', '=', 'materials.material_id')
            ->where('products.product_type', 'giveaway')
            ->select('products.name as product_name', 'materials.material_name as material_name')
            ->first();

        if ($giveaway) {
            echo "Product: {$giveaway->product_name}\n";
            echo "Material: {$giveaway->material_name}\n";
        }
    }

    echo "\nDatabase test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}