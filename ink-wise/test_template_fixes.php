<?php
// Test script to verify the template creation page fixes
echo "Testing template creation page fixes...\n\n";

// Test 1: Check if the file exists and is readable
$file = __DIR__ . '/resources/views/Staff/templates/create.blade.php';
if (!file_exists($file)) {
    echo "ERROR: Template file not found at $file\n";
    exit(1);
}

$content = file_get_contents($file);
if (!$content) {
    echo "ERROR: Could not read template file\n";
    exit(1);
}

echo "✓ Template file is readable\n";

// Test 2: Check for JavaScript syntax errors (basic check)
$jsStart = strpos($content, '<script>');
$jsEnd = strrpos($content, '</script>');
if ($jsStart !== false && $jsEnd !== false) {
    $jsContent = substr($content, $jsStart, $jsEnd - $jsStart + 10);

    // Check for the fixed DOMContentLoaded structure
    if (strpos($jsContent, '});        // Figma integration functions') === false) {
        echo "✓ JavaScript syntax error appears to be fixed\n";
    } else {
        echo "✗ JavaScript syntax error still present\n";
    }

    // Check for import method button event listeners
    if (strpos($jsContent, 'importMethodButtons.forEach(button => {') !== false) {
        echo "✓ Import method button event listeners found\n";
    } else {
        echo "✗ Import method button event listeners missing\n";
    }

    // Check for analyzeFigmaUrl function
    if (strpos($jsContent, 'async function analyzeFigmaUrl(side)') !== false) {
        echo "✓ analyzeFigmaUrl function found\n";
    } else {
        echo "✗ analyzeFigmaUrl function missing\n";
    }

    // Check for importFrameForSide function
    if (strpos($jsContent, 'async function importFrameForSide(frame, side)') !== false) {
        echo "✓ importFrameForSide function found\n";
    } else {
        echo "✗ importFrameForSide function missing\n";
    }
} else {
    echo "✗ Could not find JavaScript content\n";
}

// Test 3: Check for required HTML elements
$requiredElements = [
    'btn-method-figma',
    'analyze-figma-front-btn',
    'analyze-figma-back-btn',
    'figma-front-preview',
    'figma-back-preview',
    'front_svg_content',
    'back_svg_content'
];

foreach ($requiredElements as $element) {
    if (strpos($content, $element) !== false) {
        echo "✓ Required element '$element' found\n";
    } else {
        echo "✗ Required element '$element' missing\n";
    }
}

// Test 4: Check for proper preview container structure
if (strpos($content, 'id="figma-front-preview"') !== false && strpos($content, 'id="figma-back-preview"') !== false) {
    echo "✓ Figma preview containers have correct IDs\n";
} else {
    echo "✗ Figma preview containers missing or incorrect IDs\n";
}

echo "\nTest completed. If all checks pass, the template creation page should now work correctly.\n";
echo "Next steps:\n";
echo "1. Login to the system as admin/staff\n";
echo "2. Navigate to http://127.0.0.1:8000/staff/templates/create?type=invitation\n";
echo "3. Click 'Import from Figma' button - it should now be clickable\n";
echo "4. Enter a Figma URL and click 'Analyze Front Design'\n";
echo "5. Select a frame and click 'Import Selected Front Frame'\n";
echo "6. The SVG should appear in the front preview container\n";