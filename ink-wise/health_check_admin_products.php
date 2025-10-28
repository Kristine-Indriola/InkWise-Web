<?php

/**
 * Admin Product System Health Check
 * Run this script periodically to monitor system health
 * Can be added to cron jobs or scheduled tasks
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Admin Product System Health Check ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$issues = [];
$warnings = [];

// Check 1: Database connectivity
echo "1. Database Connectivity...\n";
try {
    $productCount = App\Models\Product::count();
    echo "   ✓ Connected - Products: $productCount\n";
} catch (Exception $e) {
    $issues[] = "Database connection failed: " . $e->getMessage();
    echo "   ✗ Database connection failed\n";
}

// Check 2: Template availability
echo "\n2. Template Availability...\n";
try {
    $templateCount = App\Models\Template::count();
    echo "   - Total templates: $templateCount\n";

    if ($templateCount === 0) {
        $warnings[] = "No templates found in database";
    }

    $templateStats = App\Models\Template::selectRaw('product_type, COUNT(*) as count')
        ->groupBy('product_type')
        ->get();

    foreach ($templateStats as $stat) {
        echo "   - {$stat->product_type}: {$stat->count} templates\n";
        if ($stat->count === 0) {
            $warnings[] = "No {$stat->product_type} templates available";
        }
    }
} catch (Exception $e) {
    $issues[] = "Template check failed: " . $e->getMessage();
}

// Check 3: Route availability
echo "\n3. Route Availability...\n";
$criticalRoutes = [
    'admin.products.index',
    'admin.products.create.invitation',
    'admin.products.create.giveaway',
    'admin.products.create.envelope',
    'admin.products.store'
];

foreach ($criticalRoutes as $routeName) {
    try {
        $url = route($routeName);
        echo "   ✓ $routeName\n";
    } catch (Exception $e) {
        $issues[] = "Route '$routeName' not available: " . $e->getMessage();
        echo "   ✗ $routeName\n";
    }
}

// Check 4: Controller methods
echo "\n4. Controller Methods...\n";
try {
    $controller = new App\Http\Controllers\Admin\ProductController();
    $requiredMethods = [
        'index', 'createInvitation', 'createGiveaway', 'createEnvelope',
        'store', 'edit', 'view', 'destroy', 'getTemplateData'
    ];

    foreach ($requiredMethods as $method) {
        if (method_exists($controller, $method)) {
            echo "   ✓ $method\n";
        } else {
            $issues[] = "Controller method '$method' missing";
            echo "   ✗ $method\n";
        }
    }
} catch (Exception $e) {
    $issues[] = "Controller check failed: " . $e->getMessage();
}

// Check 5: View files existence
echo "\n5. View Files...\n";
$viewFiles = [
    'resources/views/admin/products/index.blade.php',
    'resources/views/admin/products/create-invitation.blade.php',
    'resources/views/admin/products/create-giveaways.blade.php',
    'resources/views/admin/products/create-envelope.blade.php',
    'resources/views/admin/products/edit.blade.php',
    'resources/views/admin/products/view.blade.php',
    'resources/views/admin/products/templates.blade.php'
];

foreach ($viewFiles as $viewFile) {
    if (file_exists($viewFile)) {
        echo "   ✓ " . basename($viewFile) . "\n";
    } else {
        $issues[] = "View file missing: $viewFile";
        echo "   ✗ " . basename($viewFile) . "\n";
    }
}

// Check 6: Model relationships
echo "\n6. Model Relationships...\n";
try {
    $product = App\Models\Product::first();
    if ($product) {
        $product->load('template');
        if ($product->template) {
            echo "   ✓ Product-Template relationship works\n";
        } else {
            $warnings[] = "Product-Template relationship may have issues";
            echo "   ⚠ Product-Template relationship may have issues\n";
        }
    } else {
        echo "   - No products to test relationships\n";
    }
} catch (Exception $e) {
    $issues[] = "Relationship check failed: " . $e->getMessage();
}

// Summary
echo "\n=== Health Check Summary ===\n";

if (empty($issues) && empty($warnings)) {
    echo "🎉 All systems operational!\n";
} else {
    if (!empty($issues)) {
        echo "❌ Critical Issues Found:\n";
        foreach ($issues as $issue) {
            echo "   - $issue\n";
        }
    }

    if (!empty($warnings)) {
        echo "\n⚠️  Warnings:\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
    }
}

echo "\nHealth check completed at " . date('Y-m-d H:i:s') . "\n";

// Exit with appropriate code for monitoring systems
if (!empty($issues)) {
    exit(1); // Critical issues
} elseif (!empty($warnings)) {
    exit(2); // Warnings only
} else {
    exit(0); // All good
}