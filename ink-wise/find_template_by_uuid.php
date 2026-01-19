<?php

// Find template by UUID or check asset file usage
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$uuid = '43a99e78-8d69-434a-b38d-1a946c7e440b';

echo "Looking for template with UUID: $uuid\n";

// Check if any template has this UUID in svg_path
$templates = \App\Models\Template::all();
foreach ($templates as $template) {
    if (strpos($template->svg_path, $uuid) !== false) {
        echo "Found template: {$template->id} - {$template->name}\n";
        echo "SVG path: {$template->svg_path}\n";
        echo "Has design data: " . ($template->design ? 'YES' : 'NO') . "\n";
        break;
    }
}

// Check the asset file
$assetPath = 'templates/assets/template_' . $uuid . '.json';
if (Storage::disk('public')->exists($assetPath)) {
    echo "\nAsset file exists: $assetPath\n";
    $assetData = json_decode(Storage::disk('public')->get($assetPath), true);
    echo "Asset has " . count($assetData['pages'][0]['nodes'] ?? []) . " nodes\n";

    // Check if any template's design data matches this asset
    foreach ($templates as $template) {
        if ($template->design) {
            $design = is_array($template->design) ? $template->design : json_decode($template->design, true);
            if (isset($design['pages']) && isset($assetData['pages'])) {
                // Compare some key properties
                $templatePage = $design['pages'][0] ?? $design['pages']['page-1'] ?? null;
                $assetPage = $assetData['pages'][0] ?? null;

    if ($templatePage && $assetPage && $templatePage['width'] == $assetPage['width']) {
        echo "Template {$template->id} matches asset dimensions\n";
        echo "Template has " . count($templatePage['nodes'] ?? $templatePage['layers'] ?? []) . " nodes/layers\n";
        echo "Template SVG: {$template->svg_path}\n";

        // Check if SVG is dummy
        if ($template->svg_path) {
            try {
                $svgContent = Storage::disk('public')->get($template->svg_path);
                if (strpos($svgContent, 'width="100" height="100"') !== false) {
                    echo "Template has DUMMY SVG\n";
                } else {
                    echo "Template has proper SVG\n";
                }
            } catch (\Exception $e) {
                echo "SVG file error: " . $e->getMessage() . "\n";
            }
        }
        echo "---\n";
    }
            }
        }
    }
} else {
    echo "Asset file does not exist\n";
}