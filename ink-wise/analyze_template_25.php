<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = App\Models\Template::find(25);

// Load the asset JSON file
$jsonPath = storage_path('app/public/templates/assets/template_e7643598-1730-4da5-bf78-6e65b0758a2e.json');
$data = json_decode(file_get_contents($jsonPath), true);

echo "=== TEMPLATE 25 COMPLETE ANALYSIS ===" . PHP_EOL . PHP_EOL;

if (isset($data['pages'][0])) {
    $page = $data['pages'][0];
    echo "Page size: " . $page['width'] . " x " . $page['height'] . PHP_EOL;
    echo "Background: " . $page['background'] . PHP_EOL;
    echo PHP_EOL;
    
    $nodes = $page['nodes'] ?? [];
    echo "Total nodes: " . count($nodes) . PHP_EOL . PHP_EOL;
    
    foreach ($nodes as $i => $node) {
        echo "[$i] " . ($node['name'] ?? 'Unnamed') . " (type: " . ($node['type'] ?? 'unknown') . ")" . PHP_EOL;
        echo "    Visible: " . (($node['visible'] ?? false) ? 'YES' : 'NO') . PHP_EOL;
        echo "    Frame: x=" . ($node['frame']['x'] ?? 0) . 
             ", y=" . ($node['frame']['y'] ?? 0) . 
             ", w=" . ($node['frame']['width'] ?? 0) . 
             ", h=" . ($node['frame']['height'] ?? 0) . PHP_EOL;
        
        if ($node['type'] === 'text') {
            echo "    Content: \"" . ($node['content'] ?? '') . "\"" . PHP_EOL;
            echo "    Font: " . ($node['fontFamily'] ?? 'N/A') . ", " . ($node['fontSize'] ?? 0) . "px" . PHP_EOL;
        } elseif ($node['type'] === 'shape') {
            echo "    Fill: " . ($node['fill'] ?? 'N/A') . PHP_EOL;
            echo "    Border radius: " . ($node['borderRadius'] ?? 0) . PHP_EOL;
        } elseif ($node['type'] === 'image') {
            echo "    *** IMAGE NODE ***" . PHP_EOL;
            if (isset($node['src'])) {
                echo "    Has src (length: " . strlen($node['src']) . ")" . PHP_EOL;
            }
            if (isset($node['content'])) {
                echo "    Has content (length: " . strlen($node['content']) . ")" . PHP_EOL;
            }
            if (isset($node['metadata']['isImageFrame'])) {
                echo "    Is image frame: " . ($node['metadata']['isImageFrame'] ? 'yes' : 'no') . PHP_EOL;
            }
        }
        
        echo PHP_EOL;
    }
}

// Now check what the PNG preview path is
echo "=== PREVIEW FILES ===" . PHP_EOL;
echo "Preview: " . $template->preview . PHP_EOL;
echo "Preview front: " . $template->preview_front . PHP_EOL;
echo "Preview back: " . $template->preview_back . PHP_EOL;
