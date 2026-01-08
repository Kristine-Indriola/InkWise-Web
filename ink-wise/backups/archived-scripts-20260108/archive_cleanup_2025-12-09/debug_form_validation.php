<?php
// Debug script to test the template creation form validation
echo "Debugging template creation form...\n\n";

// Check if the template file exists and has the correct structure
$file = __DIR__ . '/resources/views/Staff/templates/create.blade.php';
$content = file_get_contents($file);

echo "1. Checking form validation logic...\n";

// Check for form validation that might be too strict
if (strpos($content, 'hasAnyDesign') !== false) {
    echo "✓ Form has design validation logic\n";
} else {
    echo "✗ Form missing design validation\n";
}

// Check for required attributes that might block submission
$requiredFields = ['front_image', 'name'];
foreach ($requiredFields as $field) {
    if (strpos($content, "id=\"$field\"") !== false) {
        // Check if it has required attribute
        $pattern = "/id=\"$field\"[^>]*required/";
        if (preg_match($pattern, $content)) {
            echo "⚠ Field '$field' is marked as required - this might block submission\n";
        } else {
            echo "✓ Field '$field' found without strict requirement\n";
        }
    }
}

// Check for JavaScript errors that might prevent submission
echo "\n2. Checking JavaScript structure...\n";

// Look for potential syntax issues
$jsStart = strpos($content, '<script>');
$jsEnd = strrpos($content, '</script>');
if ($jsStart !== false && $jsEnd !== false) {
    $jsContent = substr($content, $jsStart, $jsEnd - $jsStart);
    
    // Check for common issues
    if (substr_count($jsContent, '{') === substr_count($jsContent, '}')) {
        echo "✓ JavaScript braces are balanced\n";
    } else {
        echo "✗ JavaScript braces are unbalanced\n";
    }
    
    if (strpos($jsContent, 'preventDefault()') !== false) {
        echo "⚠ Form has preventDefault() calls that might block submission\n";
    }
} else {
    echo "✗ No JavaScript found\n";
}

echo "\n3. Potential issues:\n";
echo "- The front_image field might be required even when using Figma import\n";
echo "- Form validation might be too strict about having design content\n";
echo "- JavaScript might be preventing form submission\n";

echo "\nSuggested fixes:\n";
echo "1. Make front_image field not required when using Figma import\n";
echo "2. Adjust validation to allow submission with just template name\n";
echo "3. Add debug logging to see what's blocking submission\n";