<?php
// Simple test to check if template create page works
echo "Testing template creation page...\n";

// Make a curl request to the page
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

// Check for key elements in the response
$checks = [
    'Create New Invitation Template' => strpos($response, 'Create New Invitation Template') !== false,
    'figma_url_front input' => strpos($response, 'id="figma_url_front"') !== false,
    'figma_url_back input' => strpos($response, 'id="figma_url_back"') !== false,
    'analyzeFigmaUrl function' => strpos($response, 'analyzeFigmaUrl(') !== false,
    'front_svg_content hidden field' => strpos($response, 'id="front_svg_content"') !== false,
    'back_svg_content hidden field' => strpos($response, 'id="back_svg_content"') !== false,
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

if ($allGood) {
    echo "\n🎉 All checks passed! Template creation page is working correctly.\n";
} else {
    echo "\n⚠️  Some checks failed. Please review the issues above.\n";
}
?>