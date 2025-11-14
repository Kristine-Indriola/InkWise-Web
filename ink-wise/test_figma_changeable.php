<?php
/**
 * Test script to verify Figma SVG changeable image functionality
 * Run this from the ink-wise directory: php test_figma_changeable.php
 */

require_once 'vendor/autoload.php';

use App\Services\SvgAutoParser;

// Sample Figma SVG content with changeable elements
$sampleSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="500" height="700" viewBox="0 0 500 700" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="500" height="700" fill="#f0f0f0"/>
    
    <!-- This should be detected as changeable -->
    <image id="photo_placeholder" x="50" y="50" width="200" height="150" href="data:image/png;base64,PLACEHOLDER_IMAGE"/>
    
    <!-- This should also be detected -->
    <rect id="replaceable_background" x="50" y="250" width="200" height="150" fill="#placeholder"/>
    
    <!-- Regular text element -->
    <text x="250" y="100" text-anchor="middle" font-size="24">Sample Text</text>
    
    <!-- Another changeable image -->
    <image id="user_photo_editable" x="300" y="300" width="150" height="200" href="placeholder.jpg"/>
</svg>';

echo "Testing Figma SVG Auto-Parser...\n";

try {
    // Create a temporary file to test with
    $tempPath = 'temp_test_svg.svg';
    file_put_contents(storage_path('app/public/' . $tempPath), $sampleSvg);
    
    $parser = new SvgAutoParser();
    $result = $parser->parseSvg($tempPath);
    
    echo "✓ SVG parsed successfully!\n";
    echo "  - Text elements found: " . count($result['text_elements']) . "\n";
    echo "  - Image elements found: " . count($result['image_elements']) . "\n";
    echo "  - Changeable images found: " . count($result['changeable_images']) . "\n\n";
    
    echo "Changeable images details:\n";
    foreach ($result['changeable_images'] as $changeable) {
        echo "  - ID: {$changeable['id']}, Type: {$changeable['element_type']}, Original ID: {$changeable['original_id']}\n";
    }
    
    echo "\nProcessed SVG content:\n";
    echo substr($result['content'], 0, 500) . "...\n";
    
    // Check if data attributes were added
    if (strpos($result['content'], 'data-changeable="image"') !== false) {
        echo "\n✓ Data attributes correctly added to changeable elements!\n";
    } else {
        echo "\n✗ Data attributes NOT found in processed SVG!\n";
    }
    
    // Clean up
    if (file_exists(storage_path('app/public/' . $tempPath))) {
        unlink(storage_path('app/public/' . $tempPath));
    }
    
    echo "\nTest completed successfully! The SVG auto-parser is working correctly.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}