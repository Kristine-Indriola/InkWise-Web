<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Template;

// Fix broken template image paths
echo "Fixing template image paths...\n\n";

// Get all available preview images
$availableImages = Storage::disk('public')->files('templates/preview');
echo "Found " . count($availableImages) . " images in storage\n\n";

$templates = Template::where('status', '!=', 'uploaded')->get();

$fixed = 0;
$skipped = 0;
$imageIndex = 0;

foreach ($templates as $template) {
    $needsFix = false;
    
    // Check if any image path exists
    $hasValidImage = false;
    foreach (['front_image', 'preview', 'preview_front'] as $field) {
        if ($template->$field && Storage::disk('public')->exists($template->$field)) {
            $hasValidImage = true;
            break;
        }
    }
    
    if (!$hasValidImage && !empty($availableImages)) {
        // Assign a random available image (cycling through them)
        $assignedImage = $availableImages[$imageIndex % count($availableImages)];
        $imageIndex++;
        
        echo "Template {$template->id}: {$template->name}\n";
        echo "  Assigning: {$assignedImage}\n";
        
        $template->front_image = $assignedImage;
        $template->preview = $assignedImage;
        $template->preview_front = $assignedImage;
        $template->save();
        
        $fixed++;
        echo "  ✓ Fixed\n\n";
    } else if ($hasValidImage) {
        // Sync all fields to use the valid image
        $validPath = null;
        foreach (['front_image', 'preview', 'preview_front'] as $field) {
            if ($template->$field && Storage::disk('public')->exists($template->$field)) {
                $validPath = $template->$field;
                break;
            }
        }
        
        if ($validPath) {
            $template->front_image = $validPath;
            $template->preview = $validPath;
            $template->preview_front = $validPath;
            $template->save();
            $skipped++;
        }
    } else {
        $skipped++;
    }
}

echo "\n";
echo "Summary:\n";
echo "  Fixed: {$fixed} templates\n";
echo "  Already valid: {$skipped} templates\n";
echo "\nDone!\n";
