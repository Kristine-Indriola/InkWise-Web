<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\SvgAutoParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "Processing SVG templates to add data-changeable attributes...\n";

try {
    // Get all SVG files in the templates directory
    $templateDir = 'templates';
    $files = Storage::disk('public')->files($templateDir);

    $svgFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'svg';
    });

    echo "Found " . count($svgFiles) . " SVG files to process.\n";

    $parser = new SvgAutoParser();

    foreach ($svgFiles as $filePath) {
        echo "Processing: $filePath\n";

        try {
            $parsedData = $parser->parseSvg($filePath);

            if ($parsedData['processed_path'] !== $filePath) {
                echo "  ✓ Processed and saved as: " . $parsedData['processed_path'] . "\n";
                echo "  - Text elements: " . count($parsedData['text_elements']) . "\n";
                echo "  - Changeable images: " . count($parsedData['changeable_images']) . "\n";
            } else {
                echo "  - No changes needed\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error processing $filePath: " . $e->getMessage() . "\n";
        }
    }

    echo "\nProcessing complete!\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}