<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(132);
if ($template) {
    echo 'Template ID: ' . $template->id . PHP_EOL;
    echo 'Has back design: ' . ($template->has_back_design ? 'Yes' : 'No') . PHP_EOL;
    echo 'Front SVG path: ' . ($template->svg_path ?: 'null') . PHP_EOL;
    echo 'Back SVG path: ' . ($template->back_svg_path ?: 'null') . PHP_EOL;
    echo 'Front image: ' . ($template->front_image ?: 'null') . PHP_EOL;
    echo 'Back image: ' . ($template->back_image ?: 'null') . PHP_EOL;
} else {
    echo 'Template not found';
}