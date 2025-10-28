<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Material;
use App\Models\ProductMaterial;

class GiveawayTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create some materials
        $keychainMaterial = Material::create([
            'material_name' => 'Keychain',
            'material_type' => 'giveaway',
            'product_type' => 'Giveaway',
            'description' => 'Plastic keychain material',
            'unit_cost' => 25.00,
            'unit' => 'pieces',
            'stock_qty' => 1000,
        ]);

        $candleMaterial = Material::create([
            'material_name' => 'Scented Candle',
            'material_type' => 'giveaway',
            'product_type' => 'Giveaway',
            'description' => 'Scented candle material',
            'unit_cost' => 45.00,
            'unit' => 'pieces',
            'stock_qty' => 500,
        ]);

        // Create a giveaway product
        $giveawayProduct = Product::create([
            'name' => 'Custom Keychain Favor',
            'base_price' => 50.00,
            'unit_price' => 50.00,
            'product_type' => 'Giveaway',
            'description' => 'Beautiful custom keychain giveaway',
            'lead_time' => '3-5 business days',
            'lead_time_days' => 5,
            'event_type' => 'wedding',
            'theme_style' => 'modern',
        ]);

        // Create the product-material relationship
        ProductMaterial::create([
            'product_id' => $giveawayProduct->id,
            'material_id' => $keychainMaterial->material_id,
            'item' => 'giveaway',
            'type' => 'giveaway',
            'qty' => 1,
        ]);

        echo "Created test data:\n";
        echo "- Material: {$keychainMaterial->material_name}\n";
        echo "- Material: {$candleMaterial->material_name}\n";
        echo "- Giveaway Product: {$giveawayProduct->name}\n";
        echo "- Product-Material relationship created\n";
    }
}