<?php
/**
 * Debug test for Figma SVG processing - troubleshoot rectangle detection
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SvgAutoParser;

// Test SVG with obvious rectangles
$testSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="600" viewBox="0 0 400 600" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="400" height="600" fill="#ffffff"/>
  <rect id="photo_placeholder" x="50" y="100" width="300" height="200" fill="#f0f0f0"/>
  <rect id="large_rect" x="80" y="350" width="240" height="150" fill="#e8e8e8"/>  
  <text x="200" y="80">Test Text</text>
</svg>';

class DebugSvgAutoParser extends SvgAutoParser {
    public function debugProcessFigmaSvg($content) {
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);
        
        echo "=== Debug DOM Loading ===\n";
        echo "XML loaded successfully: " . ($loaded ? "YES" : "NO") . "\n";
        echo "Document element: " . ($dom->documentElement ? $dom->documentElement->nodeName : "NONE") . "\n";
        
        $xpath = new \DOMXPath($dom);
        
        echo "\n=== Debug Rectangle Detection ===\n";
        
        // Check what elements exist
        $allElements = $xpath->query('//*');
        echo "Total elements found: " . $allElements->length . "\n";
        
        for ($i = 0; $i < min(10, $allElements->length); $i++) {
            $element = $allElements->item($i);
            echo "  Element $i: " . $element->nodeName . "\n";
        }
        
        // Try different XPath queries for rectangles
        echo "\n=== Testing Different XPath Queries ===\n";
        
        $queries = [
            '//rect' => $xpath->query('//rect'),
            '//*[local-name()="rect"]' => $xpath->query('//*[local-name()="rect"]'),
            '//svg:rect' => null,
        ];
        
        // Register SVG namespace and try again
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $queries['//svg:rect'] = $xpath->query('//svg:rect');
        
        foreach ($queries as $query => $result) {
            if ($result !== null) {
                echo "Query '$query': " . $result->length . " results\n";
            }
        }
        
        // Use the working query
        $allRects = $xpath->query('//*[local-name()="rect"]');
        echo "\nUsing local-name query - Found " . $allRects->length . " rectangles:\n";
        
        foreach ($allRects as $i => $rect) {
            $id = $rect->getAttribute('id') ?: 'no-id';
            $width = floatval($rect->getAttribute('width') ?: 0);
            $height = floatval($rect->getAttribute('height') ?: 0);
            $area = $width * $height;
            $fill = $rect->getAttribute('fill') ?: 'no-fill';
            
            echo "  Rect $i: ID='$id', Size={$width}x{$height}, Area=$area, Fill='$fill'\n";
            
            // Test shouldConvert logic
            $shouldConvert = $this->testShouldConvert($rect);
            echo "    Should convert: " . ($shouldConvert ? "YES" : "NO") . "\n";
        }
        
        echo "\n=== Testing Conversion Process ===\n";
        $result = $this->processFigmaImportedSvg($content);
        echo "Changeable images created: " . count($result['changeable_images']) . "\n";
        
        return $result;
    }
    
    public function testShouldConvert($element) {
        $id = strtolower($element->getAttribute('id') ?: '');
        $className = strtolower($element->getAttribute('class') ?: '');
        
        echo "    Testing ID patterns: '$id'\n";
        
        // Check for naming patterns
        $imageIndicators = [
            'photo', 'image', 'picture', 'img', 'placeholder', 'changeable', 
            'editable', 'replaceable', 'avatar', 'profile', 'logo', 'banner',
            'frame', 'container', 'mask', 'clip', 'background', 'bg'
        ];
        
        foreach ($imageIndicators as $indicator) {
            if (strpos($id, $indicator) !== false) {
                echo "    ✓ Found indicator '$indicator' in ID\n";
                return true;
            }
        }
        
        // Check size
        if ($element->tagName === 'rect') {
            $width = floatval($element->getAttribute('width') ?: 0);
            $height = floatval($element->getAttribute('height') ?: 0);
            $area = $width * $height;
            
            echo "    Testing size: {$width}x{$height} = $area\n";
            
            if ($width >= 50 && $height >= 50 && $area >= 2500) {
                echo "    ✓ Size criteria met\n";
                return true;
            } else {
                echo "    ✗ Size too small (need ≥50x50 and ≥2500 area)\n";
            }
            
            $fill = strtolower($element->getAttribute('fill') ?: '');
            if (in_array($fill, ['#ffffff', '#f0f0f0', '#e0e0e0', '#cccccc', '#999999', '#e8e8e8'])) {
                echo "    ✓ Placeholder fill color detected: '$fill'\n";
                return true;
            } else {
                echo "    - Fill not recognized as placeholder: '$fill'\n";
            }
        }
        
        return false;
    }
}

echo "Debug Test for Figma SVG Processing\n";
echo "===================================\n\n";

$parser = new DebugSvgAutoParser();
$result = $parser->debugProcessFigmaSvg($testSvg);

echo "\nFinal result:\n";
echo "- Text elements: " . count($result['text_elements']) . "\n";
echo "- Changeable images: " . count($result['changeable_images']) . "\n";

if (count($result['changeable_images']) > 0) {
    echo "\nChangeable images created:\n";
    foreach ($result['changeable_images'] as $img) {
        echo "  - ID: {$img['id']}, Type: {$img['element_type']}, Size: {$img['width']}x{$img['height']}\n";
    }
}

echo "\nDone.\n";