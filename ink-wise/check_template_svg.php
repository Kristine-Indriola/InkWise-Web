<?php

// Simple script to check template SVG generation
require_once 'vendor/autoload.php';

// Bootstrap Laravel minimally
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::latest()->first();

if ($template) {
    echo "Latest template: {$template->name}\n";
    echo "Has SVG path: " . ($template->svg_path ? 'YES' : 'NO') . "\n";
    echo "Has design data: " . ($template->design ? 'YES' : 'NO') . "\n";

    if ($template->svg_path) {
        echo "SVG path: {$template->svg_path}\n";

        // Check if it's a dummy SVG (contains the specific dummy dimensions)
        $svgContent = Storage::disk('public')->get($template->svg_path);
        if (strpos($svgContent, 'width="100" height="100"') !== false) {
            echo "⚠️  WARNING: This appears to be a dummy SVG (100x100 white rectangle)\n";
        } else {
            echo "✅ This appears to be generated SVG content\n";
        }
    }

    if ($template->design) {
        $design = is_array($template->design) ? $template->design : json_decode($template->design, true);
        echo "Design data loaded successfully\n";
        echo "Design is array: " . (is_array($design) ? 'YES' : 'NO') . "\n";
        if (is_array($design)) {
            echo "Has pages: " . (isset($design['pages']) ? 'YES' : 'NO') . "\n";
            if (isset($design['pages'])) {
                echo "Pages keys: " . implode(', ', array_keys($design['pages'])) . "\n";
                $firstPageKey = array_key_first($design['pages']);
                echo "First page key: {$firstPageKey}\n";
                echo "First page data keys: " . implode(', ', array_keys($design['pages'][$firstPageKey])) . "\n";
                if (isset($design['pages'][$firstPageKey]['layers'])) {
                    echo "Has layers in first page: YES\n";
                    $layers = $design['pages'][$firstPageKey]['layers'];
                } elseif (isset($design['pages'][$firstPageKey]['nodes'])) {
                    echo "Has nodes in first page: YES\n";
                    $layers = $design['pages'][$firstPageKey]['nodes'];
                } else {
                    echo "Has layers or nodes in first page: NO\n";
                    $layers = [];
                }
            } else {
                $layers = [];
            }
        } else {
            $layers = [];
        }

        // Check for layers
        echo "Layers found: " . count($layers) . "\n";
        $textCount = 0;
        $imageCount = 0;
        foreach ($layers as $layerId => $layer) {
            echo "Processing layer {$layerId}: type=" . ($layer['type'] ?? 'unknown') . ", visible=" . ($layer['visible'] ?? 'unknown') . "\n";
            if (isset($layer['visible']) && $layer['visible']) {
                if ($layer['type'] === 'text') {
                    $textCount++;
                } elseif ($layer['type'] === 'image') {
                    $imageCount++;
                }
            }
        }
        echo "\nFront design elements: {$textCount} text, {$imageCount} images\n";

        // Now try to generate SVG
        $controller = new \App\Http\Controllers\Admin\TemplateController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('generateSvgFromTemplate');
        $method->setAccessible(true);
        $generatedSvg = $method->invoke($controller, $template->id);
        if ($generatedSvg) {
            echo "SVG generated successfully!\n";
            // Save it to a test file
            $testPath = 'templates/svg/test_generated.svg';
            Storage::disk('public')->put($testPath, base64_decode(str_replace('data:image/svg+xml;base64,', '', $generatedSvg)));
            echo "Saved to: storage/app/public/{$testPath}\n";

            // Update the template's svg_path
            $template->svg_path = $testPath;
            $template->save();
            echo "Updated template SVG path to: {$testPath}\n";
        } else {
            echo "SVG generation failed or returned null\n";
        }
    }
} else {
    echo "No templates found in database.\n";
}