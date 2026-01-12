<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$svgPath = 'templates/svg/template_3e937f8b-e266-4729-b3c9-89c7417c5993.svg';
$template = \App\Models\Template::where('svg_path', $svgPath)->first();
if (!$template) {
    echo "not found for svg_path {$svgPath}\n";
    exit;
}
$meta = $template->metadata;
if (!is_string($meta)) {
    $meta = json_encode($meta);
}
$designLen = is_string($template->design) ? strlen($template->design) : 0;
echo "id={$template->id}\n";
echo "name={$template->name}\n";
echo "svg_path={$template->svg_path}\n";
echo "metadata={$meta}\n";
echo "design_len={$designLen}\n";
