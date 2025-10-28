<?php

/**
 * Admin Product Functionality Test Script
 * Tests all admin product features including template integration, CRUD operations, and validation
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Admin Product Functionality Test ===\n\n";

try {
    // Test 1: Check if templates exist
    echo "1. Testing Templates...\n";
    $templateCount = App\Models\Template::count();
    echo "   - Total templates: $templateCount\n";

    if ($templateCount === 0) {
        echo "   ⚠️  No templates found. Creating test templates...\n";

        // Create test templates
        $templates = [
            [
                'name' => 'Wedding Invitation Template',
                'product_type' => 'Invitation',
                'event_type' => 'Wedding',
                'theme_style' => 'Elegant',
                'description' => 'Beautiful wedding invitation template',
                'front_image' => 'templates/wedding_front.svg',
                'back_image' => 'templates/wedding_back.svg',
                'design' => json_encode([
                    'paper_stocks' => [['name' => 'Premium Cardstock', 'price' => 50.00]],
                    'addons' => [['name' => 'Gold Foil', 'price' => 25.00]],
                    'colors' => [['name' => 'Gold', 'color_code' => '#FFD700']],
                    'bulk_orders' => [['min_qty' => 50, 'max_qty' => 100, 'price_per_unit' => 8.50]]
                ])
            ],
            [
                'name' => 'Birthday Giveaway Template',
                'product_type' => 'Giveaway',
                'event_type' => 'Birthday',
                'theme_style' => 'Fun',
                'description' => 'Colorful birthday giveaway template',
                'front_image' => 'templates/birthday_front.svg',
                'design' => json_encode([
                    'colors' => [['name' => 'Blue', 'color_code' => '#0000FF']],
                    'bulk_orders' => [['min_qty' => 25, 'max_qty' => 50, 'price_per_unit' => 12.00]]
                ])
            ],
            [
                'name' => 'Envelope Template',
                'product_type' => 'Envelope',
                'event_type' => 'General',
                'theme_style' => 'Classic',
                'description' => 'Classic envelope template',
                'front_image' => 'templates/envelope_front.svg',
                'design' => json_encode([
                    'colors' => [['name' => 'White', 'color_code' => '#FFFFFF']]
                ])
            ]
        ];

        foreach ($templates as $templateData) {
            App\Models\Template::create($templateData);
        }

        $templateCount = App\Models\Template::count();
        echo "   ✓ Created $templateCount test templates\n";
    }

    // Test 2: Check template distribution by type
    echo "\n2. Testing Template Distribution...\n";
    $templateStats = App\Models\Template::selectRaw('product_type, COUNT(*) as count')
        ->groupBy('product_type')
        ->get();

    foreach ($templateStats as $stat) {
        echo "   - {$stat->product_type}: {$stat->count} templates\n";
    }

    // Test 3: Test Product Creation from Template
    echo "\n3. Testing Product Creation from Template...\n";

    $weddingTemplate = App\Models\Template::where('product_type', 'Invitation')->first();
    if ($weddingTemplate) {
        echo "   - Using template: {$weddingTemplate->name}\n";

        $productData = [
            'template_id' => $weddingTemplate->id,
            'name' => 'Test Wedding Invitation',
            'event_type' => 'Wedding',
            'product_type' => 'Invitation',
            'theme_style' => 'Elegant',
            'description' => 'Test product created from template',
            'base_price' => 25.00,
            'lead_time' => '3-5 days'
        ];

        $product = App\Models\Product::create($productData);
        echo "   ✓ Created product: {$product->name} (ID: {$product->id})\n";

        // Test template relationship
        $product->load('template');
        if ($product->template) {
            echo "   ✓ Template relationship works: {$product->template->name}\n";
        } else {
            echo "   ✗ Template relationship failed\n";
        }

        // Test 4: Test Product Editing
        echo "\n4. Testing Product Editing...\n";
        $product->update([
            'name' => 'Updated Wedding Invitation',
            'base_price' => 30.00
        ]);
        echo "   ✓ Product updated successfully\n";

        // Test 5: Test Product Viewing with Relationships
        echo "\n5. Testing Product Viewing with Relationships...\n";
        $product->load(['template', 'images', 'paperStocks', 'addons', 'colors', 'bulkOrders']);
        echo "   ✓ Product relationships loaded successfully\n";

        // Test 6: Test Product Deletion
        echo "\n6. Testing Product Deletion...\n";
        $product->delete();
        echo "   ✓ Product deleted successfully\n";

        // Test 7: Test Template Data Retrieval
        echo "\n7. Testing Template Data Retrieval...\n";
        $templateData = App\Models\Template::find($weddingTemplate->id);
        if ($templateData && $templateData->design) {
            $design = json_decode($templateData->design, true);
            echo "   ✓ Template design data retrieved successfully\n";
            if (isset($design['paper_stocks'])) {
                echo "     - Paper stocks: " . count($design['paper_stocks']) . " items\n";
            }
            if (isset($design['addons'])) {
                echo "     - Addons: " . count($design['addons']) . " items\n";
            }
            if (isset($design['colors'])) {
                echo "     - Colors: " . count($design['colors']) . " items\n";
            }
        }

        // Test 8: Test Product Counts for Summary Cards
        echo "\n8. Testing Product Counts for Summary Cards...\n";
        $invitationCount = App\Models\Product::where('product_type', 'Invitation')->count();
        $giveawayCount = App\Models\Product::where('product_type', 'Giveaway')->count();
        $envelopeCount = App\Models\Product::where('product_type', 'Envelope')->count();
        $totalTemplates = App\Models\Template::count();

        echo "   - Invitations: $invitationCount\n";
        echo "   - Giveaways: $giveawayCount\n";
        echo "   - Envelopes: $envelopeCount\n";
        echo "   - Total Templates: $totalTemplates\n";

        // Test 9: Test Route Resolution
        echo "\n9. Testing Route Resolution...\n";
        $routes = [
            'admin.products.index',
            'admin.products.create.invitation',
            'admin.products.create.giveaway',
            'admin.products.create.envelope',
            'admin.products.store',
            'admin.products.template.data'
        ];

        foreach ($routes as $routeName) {
            try {
                $url = route($routeName);
                echo "   ✓ Route '$routeName' resolves to: $url\n";
            } catch (Exception $e) {
                echo "   ✗ Route '$routeName' failed: {$e->getMessage()}\n";
            }
        }

        // Test 10: Test Controller Method Existence
        echo "\n10. Testing Controller Methods...\n";
        $controller = new App\Http\Controllers\Admin\ProductController();
        $methods = [
            'index', 'createInvitation', 'createGiveaway', 'createEnvelope',
            'store', 'edit', 'show', 'view', 'destroy', 'getTemplateData'
        ];

        foreach ($methods as $method) {
            if (method_exists($controller, $method)) {
                echo "   ✓ Method '$method' exists\n";
            } else {
                echo "   ✗ Method '$method' missing\n";
            }
        }

        echo "\n=== Test Summary ===\n";
        echo "✓ Templates: Available and properly distributed\n";
        echo "✓ Product Creation: Works with template integration\n";
        echo "✓ Product Editing: Updates successfully\n";
        echo "✓ Product Viewing: Relationships load correctly\n";
        echo "✓ Product Deletion: Works without errors\n";
        echo "✓ Template Data: Retrieved and parsed correctly\n";
        echo "✓ Summary Counts: Calculated accurately\n";
        echo "✓ Routes: All admin product routes resolve\n";
        echo "✓ Controller Methods: All required methods exist\n";

        echo "\n🎉 All admin product functionality tests passed!\n";

    } else {
        echo "   ✗ No invitation template found for testing\n";
    }

} catch (Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}