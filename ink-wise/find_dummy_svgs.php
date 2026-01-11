<?php

// Script to find templates with dummy SVGs
require_once 'vendor/autoload.php';

// Bootstrap Laravel minimally
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templates = \App\Models\Template::whereNotNull('design')->whereNotNull('svg_path')->get();

$dummyTemplates = [];
foreach ($templates as $template) {
    try {
        $content = Storage::disk('public')->get($template->svg_path);
        if (strpos($content, 'width="100" height="100"') !== false) {
            $dummyTemplates[] = $template;
        }
    } catch (\Exception $e) {
        // File might not exist
        continue;
    }
}

echo "Templates with dummy SVGs: " . count($dummyTemplates) . "\n";
foreach ($dummyTemplates as $template) {
    echo "{$template->id}: {$template->name}\n";
}