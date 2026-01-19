<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get template 25
$template = App\Models\Template::find(25);

if (!$template) {
    echo "Template 25 not found!" . PHP_EOL;
    exit(1);
}

// Get the controller to use its method
$controller = new App\Http\Controllers\Admin\TemplateController();

// Use reflection to call the protected generateSvgFromTemplate method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateSvgFromTemplate');
$method->setAccessible(true);

echo "Generating SVG for template 25..." . PHP_EOL;
$svgDataUrl = $method->invoke($controller, 25);

if ($svgDataUrl) {
    // Extract the base64 content
    if (strpos($svgDataUrl, 'data:image/svg+xml;base64,') === 0) {
        $base64 = substr($svgDataUrl, strlen('data:image/svg+xml;base64,'));
        $svgContent = base64_decode($base64);
        
        // Save to the actual public storage location
        $svgPath = public_path('storage/templates/svg/template_25.svg');
        file_put_contents($svgPath, $svgContent);
        
        echo "SVG generated successfully!" . PHP_EOL;
        echo "Saved to: " . $svgPath . PHP_EOL;
        echo PHP_EOL;
        echo "=== SVG CONTENT ===" . PHP_EOL;
        echo $svgContent . PHP_EOL;
    } else {
        echo "Invalid SVG data URL format" . PHP_EOL;
    }
} else {
    echo "Failed to generate SVG" . PHP_EOL;
}
