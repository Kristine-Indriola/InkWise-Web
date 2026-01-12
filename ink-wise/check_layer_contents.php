<?php
$json = json_decode(file_get_contents('storage/app/public/templates/assets/template_e3d1a208-3200-4ac2-bb76-aab82c15dc12.json'), true);

foreach ($json['pages'][0]['nodes'] as $layer) {
    echo "Layer: " . $layer['name'] . " (id: " . $layer['id'] . ")\n";
    echo "  type: " . $layer['type'] . "\n";
    if (isset($layer['content'])) {
        $content = $layer['content'];
        if (strlen($content) > 100) {
            echo "  content: " . substr($content, 0, 100) . "... (length: " . strlen($content) . ")\n";
        } else {
            echo "  content: " . $content . "\n";
        }
    } else {
        echo "  content: NOT SET\n";
    }
    if (isset($layer['src'])) {
        $src = $layer['src'];
        if (strlen($src) > 100) {
            echo "  src: " . substr($src, 0, 100) . "... (length: " . strlen($src) . ")\n";
        } else {
            echo "  src: " . $src . "\n";
        }
    }
    if (isset($layer['metadata']['isImageFrame'])) {
        echo "  isImageFrame: true\n";
        if (isset($layer['metadata']['imageContent'])) {
            echo "  metadata.imageContent: SET (length: " . strlen($layer['metadata']['imageContent']) . ")\n";
        }
        if (isset($layer['metadata']['imageSrc'])) {
            echo "  metadata.imageSrc: " . substr($layer['metadata']['imageSrc'], 0, 100) . "\n";
        }
    }
    echo "\n";
}
