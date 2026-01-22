<?php

// Check SVG file status
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/front/svg/template_19_regenerated.svg')) {
    $content = \Illuminate\Support\Facades\Storage::disk('public')->get('templates/front/svg/template_19_regenerated.svg');
    echo 'SVG file exists, size: ' . strlen($content) . ' bytes' . PHP_EOL;
    if (strpos($content, 'width="100" height="100"') !== false) {
        echo 'WARNING: Contains dummy SVG dimensions' . PHP_EOL;
    } else {
        echo 'Appears to be generated SVG' . PHP_EOL;
    }
    // Show first 200 chars
    echo 'First 200 chars: ' . substr($content, 0, 200) . '...' . PHP_EOL;
} else {
    echo 'SVG file does not exist' . PHP_EOL;
}