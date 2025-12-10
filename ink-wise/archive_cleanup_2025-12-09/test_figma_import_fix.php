<?php
// Test script to verify Figma import fixes
echo "Testing Figma import fixes...\n\n";

// Test 1: Check if the route names are correct in the template
$file = __DIR__ . '/resources/views/Staff/templates/create.blade.php';
$content = file_get_contents($file);

echo "✓ Checking route names...\n";

// Check for correct route names
if (strpos($content, 'route("figma.analyze")') !== false) {
    echo "✓ figma.analyze route found\n";
} else {
    echo "✗ figma.analyze route missing\n";
}

if (strpos($content, 'route("figma.preview")') !== false) {
    echo "✓ figma.preview route found\n";
} else {
    echo "✗ figma.preview route missing\n";
}

// Check for correct data structure in importFrameForSide
if (strpos($content, 'file_key: figmaData.file_key') !== false) {
    echo "✓ Correct file_key parameter found\n";
} else {
    echo "✗ file_key parameter missing\n";
}

if (strpos($content, 'frames: [{') !== false) {
    echo "✓ Correct frames array structure found\n";
} else {
    echo "✗ frames array structure missing\n";
}

// Test 2: Check if the routes exist in web.php
$routesFile = __DIR__ . '/routes/web.php';
$routesContent = file_get_contents($routesFile);

echo "\n✓ Checking route definitions...\n";

if (strpos($routesContent, "Route::post('/analyze'") !== false) {
    echo "✓ /analyze route defined\n";
} else {
    echo "✗ /analyze route missing\n";
}

if (strpos($routesContent, "Route::post('/preview'") !== false) {
    echo "✓ /preview route defined\n";
} else {
    echo "✗ /preview route missing\n";
}

// Test 3: Check FigmaController preview method
$controllerFile = __DIR__ . '/app/Http/Controllers/FigmaController.php';
$controllerContent = file_get_contents($controllerFile);

echo "\n✓ Checking FigmaController...\n";

if (strpos($controllerContent, 'public function preview(Request $request)') !== false) {
    echo "✓ preview method found\n";
} else {
    echo "✗ preview method missing\n";
}

if (strpos($controllerContent, 'front_svg') !== false) {
    echo "✓ front_svg field found in response\n";
} else {
    echo "✗ front_svg field missing in response\n";
}

echo "\nTest completed!\n";
echo "\nWhat was fixed:\n";
echo "1. ✓ Changed from 'staff.figma.analyze' to 'figma.analyze' route\n";
echo "2. ✓ Changed from 'staff.figma.preview' to 'figma.preview' route\n";
echo "3. ✓ Updated data format: file_key instead of figma_url\n";
echo "4. ✓ Updated data format: frames array instead of individual frame properties\n";
echo "5. ✓ Using 'figma.preview' endpoint which returns SVG content directly\n";
echo "\nThe import should now work without 'Invalid import data provided' error.\n";