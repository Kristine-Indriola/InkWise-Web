<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::find(25);

if ($t) {
    echo "=== TEMPLATE 25 INFO ===" . PHP_EOL;
    echo "Name: " . $t->name . PHP_EOL;
    echo "Preview: " . $t->preview . PHP_EOL;
    echo "SVG Path: " . $t->svg_path . PHP_EOL;
    echo PHP_EOL;
    
    echo "=== METADATA ===" . PHP_EOL;
    print_r($t->metadata);
    echo PHP_EOL;
    
    echo "=== DESIGN DATA ===" . PHP_EOL;
    $design = $t->design;
    
    // Decode if it's a JSON string
    if (is_string($design)) {
        echo "Design is stored as JSON string, decoding..." . PHP_EOL;
        $design = json_decode($design, true);
    }
    
    if (is_array($design)) {
        echo "Design is array" . PHP_EOL;
        if (isset($design['pages'])) {
            echo "Pages count: " . count($design['pages']) . PHP_EOL;
            foreach ($design['pages'] as $i => $page) {
                echo "Page $i:" . PHP_EOL;
                echo "  Width: " . ($page['width'] ?? 'N/A') . PHP_EOL;
                echo "  Height: " . ($page['height'] ?? 'N/A') . PHP_EOL;
                echo "  Background: " . ($page['background'] ?? 'N/A') . PHP_EOL;
                $nodes = $page['nodes'] ?? $page['layers'] ?? [];
                echo "  Nodes/Layers: " . count($nodes) . PHP_EOL;
                foreach ($nodes as $j => $node) {
                    $type = $node['type'] ?? 'unknown';
                    $name = $node['name'] ?? 'Unnamed';
                    echo "    [$j] $name ($type)" . PHP_EOL;
                }
            }
        }
        
        // Save full design to JSON
        file_put_contents('template_25_full.json', json_encode($design, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo PHP_EOL . "Full design saved to template_25_full.json" . PHP_EOL;
    } else {
        echo "Design is not an array: " . gettype($design) . PHP_EOL;
    }
} else {
    echo "Template 25 not found" . PHP_EOL;
}
