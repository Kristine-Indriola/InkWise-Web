<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$template = \App\Models\Template::find(19);
if (!$template) {
    echo "Template not found\n";
    exit(1);
}
$metadata = $template->metadata;
if (is_string($metadata)) {
    $metadata = json_decode($metadata, true);
}
// Decode design JSON for inspection
$design = $template->design;
if (is_string($design)) {
    $decodedDesign = json_decode($design, true);
    $designSnippet = substr($design, 0, 400);
} elseif (is_array($design)) {
    $decodedDesign = $design;
    $designSnippet = json_encode($design);
} else {
    $decodedDesign = null;
    $designSnippet = null;
}

$nodeSummaries = [];
if (is_array($decodedDesign['pages'] ?? null)) {
    foreach ($decodedDesign['pages'] as $page) {
        if (!empty($page['nodes']) && is_array($page['nodes'])) {
            foreach ($page['nodes'] as $node) {
                $nodeSummaries[] = [
                    'id' => $node['id'] ?? null,
                    'type' => $node['type'] ?? null,
                    'frame' => $node['frame'] ?? null,
                    'fill' => $node['fill'] ?? null,
                    'stroke' => $node['stroke'] ?? null,
                    'content' => $node['content'] ?? null,
                    'metadata' => $node['metadata'] ?? null,
                ];
            }
        }
    }
}
$result = [
    'svg_path' => $template->svg_path,
    'preview' => $template->preview,
    'metadata_keys' => is_array($metadata) ? array_keys($metadata) : gettype($metadata),
    'metadata' => $metadata,
    'design_snippet' => $designSnippet,
    'node_summaries' => $nodeSummaries,
];
print(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
