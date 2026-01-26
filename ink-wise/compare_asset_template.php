<?php

// Compare asset file with template design data
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$uuid = '43a99e78-8d69-434a-b38d-1a946c7e440b';
$templateId = 19; // The one we just fixed

$template = \App\Models\Template::find($templateId);
$assetPath = 'templates/assets/template_' . $uuid . '.json';

if ($template && Storage::disk('public')->exists($assetPath)) {
    echo "Comparing Template $templateId with Asset File\n";
    echo "==========================================\n";

    $assetData = json_decode(Storage::disk('public')->get($assetPath), true);
    $templateDesign = is_array($template->design) ? $template->design : json_decode($template->design, true);

    echo "Asset file nodes: " . count($assetData['pages'][0]['nodes']) . "\n";
    echo "Template design nodes: " . count($templateDesign['pages'][0]['nodes'] ?? $templateDesign['pages']['page-1']['layers'] ?? []) . "\n";

    // Compare node contents
    $assetNodes = $assetData['pages'][0]['nodes'];
    $templateNodes = $templateDesign['pages'][0]['nodes'] ?? $templateDesign['pages']['page-1']['layers'] ?? [];

    echo "\nAsset nodes:\n";
    foreach ($assetNodes as $node) {
        echo "- {$node['id']}: {$node['type']} - {$node['name']}\n";
    }

    echo "\nTemplate nodes:\n";
    foreach ($templateNodes as $node) {
        echo "- {$node['id']}: {$node['type']} - " . ($node['name'] ?? $node['content'] ?? 'unnamed') . "\n";
    }

    // Check if we should use asset data for SVG generation
    echo "\nTesting SVG generation with asset data...\n";
    $controller = new \App\Http\Controllers\Admin\TemplateController();
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('generateSvgFromTemplate');
    $method->setAccessible(true);
    $generatedSvg = $method->invoke($controller, $template->id);

    if ($generatedSvg) {
        echo "SVG generated successfully from asset data!\n";
        $testPath = 'templates/front/svg/test_asset_generated.svg';
        Storage::disk('public')->put($testPath, base64_decode(str_replace('data:image/svg+xml;base64,', '', $generatedSvg)));
        echo "Saved to: storage/app/public/{$testPath}\n";
    } else {
        echo "SVG generation failed with asset data\n";
    }
}