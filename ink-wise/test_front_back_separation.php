<?php

/**
 * Test script to verify front/back template separation functionality
 * Run this script to test the autosave and design loading separation
 */

require_once 'vendor/autoload.php';

use App\Models\Template;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\TemplateController;

// Test data
$templateId = 1; // Replace with actual template ID
$testFrontDesign = [
    'pages' => [
        [
            'id' => 'page-1',
            'width' => 400,
            'height' => 600,
            'nodes' => [
                [
                    'id' => 'text-1',
                    'type' => 'text',
                    'content' => 'Front Side Text',
                    'frame' => ['x' => 50, 'y' => 50, 'width' => 300, 'height' => 50]
                ]
            ]
        ]
    ]
];

$testBackDesign = [
    'pages' => [
        [
            'id' => 'page-2',
            'width' => 400,
            'height' => 600,
            'nodes' => [
                [
                    'id' => 'text-2',
                    'type' => 'text',
                    'content' => 'Back Side Text',
                    'frame' => ['x' => 50, 'y' => 50, 'width' => 300, 'height' => 50]
                ]
            ]
        ]
    ]
];

echo "=== Testing Front/Back Template Separation ===\n\n";

// Test 1: Autosave front side
echo "Test 1: Autosaving front side...\n";
$controller = new TemplateController();
$request = new Request();
$request->merge([
    'design' => $testFrontDesign,
    'side' => 'front'
]);

try {
    $response = $controller->autosave($request, $templateId);
    $responseData = json_decode($response->getContent(), true);
    echo "✓ Front side autosave: " . ($responseData['success'] ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "✗ Front side autosave failed: " . $e->getMessage() . "\n";
}

// Test 2: Autosave back side
echo "\nTest 2: Autosaving back side...\n";
$request = new Request();
$request->merge([
    'design' => $testBackDesign,
    'side' => 'back'
]);

try {
    $response = $controller->autosave($request, $templateId);
    $responseData = json_decode($response->getContent(), true);
    echo "✓ Back side autosave: " . ($responseData['success'] ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "✗ Back side autosave failed: " . $e->getMessage() . "\n";
}

// Test 3: Load front design
echo "\nTest 3: Loading front design...\n";
$request = new Request();
$request->merge(['side' => 'front']);

try {
    $response = $controller->loadDesign($templateId, $request);
    $responseData = json_decode($response->getContent(), true);
    $hasFrontContent = isset($responseData['design']['pages'][0]['nodes'][0]['content']) &&
                      $responseData['design']['pages'][0]['nodes'][0]['content'] === 'Front Side Text';
    echo "✓ Front design loading: " . ($hasFrontContent ? 'SUCCESS' : 'FAILED') . "\n";
    echo "  - Side: " . ($responseData['side'] ?? 'unknown') . "\n";
} catch (Exception $e) {
    echo "✗ Front design loading failed: " . $e->getMessage() . "\n";
}

// Test 4: Load back design
echo "\nTest 4: Loading back design...\n";
$request = new Request();
$request->merge(['side' => 'back']);

try {
    $response = $controller->loadDesign($templateId, $request);
    $responseData = json_decode($response->getContent(), true);
    $hasBackContent = isset($responseData['design']['pages'][0]['nodes'][0]['content']) &&
                     $responseData['design']['pages'][0]['nodes'][0]['content'] === 'Back Side Text';
    echo "✓ Back design loading: " . ($hasBackContent ? 'SUCCESS' : 'FAILED') . "\n";
    echo "  - Side: " . ($responseData['side'] ?? 'unknown') . "\n";
} catch (Exception $e) {
    echo "✗ Back design loading failed: " . $e->getMessage() . "\n";
}

// Test 5: Verify template metadata
echo "\nTest 5: Checking template metadata...\n";
try {
    $template = Template::find($templateId);
    $metadata = $template->metadata ?? [];

    $hasFrontPath = isset($metadata['json_path']);
    $hasBackPath = isset($metadata['back_json_path']);

    echo "✓ Template metadata check:\n";
    echo "  - Has front JSON path: " . ($hasFrontPath ? 'YES' : 'NO') . "\n";
    echo "  - Has back JSON path: " . ($hasBackPath ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "✗ Template metadata check failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "If all tests show SUCCESS, the front/back separation is working correctly.\n";
echo "The autosave and design loading now respect the 'side' parameter.\n";

?>
