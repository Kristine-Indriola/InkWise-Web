<?php
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

$templateId = (int) ($_SERVER['argv'][1] ?? 75);
$relativePath = $_SERVER['argv'][2] ?? "templates/assets/template_{$templateId}_bg.jpg";

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

$content = $imageNode['content'];
if (!is_string($content) || strpos($content, 'base64,') === false) {
    fwrite(STDERR, "Image content is not a data URI\n");
    exit(1);
}

[$meta, $data] = explode(',', $content, 2);
$binary = base64_decode($data, true);
if ($binary === false) {
    fwrite(STDERR, "Failed to decode base64 data\n");
    exit(1);
}

$fullPath = storage_path('app/public/' . ltrim($relativePath, '/'));
$dir = dirname($fullPath);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

file_put_contents($fullPath, $binary);

echo "Saved image to {$fullPath}\n";
