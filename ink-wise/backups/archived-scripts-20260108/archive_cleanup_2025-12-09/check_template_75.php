<?php
require_once 'bootstrap/app.php';

$template = \App\Models\Template::find(75);
if ($template) {
    echo "Template ID: " . $template->id . PHP_EOL;
    echo "Name: " . $template->name . PHP_EOL;
    echo "SVG Path: " . $template->svg_path . PHP_EOL;
    echo "Preview: " . $template->preview . PHP_EOL;
    echo "Metadata: " . json_encode($template->metadata, JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "Template not found" . PHP_EOL;
}