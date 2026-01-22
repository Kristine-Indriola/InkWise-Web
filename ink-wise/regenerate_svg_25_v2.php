<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get template 25
$template = App\Models\Template::find(25);

// Get the controller to use its method
$controller = new App\Http\Controllers\Admin\TemplateController();

// Use reflection to call the protected generateSvgFromTemplate method
// Pass the correct JSON path
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateSvgFromTemplate');
$method->setAccessible(true);

echo "Generating SVG for template 25 using asset file..." . PHP_EOL;
$jsonPath = 'templates/assets/template_e7643598-1730-4da5-bf78-6e65b0758a2e.json';
$svgDataUrl = $method->invoke($controller, 25, $jsonPath);

if ($svgDataUrl) {
    // Extract the base64 content
    if (strpos($svgDataUrl, 'data:image/svg+xml;base64,') === 0) {
        $base64 = substr($svgDataUrl, strlen('data:image/svg+xml;base64,'));
        $svgContent = base64_decode($base64);
        
        // Save to the actual public storage location
        $svgPath = public_path('storage/templates/front/svg/template_25.svg');
        file_put_contents($svgPath, $svgContent);
        
        echo "SVG generated successfully!" . PHP_EOL;
        echo "Saved to: " . $svgPath . PHP_EOL;
        echo PHP_EOL;
        echo "=== SVG CONTENT (formatted) ===" . PHP_EOL;
        
        // Format the SVG nicely
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($svgContent);
        $formatted = $dom->saveXML();
        
        echo $formatted . PHP_EOL;
        
        // Also save formatted version
        file_put_contents($svgPath, $formatted);
        echo PHP_EOL . "Formatted SVG saved!" . PHP_EOL;
    } else {
        echo "Invalid SVG data URL format" . PHP_EOL;
    }
} else {
    echo "Failed to generate SVG" . PHP_EOL;
}
