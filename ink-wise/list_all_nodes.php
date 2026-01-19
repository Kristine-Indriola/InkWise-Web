<?php

$file = 'storage/app/public/templates/assets/template_e7643598-1730-4da5-bf78-6e65b0758a2e.json';
$content = file_get_contents($file);
$data = json_decode($content, true);

if (isset($data['pages'][0]['nodes'])) {
    $nodes = $data['pages'][0]['nodes'];
    echo "Total nodes: " . count($nodes) . PHP_EOL . PHP_EOL;
    
    foreach ($nodes as $i => $node) {
        echo "Node $i:" . PHP_EOL;
        echo "  ID: " . ($node['id'] ?? 'N/A') . PHP_EOL;
        echo "  Name: " . ($node['name'] ?? 'N/A') . PHP_EOL;
        echo "  Type: " . ($node['type'] ?? 'N/A') . PHP_EOL;
        echo "  Visible: " . (($node['visible'] ?? false) ? 'yes' : 'no') . PHP_EOL;
        
        if (($node['type'] ?? '') === 'image') {
            echo "  *** IMAGE FOUND ***" . PHP_EOL;
            echo "  Frame: x=" . ($node['frame']['x'] ?? 0) . ", y=" . ($node['frame']['y'] ?? 0) . 
                 ", w=" . ($node['frame']['width'] ?? 0) . ", h=" . ($node['frame']['height'] ?? 0) . PHP_EOL;
            
            if (isset($node['src'])) {
                $srcPreview = substr($node['src'], 0, 80);
                echo "  Has src: " . $srcPreview . "..." . PHP_EOL;
            } else {
                echo "  No src" . PHP_EOL;
            }
            
            if (isset($node['content'])) {
                $contentPreview = substr($node['content'], 0, 80);
                echo "  Has content: " . $contentPreview . "..." . PHP_EOL;
            } else {
                echo "  No content" . PHP_EOL;
            }
        }
        
        echo PHP_EOL;
    }
}
