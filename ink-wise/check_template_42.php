<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Template;
use Illuminate\Support\Facades\Storage;

// Find template 42
$template = Template::find(42);
if (!$template) {
    die("Template 42 not found\n");
}

echo "Template ID: {$template->id}\n";
echo "Name: {$template->name}\n\n";

echo "=== Image fields on template ===\n";
echo "preview_front: " . ($template->preview_front ?? 'NULL') . "\n";
echo "preview_back: " . ($template->preview_back ?? 'NULL') . "\n";
echo "front_image: " . ($template->front_image ?? 'NULL') . "\n";
echo "back_image: " . ($template->back_image ?? 'NULL') . "\n";
echo "preview: " . ($template->preview ?? 'NULL') . "\n";
echo "image: " . ($template->image ?? 'NULL') . "\n";

echo "\n=== Metadata ===\n";
$metadata = $template->metadata;
if (is_string($metadata)) {
    $metadata = json_decode($metadata, true) ?: [];
}
echo "json_path: " . ($metadata['json_path'] ?? 'NULL') . "\n";
echo "svg_path: " . ($metadata['svg_path'] ?? 'NULL') . "\n";

echo "\n=== Storage check ===\n";
$svgPath = $metadata['svg_path'] ?? null;
if ($svgPath) {
    echo "svg_path exists in storage: " . (Storage::disk('public')->exists($svgPath) ? 'YES' : 'NO') . "\n";
}
$jsonPath = $metadata['json_path'] ?? null;
if ($jsonPath) {
    echo "json_path exists in storage: " . (Storage::disk('public')->exists($jsonPath) ? 'YES' : 'NO') . "\n";
}

echo "\n=== URL Resolution ===\n";
$previewFront = $template->preview_front;
if ($previewFront) {
    echo "preview_front path: {$previewFront}\n";
    echo "Storage::url(): " . Storage::url($previewFront) . "\n";
    echo "File exists: " . (Storage::disk('public')->exists($previewFront) ? 'YES' : 'NO') . "\n";
}
