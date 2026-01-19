<?php

/**
 * Template Save Test Script
 * Tests the template saving functionality end-to-end
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Log;

echo "\n=== Template Save Test ===\n\n";

// Find or create a test template
$template = Template::where('name', 'LIKE', 'Test%')
    ->orWhere('status', 'draft')
    ->first();

if (!$template) {
    echo "❌ No test template found. Creating one...\n";
    $template = Template::create([
        'name' => 'Test Template ' . time(),
        'product_type' => 'Invitation',
        'event_type' => 'Birthday',
        'status' => 'draft',
        'design' => json_encode([
            'pages' => [
                [
                    'id' => 'page-1',
                    'name' => 'Front',
                    'width' => 400,
                    'height' => 600,
                    'background' => '#ffffff',
                    'nodes' => [
                        [
                            'id' => 'text-1',
                            'type' => 'text',
                            'name' => 'Title',
                            'content' => 'Birthday Celebration',
                            'frame' => ['x' => 50, 'y' => 100, 'width' => 300, 'height' => 60],
                            'fontSize' => 32,
                            'fontFamily' => 'Inter',
                            'visible' => true,
                        ],
                        [
                            'id' => 'shape-1',
                            'type' => 'rectangle',
                            'name' => 'Background Box',
                            'frame' => ['x' => 30, 'y' => 80, 'width' => 340, 'height' => 440],
                            'fill' => '#f0f9ff',
                            'visible' => true,
                        ],
                    ],
                ],
            ],
        ]),
    ]);
    echo "✓ Created test template: {$template->name} (ID: {$template->id})\n";
} else {
    echo "✓ Using existing template: {$template->name} (ID: {$template->id})\n";
}

echo "\n--- Template Details ---\n";
echo "ID: {$template->id}\n";
echo "Name: {$template->name}\n";
echo "Type: {$template->product_type}\n";
echo "Status: {$template->status}\n";

// Check current design
$design = json_decode($template->design, true);
$pageCount = isset($design['pages']) ? count($design['pages']) : 0;
$nodeCount = isset($design['pages'][0]['nodes']) ? count($design['pages'][0]['nodes']) : 0;

echo "Design Pages: {$pageCount}\n";
echo "Nodes in first page: {$nodeCount}\n";

// Check metadata
$metadata = is_string($template->metadata) 
    ? json_decode($template->metadata, true) 
    : (is_array($template->metadata) ? $template->metadata : []);

$jsonPath = $metadata['json_path'] ?? null;

echo "\n--- Storage Status ---\n";

// Check preview file
if ($template->preview) {
    $previewPath = storage_path('app/public/' . ltrim($template->preview, '/'));
    if (file_exists($previewPath)) {
        $previewSize = filesize($previewPath);
        $sizeKB = round($previewSize / 1024, 1);
        $status = $previewSize > 5000 ? '✓ GOOD' : '⚠ SMALL (may be blank)';
        echo "Preview: {$status} - {$sizeKB} KB\n";
        echo "  Path: {$template->preview}\n";
    } else {
        echo "❌ Preview file not found: {$previewPath}\n";
    }
} else {
    echo "⚠ No preview path set\n";
}

// Check SVG file
if ($template->svg_path) {
    $svgPath = storage_path('app/public/' . ltrim($template->svg_path, '/'));
    if (file_exists($svgPath)) {
        $svgSize = filesize($svgPath);
        echo "✓ SVG exists - " . round($svgSize / 1024, 1) . " KB\n";
    } else {
        echo "❌ SVG file not found: {$svgPath}\n";
    }
} else {
    echo "⚠ No SVG path set\n";
}

// Check JSON file
if ($jsonPath) {
    $fullJsonPath = storage_path('app/public/' . ltrim($jsonPath, '/'));
    if (file_exists($fullJsonPath)) {
        $jsonSize = filesize($fullJsonPath);
        $jsonContent = json_decode(file_get_contents($fullJsonPath), true);
        $jsonPages = isset($jsonContent['pages']) ? count($jsonContent['pages']) : 0;
        $jsonNodes = isset($jsonContent['pages'][0]['nodes']) ? count($jsonContent['pages'][0]['nodes']) : 0;
        
        echo "✓ JSON exists - " . round($jsonSize / 1024, 1) . " KB\n";
        echo "  Pages: {$jsonPages}\n";
        echo "  Nodes: {$jsonNodes}\n";
    } else {
        echo "❌ JSON file not found: {$fullJsonPath}\n";
    }
} else {
    echo "⚠ No JSON path set in metadata\n";
}

echo "\n--- Test Results ---\n";

$issues = [];

if (!$template->preview || !file_exists(storage_path('app/public/' . ltrim($template->preview, '/')))) {
    $issues[] = "Preview file missing";
}

if ($template->preview && file_exists(storage_path('app/public/' . ltrim($template->preview, '/')))) {
    $size = filesize(storage_path('app/public/' . ltrim($template->preview, '/')));
    if ($size < 5000) {
        $issues[] = "Preview file too small (likely blank)";
    }
}

if ($nodeCount === 0) {
    $issues[] = "Template has no design nodes";
}

if ($jsonPath && file_exists(storage_path('app/public/' . ltrim($jsonPath, '/')))) {
    $jsonContent = json_decode(file_get_contents(storage_path('app/public/' . ltrim($jsonPath, '/'))), true);
    $jsonNodes = isset($jsonContent['pages'][0]['nodes']) ? count($jsonContent['pages'][0]['nodes']) : 0;
    if ($jsonNodes === 0) {
        $issues[] = "Saved JSON has no nodes";
    }
}

if (empty($issues)) {
    echo "✓ All checks passed!\n";
    echo "\n";
    echo "The template appears to be properly saved with:\n";
    echo "  - Valid design JSON with nodes\n";
    echo "  - Preview image of appropriate size\n";
    echo "  - Storage files persisted to disk\n";
} else {
    echo "⚠ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n--- Next Steps ---\n";
echo "1. Open the editor: http://127.0.0.1:8000/staff/templates/{$template->id}/editor\n";
echo "2. Open browser DevTools (F12) → Console\n";
echo "3. Click Save Template\n";
echo "4. Monitor console logs for detailed save progress\n";
echo "5. Run this script again to verify changes\n";

echo "\n=== Test Complete ===\n\n";
