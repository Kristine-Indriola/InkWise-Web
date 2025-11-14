<?php
// Test the fixed template creation page
echo "Testing fixed template creation page...\n";

// Test 1: Check if page loads without errors
$url = 'http://127.0.0.1:8000/staff/templates/create?type=invitation';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "❌ HTTP Error: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
    exit(1);
}

// Test 2: Check for key elements
$checks = [
    'Create New Invitation Template' => strpos($response, 'Create New Invitation Template') !== false,
    'Figma import button' => strpos($response, 'btn-method-figma') !== false,
    'Manual upload button' => strpos($response, 'btn-method-manual') !== false,
    'Front preview container' => strpos($response, 'id="front-preview"') !== false,
    'Back preview container' => strpos($response, 'id="back-preview"') !== false,
    'Figma front preview' => strpos($response, 'id="figma-front-preview"') !== false,
    'Figma back preview' => strpos($response, 'id="figma-back-preview"') !== false,
    'Analyze front button' => strpos($response, 'analyze-figma-front-btn') !== false,
    'Analyze back button' => strpos($response, 'analyze-figma-back-btn') !== false,
    'Front SVG content field' => strpos($response, 'id="front_svg_content"') !== false,
    'Back SVG content field' => strpos($response, 'id="back_svg_content"') !== false,
    'analyzeFigmaUrl function' => strpos($response, 'function analyzeFigmaUrl') !== false,
    'toggleImportMethod function' => strpos($response, 'function toggleImportMethod') !== false,
];

$allGood = true;
foreach ($checks as $check => $result) {
    if ($result) {
        echo "✅ $check: Found\n";
    } else {
        echo "❌ $check: Missing\n";
        $allGood = false;
    }
}

// Test 3: Check for JavaScript syntax errors
$jsErrors = [];
if (strpos($response, 'DOMContentLoaded') === false) {
    $jsErrors[] = 'DOMContentLoaded event listener missing';
}
if (strpos($response, 'addEventListener(\'click\'') === false) {
    $jsErrors[] = 'Import method button event listeners missing';
}
if (strpos($response, 'getCsrfToken()') === false) {
    $jsErrors[] = 'CSRF token function missing';
}

if (!empty($jsErrors)) {
    echo "\n⚠️  JavaScript issues found:\n";
    foreach ($jsErrors as $error) {
        echo "   - $error\n";
    }
    $allGood = false;
}

if ($allGood) {
    echo "\n🎉 All checks passed! Template creation page is fixed.\n";
    echo "\nNext steps:\n";
    echo "1. Login to the system at http://127.0.0.1:8000/login\n";
    echo "2. Navigate to http://127.0.0.1:8000/staff/templates/create?type=invitation\n";
    echo "3. Click 'Import from Figma' button\n";
    echo "4. Enter your Figma URL and click 'Analyze Front Design'\n";
    echo "5. Select a frame and click 'Import Selected Front Frame'\n";
} else {
    echo "\n⚠️  Some issues remain. Please check the errors above.\n";
}
?>