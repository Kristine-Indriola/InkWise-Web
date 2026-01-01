<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Template;

echo "Checking templates...\n\n";

$templates = Template::all();

echo "Found " . $templates->count() . " templates:\n\n";

foreach ($templates as $template) {
    echo "ID: " . $template->id . "\n";
    echo "Name: " . $template->name . "\n";
    echo "Status: " . $template->status . "\n";
    echo "Event Type: " . ($template->event_type ?? 'N/A') . "\n";
    echo "---\n";
}

// Activate one template for testing
if ($templates->count() > 0) {
    $firstTemplate = $templates->first();
    $firstTemplate->update(['status' => 'active']);
    echo "\nActivated template: " . $firstTemplate->name . "\n";
}
