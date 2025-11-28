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

$pages = $template->design['pages'] ?? [];
$page = $pages[0] ?? null;
$nodes = $page['nodes'] ?? [];
$imageNode = null;
foreach ($nodes as $node) {
    if (($node['type'] ?? null) === 'image' && isset($node['content'])) {
        $imageNode = $node;
        break;
    }
}

if (!$imageNode) {
    fwrite(STDERR, "No image node found\n");
    exit(1);
}

$dir = storage_path('app/debug');
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$file = $dir . DIRECTORY_SEPARATOR . "template_{$template->id}_image.txt";
file_put_contents($file, $imageNode['content']);

echo "Saved image content for template {$template->id} to {$file}\n";
