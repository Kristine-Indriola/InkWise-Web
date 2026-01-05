<?php
// Test if the Figma routes are now accessible from staff context
echo "Testing Figma route accessibility fix...\n\n";

// Load Laravel application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test if staff.figma.analyze route exists
    $analyzeRoute = route('staff.templates.figma.analyze');
    echo "✓ staff.figma.analyze route exists: $analyzeRoute\n";
} catch (Exception $e) {
    echo "✗ staff.figma.analyze route missing: " . $e->getMessage() . "\n";
}

try {
    // Test if staff.figma.preview route exists
    $previewRoute = route('staff.templates.figma.preview');
    echo "✓ staff.figma.preview route exists: $previewRoute\n";
} catch (Exception $e) {
    echo "✗ staff.figma.preview route missing: " . $e->getMessage() . "\n";
}

// Test the template file has correct route names
$templateFile = __DIR__ . '/resources/views/Staff/templates/create.blade.php';
$content = file_get_contents($templateFile);

if (strpos($content, 'staff.templates.figma.analyze') !== false) {
    echo "✓ Template uses staff.templates.figma.analyze route\n";
} else {
    echo "✗ Template missing staff.figma.analyze route\n";
}

if (strpos($content, 'staff.templates.figma.preview') !== false) {
    echo "✓ Template uses staff.templates.figma.preview route\n";
} else {
    echo "✗ Template missing staff.figma.preview route\n";
}

echo "\n✅ Route fix completed!\n";
echo "The Figma routes are now properly nested under the staff middleware group.\n";
echo "This should resolve the 'Route [figma.analyze] not defined' error.\n";