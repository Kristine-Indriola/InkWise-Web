<?php
// Running from public folder, so paths need adjustment
chdir(__DIR__ . '/..');
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cr = App\Models\CustomerReview::where('template_id', 43)->latest()->first();
if ($cr && $cr->design_svg) {
    // Save the raw SVG to a file for inspection
    file_put_contents('test_svg_output.svg', $cr->design_svg);
    
    // Show as HTML page with the SVG displayed inline
    echo '<!DOCTYPE html><html><head><title>SVG Test</title></head><body>';
    echo '<h1>SVG from database (id: ' . $cr->id . ', template_id: ' . $cr->template_id . ')</h1>';
    echo '<p>SVG length: ' . strlen($cr->design_svg) . ' bytes</p>';
    
    echo '<h2>Method 1: Direct SVG embed</h2>';
    echo '<div style="border: 2px solid red; display: inline-block;">';
    echo $cr->design_svg;
    echo '</div>';
    
    echo '<h2>Method 2: Base64 img src (same as review page)</h2>';
    $dataUrl = 'data:image/svg+xml;base64,' . base64_encode($cr->design_svg);
    echo '<div style="border: 2px solid blue; display: inline-block;">';
    echo '<img src="' . $dataUrl . '" style="max-width: 500px;">';
    echo '</div>';
    
    echo '<h2>Method 3: Object tag</h2>';
    echo '<div style="border: 2px solid green; display: inline-block;">';
    echo '<object type="image/svg+xml" data="' . $dataUrl . '" style="max-width: 500px;"></object>';
    echo '</div>';
    
    echo '</body></html>';
} else {
    echo 'No SVG found for template_id 43';
}
