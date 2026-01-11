<?php

// Check template metadata
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::find(19);

if ($template) {
    echo "Template 19 metadata:\n";
    print_r($template->metadata);

    echo "\nChecking for json_path...\n";
    $metadata = $template->metadata ?? [];
    $jsonPath = $metadata['json_path'] ?? null;
    echo "json_path: " . ($jsonPath ?: 'NOT SET') . "\n";

    if ($jsonPath && Storage::disk('public')->exists($jsonPath)) {
        echo "JSON file exists at: $jsonPath\n";
    } else {
        echo "JSON file does not exist\n";
    }
}