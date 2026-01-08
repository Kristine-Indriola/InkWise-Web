<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Template;

echo "=== Template Preview Check ===\n\n";

// Get first 5 templates
$templates = Template::take(5)->get();

foreach ($templates as $template) {
    echo "Template ID: {$template->id}\n";
    echo "Name: {$template->name}\n";
    echo "Front Image: " . ($template->front_image ?? 'null') . "\n";
    echo "Preview: " . ($template->preview ?? 'null') . "\n";
    echo "SVG Path: " . ($template->svg_path ?? 'null') . "\n";
    
    if ($template->metadata) {
        echo "Metadata Previews: ";
        if (is_array($template->metadata) && isset($template->metadata['previews'])) {
            print_r($template->metadata['previews']);
        } else {
            echo "none\n";
        }
    }
    
    echo "---\n\n";
}
