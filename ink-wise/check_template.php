<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(75);
if ($template) {
    echo "Template exists: " . $template->name . "\n";
    echo "Status: " . $template->status . "\n";
    echo "SVG Path: " . $template->svg_path . "\n";
    echo "Preview: " . $template->preview . "\n";
    echo "Design data: " . json_encode($template->design, JSON_PRETTY_PRINT) . "\n";
    echo "Metadata: " . json_encode($template->metadata, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Template not found\n";
}