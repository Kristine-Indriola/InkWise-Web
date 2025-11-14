<?php
/**
 * Test script to verify enhanced Figma SVG processing functionality
 * This tests the new vector-to-image conversion features
 * Run this from the ink-wise directory: php test_enhanced_figma.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SvgAutoParser;

// Sample Figma SVG with vector shapes that should be converted to changeable images
$figmaSvgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="600" viewBox="0 0 400 600" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Background -->
  <rect width="400" height="600" fill="#ffffff"/>
  
  <!-- Large rectangular shape that should become changeable image -->
  <rect id="photo_placeholder" x="50" y="100" width="300" height="200" fill="#f0f0f0" stroke="#cccccc" stroke-width="2"/>
  
  <!-- Another potential image area -->
  <rect x="80" y="350" width="240" height="150" fill="#e8e8e8"/>
  
  <!-- Small decorative rectangle (should not be converted) -->
  <rect x="100" y="520" width="200" height="20" fill="#333333"/>
  
  <!-- Text elements -->
  <text x="200" y="80" text-anchor="middle" font-family="Arial" font-size="24" fill="#333333">Wedding Invitation</text>
  <text x="200" y="330" text-anchor="middle" font-family="Arial" font-size="16" fill="#666666">Your Photo Here</text>
  
  <!-- Path that could be a complex image frame -->
  <path id="frame_container" d="M 320 120 L 380 120 L 380 180 L 320 180 Z" fill="#f9f9f9" stroke="#999999"/>
</svg>';

echo "Testing Enhanced Figma SVG Auto-Parser...\n";
echo "========================================\n\n";

try {
    // Create parser instance
    $parser = new SvgAutoParser();
    
    echo "1. Processing Figma SVG with vector-to-image conversion...\n";
    
    // Process with Figma-specific enhancement
    $result = $parser->processFigmaImportedSvg($figmaSvgContent);
    
    echo "   ✓ Processing completed successfully\n\n";
    
    echo "2. Results Summary:\n";
    echo "   - Text elements found: " . count($result['text_elements']) . "\n";
    echo "   - Image elements found: " . count($result['image_elements']) . "\n";  
    echo "   - Changeable images created: " . count($result['changeable_images']) . "\n";
    echo "   - Processing type: " . ($result['metadata']['processing_type'] ?? 'unknown') . "\n";
    echo "   - Vector shapes converted: " . ($result['metadata']['vector_shapes_converted'] ?? 0) . "\n\n";
    
    if (count($result['changeable_images']) > 0) {
        echo "3. Changeable Images Details:\n";
        foreach ($result['changeable_images'] as $i => $image) {
            echo "   Image " . ($i + 1) . ":\n";
            echo "     - ID: " . $image['id'] . "\n";
            echo "     - Element Type: " . $image['element_type'] . "\n";
            echo "     - Original Element: " . ($image['original_element'] ?? 'N/A') . "\n";
            echo "     - Original ID: " . ($image['original_id'] ?? 'N/A') . "\n";
            echo "     - Position: (" . $image['x'] . ", " . $image['y'] . ")\n";
            echo "     - Size: " . $image['width'] . " x " . $image['height'] . "\n\n";
        }
    } else {
        echo "3. ⚠️ No changeable images were created!\n\n";
    }
    
    echo "4. Processed SVG Content Preview:\n";
    echo "   " . str_repeat("-", 50) . "\n";
    
    // Show key parts of the processed SVG
    $processedSvg = $result['content'];
    
    // Check for data-changeable attributes
    $changeableCount = substr_count($processedSvg, 'data-changeable="image"');
    echo "   - Found $changeableCount elements with data-changeable=\"image\"\n";
    
    // Check for converted image elements
    $imageElementCount = substr_count($processedSvg, '<image');
    echo "   - Found $imageElementCount <image> elements in processed SVG\n";
    
    // Show if SVG editor data was added
    if (strpos($processedSvg, 'data-svg-editor="true"') !== false) {
        echo "   - ✓ SVG editor attributes added\n";
    } else {
        echo "   - ✗ SVG editor attributes missing\n";
    }
    
    echo "   " . str_repeat("-", 50) . "\n\n";
    
    // Test comparison with standard processing
    echo "5. Comparison with Standard Processing:\n";
    $standardResult = $parser->processSvgContent($figmaSvgContent, false);
    
    echo "   Standard processing:\n";
    echo "     - Changeable images: " . count($standardResult['changeable_images']) . "\n";
    echo "   Enhanced Figma processing:\n"; 
    echo "     - Changeable images: " . count($result['changeable_images']) . "\n";
    
    $improvement = count($result['changeable_images']) - count($standardResult['changeable_images']);
    if ($improvement > 0) {
        echo "   ✓ Enhancement created $improvement additional changeable image(s)!\n\n";
    } elseif ($improvement === 0) {
        echo "   - No difference between processing methods\n\n";
    } else {
        echo "   ⚠️ Standard processing found more images\n\n";
    }
    
    echo "6. Generated Editor JavaScript:\n";
    if (method_exists($parser, 'generateEditorJs')) {
        $editorJs = $parser->generateEditorJs($result);
        $jsLines = explode("\n", $editorJs);
        echo "   - JavaScript generated: " . count($jsLines) . " lines\n";
        echo "   - Contains image upload handling: " . (strpos($editorJs, 'showImageUploadDialog') !== false ? "✓" : "✗") . "\n";
        echo "   - Contains text editing: " . (strpos($editorJs, 'showTextEditDialog') !== false ? "✓" : "✗") . "\n\n";
    } else {
        echo "   - generateEditorJs method not available\n\n";
    }
    
    echo "✅ Enhanced Figma SVG processing test completed successfully!\n";
    
    if (count($result['changeable_images']) > 0) {
        echo "\n🎉 SUCCESS: Vector shapes were converted to changeable images!\n";
        echo "   This means customers will be able to change images in the editing interface.\n";
    } else {
        echo "\n⚠️  NOTICE: No vector shapes were converted to changeable images.\n";
        echo "   You may need to adjust the conversion criteria in SvgAutoParser.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";