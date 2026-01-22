<?php
/**
 * Regenerate SVG for template 42 to test the image frame fix
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\TemplateController;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

$templateId = 42;
$template = Template::find($templateId);

if (!$template) {
    die("Template not found\n");
}

echo "Template ID: {$template->id}\n";
echo "Name: {$template->name}\n";

// Get metadata to find json_path
$metadata = $template->metadata;
if (is_string($metadata)) {
    $metadata = json_decode($metadata, true) ?: [];
}
$jsonPath = $metadata['json_path'] ?? null;
echo "JSON Path: {$jsonPath}\n";
echo "Metadata keys: " . implode(', ', array_keys($metadata)) . "\n";

// Check for svg_path
$svgPath = $metadata['svg_path'] ?? null;
if (!$svgPath) {
    // Generate the expected SVG filename from the template's file
    $svgUuid = pathinfo($template->file ?? '', PATHINFO_FILENAME);
    if ($svgUuid) {
        $svgPath = "templates/front/svg/{$svgUuid}.svg";
    } else {
        // Use the JSON file UUID
        $jsonUuid = pathinfo($jsonPath, PATHINFO_FILENAME);
        $jsonUuid = str_replace('template_', '', $jsonUuid);
        $svgPath = "templates/front/svg/template_{$jsonUuid}.svg";
    }
    echo "Generated SVG Path: {$svgPath}\n";
}

// Use reflection to call the protected generateSvgFromTemplate method
$controller = new TemplateController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateSvgFromTemplate');
$method->setAccessible(true);

echo "\nGenerating SVG...\n";
$svgDataUrl = $method->invoke($controller, $templateId, $jsonPath);

if (!$svgDataUrl) {
    die("Failed to generate SVG\n");
}

// Save the SVG to the file
if ($svgPath) {
    // Decode the data URL
    $base64 = str_replace('data:image/svg+xml;base64,', '', $svgDataUrl);
    $svgContent = base64_decode($base64);
    
    Storage::disk('public')->put($svgPath, $svgContent);
    echo "SVG saved to: {$svgPath}\n";
    
    // Check if it contains <image> element
    if (strpos($svgContent, '<image') !== false) {
        echo "✓ SVG contains <image> element!\n";
        
        // Count image elements
        preg_match_all('/<image[^>]*>/', $svgContent, $matches);
        echo "  Found " . count($matches[0]) . " image element(s)\n";
    } else {
        echo "✗ SVG does NOT contain <image> element\n";
    }
    
    echo "\nFirst 2000 chars of SVG:\n";
    echo substr($svgContent, 0, 2000) . "...\n";
} else {
    echo "No SVG path in metadata\n";
}
