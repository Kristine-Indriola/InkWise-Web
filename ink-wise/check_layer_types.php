<?php

// Check layer types in template 19 design data
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::find(19);
$jsonPath = $template->metadata['json_path'] ?? null;
if ($jsonPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($jsonPath)) {
    $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($jsonPath);
    $designData = json_decode($contents, true);

    if (isset($designData['pages'])) {
        foreach ($designData['pages'] as $pageKey => $page) {
            $layers = isset($page['layers']) ? $page['layers'] : (isset($page['nodes']) ? $page['nodes'] : []);
            echo "Page: {$pageKey}\n";
            foreach ($layers as $layer) {
                echo "  Layer: " . ($layer['id'] ?? 'no-id') . " - Type: " . ($layer['type'] ?? 'no-type') . " - Name: " . ($layer['name'] ?? $layer['content'] ?? 'unnamed') . "\n";
                if (isset($layer['type']) && $layer['type'] !== 'text' && $layer['type'] !== 'image') {
                    echo "    Non-text/image layer details: " . json_encode($layer) . "\n";
                }
            }
            break; // Just check first page
        }
    }
} else {
    echo "Asset file not found\n";
}