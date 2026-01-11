<?php

// Check template 19 status
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::find(19);
if ($template) {
    echo 'Template 19 SVG path: ' . ($template->svg_path ?? 'null') . PHP_EOL;
    echo 'Template 19 metadata json_path: ' . ($template->metadata['json_path'] ?? 'null') . PHP_EOL;

    $jsonPath = $template->metadata['json_path'] ?? null;
    if ($jsonPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($jsonPath)) {
        echo 'Asset file exists: YES' . PHP_EOL;
    } else {
        echo 'Asset file exists: NO' . PHP_EOL;
    }
} else {
    echo 'Template 19 not found' . PHP_EOL;
}