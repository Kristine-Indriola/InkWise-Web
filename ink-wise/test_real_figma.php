<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SvgAutoParser;

$svgFile = 'storage/app/public/templates/figma_front_6904f07897bf6.svg';
if (file_exists($svgFile)) {
    $parser = new SvgAutoParser();
    $content = file_get_contents($svgFile);
    $result = $parser->processFigmaImportedSvg($content);
    
    echo "Real Figma SVG processing results:\n";
    echo "- Changeable images created: " . count($result['changeable_images']) . "\n";
    echo "- Text elements found: " . count($result['text_elements']) . "\n";
    
    if (count($result['changeable_images']) > 0) {
        echo "\n🎉 Success! Real Figma SVG has been enhanced with changeable images!\n";
        foreach ($result['changeable_images'] as $i => $img) {
            echo "  Image " . ($i + 1) . ": {$img['width']}x{$img['height']} at ({$img['x']}, {$img['y']})\n";
        }
    } else {
        echo "\n⚠️ No changeable images created from real Figma SVG.\n";
        echo "This may mean the SVG doesn't have suitable rectangular shapes.\n";
    }
} else {
    echo "❌ Figma SVG file not found at: $svgFile\n";
}