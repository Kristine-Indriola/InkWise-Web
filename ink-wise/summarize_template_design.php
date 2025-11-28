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

echo "Template #{$template->id}: {$template->name}" . PHP_EOL;

$pages = $template->design['pages'] ?? [];
foreach ($pages as $pageIndex => $page) {
    $pageName = $page['name'] ?? ('Page ' . ($pageIndex + 1));
    echo "Page {$pageIndex} ({$pageName})" . PHP_EOL;
    $nodes = $page['nodes'] ?? [];
    foreach ($nodes as $nodeIndex => $node) {
        $type = $node['type'] ?? 'unknown';
        $name = $node['name'] ?? ($node['id'] ?? "node-{$nodeIndex}");
        $frame = $node['frame'] ?? [];
        $content = $node['content'] ?? '';
        if (is_string($content) && strlen($content) > 120) {
            $content = substr($content, 0, 60) . '...' . substr($content, -20);
        }
        printf(
            "  [%02d] %-8s %-40s x=%s y=%s w=%s h=%s content=%s\n",
            $nodeIndex + 1,
            $type,
            $name,
            $frame['x'] ?? '-',
            $frame['y'] ?? '-',
            $frame['width'] ?? '-',
            $frame['height'] ?? '-',
            is_scalar($content) ? $content : json_encode($content)
        );
        if ($type === 'text') {
            printf(
                "       font=%s size=%s align=%s fill=%s text=%s\n",
                $node['fontFamily'] ?? 'default',
                $node['fontSize'] ?? 'n/a',
                $node['textAlign'] ?? 'left',
                $node['fill'] ?? '#000000',
                trim(preg_replace('/\s+/', ' ', $node['content'] ?? ''))
            );
        }
    }
}
