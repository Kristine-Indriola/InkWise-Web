<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::find(25);

if ($t) {
    $meta = $t->metadata;
    if (isset($meta['json_path'])) {
        echo "JSON Path: " . $meta['json_path'] . PHP_EOL;
        
        $jsonPath = $meta['json_path'];
        $fullPath = storage_path('app/public/' . $jsonPath);
        
        if (file_exists($fullPath)) {
            echo "Found asset file at: " . $fullPath . PHP_EOL;
            $content = file_get_contents($fullPath);
            $data = json_decode($content, true);
            
            if (isset($data['pages'][0]['nodes'])) {
                echo "Number of nodes: " . count($data['pages'][0]['nodes']) . PHP_EOL;
                echo PHP_EOL . "=== ALL NODES ===" . PHP_EOL;
                foreach ($data['pages'][0]['nodes'] as $i => $node) {
                    echo "[$i] " . ($node['name'] ?? 'Unnamed') . " (type: " . ($node['type'] ?? 'unknown') . ")" . PHP_EOL;
                    if ($node['type'] === 'image') {
                        echo "    Image node found!" . PHP_EOL;
                        echo "    Frame: x=" . $node['frame']['x'] . ", y=" . $node['frame']['y'] . ", w=" . $node['frame']['width'] . ", h=" . $node['frame']['height'] . PHP_EOL;
                        if (isset($node['src'])) {
                            echo "    Has src: " . substr($node['src'], 0, 50) . "..." . PHP_EOL;
                        }
                        if (isset($node['content'])) {
                            echo "    Has content: " . substr($node['content'], 0, 50) . "..." . PHP_EOL;
                        }
                    }
                }
            }
        } else {
            echo "Asset file not found!" . PHP_EOL;
        }
    }
}
