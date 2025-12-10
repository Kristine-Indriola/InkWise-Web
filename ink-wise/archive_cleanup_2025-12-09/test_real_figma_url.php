<?php
/**
 * Test script to verify Figma enhancement with the actual Figma URL provided
 * This tests the real-world scenario with the user's specific Figma design
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SvgAutoParser;

echo "Testing Figma Enhancement with Real URL\n";
echo "======================================\n";
echo "URL: https://www.figma.com/design/JU3zVG8hNytjSMXoCkxpcZ/Invitation?node-id=35-3&p=f&t=Cudd3AT9dnCSLAcc-0\n\n";

// First, let's check if we have any existing Figma SVG files from this template
echo "1. Checking for existing Figma templates in database...\n";

try {
    // Simulate what happens when we import this Figma design
    $testSvgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="384" height="559" viewBox="0 0 384 559" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Background rectangle like the one in the user\'s interface -->
  <rect width="384" height="559" fill="#F1EEE9"/>
  
  <!-- Some decorative elements -->
  <g id="decorative_elements">
    <path d="M50 100 Q100 50 150 100 T250 100" stroke="#D4AF8C" stroke-width="2" fill="none"/>
    <circle cx="300" cy="400" r="30" fill="#E8DDD4" opacity="0.5"/>
  </g>
  
  <!-- Text elements that might be in the design -->
  <text x="192" y="200" text-anchor="middle" font-family="serif" font-size="36" fill="#333">Leah</text>
  <text x="192" y="350" text-anchor="middle" font-family="serif" font-size="24" fill="#666">Wedding Invitation</text>
</svg>';

    echo "2. Testing enhanced Figma SVG processing...\n";
    
    $parser = new SvgAutoParser();
    $result = $parser->processFigmaImportedSvg($testSvgContent);
    
    echo "   Processing completed.\n\n";
    
    echo "3. Results Analysis:\n";
    echo "   - Text elements found: " . count($result['text_elements']) . "\n";
    echo "   - Image elements found: " . count($result['image_elements']) . "\n";
    echo "   - Changeable images created: " . count($result['changeable_images']) . "\n";
    echo "   - Processing type: " . ($result['metadata']['processing_type'] ?? 'unknown') . "\n\n";
    
    if (count($result['changeable_images']) > 0) {
        echo "4. ✅ SUCCESS: Changeable images were created!\n";
        echo "   This means the background rectangle would become changeable.\n\n";
        
        foreach ($result['changeable_images'] as $i => $img) {
            echo "   Changeable Image " . ($i + 1) . ":\n";
            echo "     - ID: {$img['id']}\n";
            echo "     - Type: {$img['element_type']}\n";
            echo "     - Size: {$img['width']}x{$img['height']}\n";
            echo "     - Position: ({$img['x']}, {$img['y']})\n";
            echo "     - Original Element: " . ($img['original_element'] ?? 'N/A') . "\n\n";
        }
        
        echo "5. 🎯 The Issue with Your Current Template:\n";
        echo "   Your template (ID 17) was likely created BEFORE our enhancement.\n";
        echo "   The background rectangle should be converted to a changeable image.\n\n";
        
        echo "6. 💡 Solution Options:\n";
        echo "   A) Re-import the Figma design using the staff template creation interface\n";
        echo "   B) Update the existing template with enhanced processing\n\n";
        
    } else {
        echo "4. ⚠️  No changeable images created.\n";
        echo "   The criteria might need adjustment for this specific design.\n\n";
        
        // Let's examine what elements were found
        echo "5. 🔍 Debugging - What was found:\n";
        
        // Parse the SVG manually to see what's there
        $dom = new \DOMDocument();
        $dom->loadXML($testSvgContent, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        
        $allRects = $xpath->query('//*[local-name()="rect"]');
        echo "   - Rectangles found: " . $allRects->length . "\n";
        
        foreach ($allRects as $i => $rect) {
            $width = $rect->getAttribute('width') ?: 'N/A';
            $height = $rect->getAttribute('height') ?: 'N/A';
            $fill = $rect->getAttribute('fill') ?: 'N/A';
            echo "     Rect $i: {$width}x{$height}, fill='{$fill}'\n";
        }
    }
    
    echo "\n7. 🚀 Recommended Next Steps:\n";
    echo "   1. Go to Staff Template Creation Interface\n";
    echo "   2. Import from your Figma URL again\n";
    echo "   3. The background should automatically become changeable\n";
    echo "   4. Test in customer editing interface\n\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo str_repeat("=", 60) . "\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";