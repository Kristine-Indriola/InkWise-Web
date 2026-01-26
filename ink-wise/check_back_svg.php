<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(132);
echo 'Template ID: ' . $template->id . PHP_EOL;
echo 'Has back design: ' . ($template->has_back_design ? 'YES' : 'NO') . PHP_EOL;
echo 'Has back side: ' . ($template->has_back_side ? 'YES' : 'NO') . PHP_EOL;
echo 'Back SVG path: ' . ($template->back_svg_path ?: 'null') . PHP_EOL;