<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

$templateId = (int) ($_SERVER['argv'][1] ?? 75);
$template = \App\Models\Template::find($templateId);
if (!$template) {
    fwrite(STDERR, "Template {$templateId} not found\n");
    exit(1);
}

$dir = storage_path('app/debug');
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$payload = [
    'id' => $template->id,
    'name' => $template->name,
    'svg_path' => $template->svg_path,
    'design' => $template->design,
    'metadata' => $template->metadata,
];

$file = $dir . DIRECTORY_SEPARATOR . "template_{$template->id}_design.json";
file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Exported template {$template->id} design to {$file}\n";
