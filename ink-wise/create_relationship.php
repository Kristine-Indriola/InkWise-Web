<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Creating product-material relationship...\n";

try {
    // Get the existing giveaway product or create one
    $giveawayProduct = DB::table('products')->where('product_type', 'Giveaway')->first();

    if (!$giveawayProduct) {
        echo "No giveaway product found, creating one...\n";
        $productId = DB::table('products')->insertGetId([
            'name' => 'Custom Keychain Favor',
            'base_price' => 50.00,
            'unit_price' => 50.00,
            'product_type' => 'Giveaway',
            'description' => 'Beautiful custom keychain giveaway',
            'lead_time' => '3-5 business days',
            'lead_time_days' => 5,
            'event_type' => 'wedding',
            'theme_style' => 'modern',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $giveawayProduct = DB::table('products')->find($productId);
        echo "Created giveaway product with ID: $productId\n";
    }

    // Get a material
    $material = DB::table('materials')->where('material_type', 'giveaway')->first();

    if (!$material) {
        echo "No giveaway material found!\n";
        exit(1);
    }

    // Check if relationship already exists
    $existing = DB::table('product_materials')
        ->where('product_id', $giveawayProduct->id)
        ->where('material_id', $material->material_id)
        ->first();

    if ($existing) {
        echo "Relationship already exists!\n";
    } else {
        // Create the relationship
        DB::table('product_materials')->insert([
            'product_id' => $giveawayProduct->id,
            'material_id' => $material->material_id,
            'item' => 'giveaway',
            'type' => 'giveaway',
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Created product-material relationship!\n";
    }

    echo "Product: {$giveawayProduct->name}\n";
    echo "Material: {$material->material_name}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}