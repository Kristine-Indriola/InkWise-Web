<?php

// Check image layer details
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
            foreach ($layers as $layer) {
                if ($layer['type'] === 'image') {
                    echo "Image layer details: " . json_encode($layer, JSON_PRETTY_PRINT) . "\n";
                    break;
                }
            }
            break;
        }
    }
}