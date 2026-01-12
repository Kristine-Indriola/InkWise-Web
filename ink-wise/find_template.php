<?php
// Find template by SVG path
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svgPath = 'templates/svg/template_1e5d4799-1e0c-410f-b04b-f7e4dc0e4f5e.svg';
$template = \App\Models\Template::where('svg_path', 'like', '%1e5d4799%')->first();

if (!$template) {
    // Try finding most recent template
    $template = \App\Models\Template::orderBy('updated_at', 'desc')->first();
    echo "Using most recent template: ID={$template->id}\n";
} else {
    echo "Found template by SVG path: ID={$template->id}\n";
}

$jsonPath = $template->metadata['json_path'] ?? null;
echo "JSON Path: $jsonPath\n";

if ($jsonPath && Illuminate\Support\Facades\Storage::disk('public')->exists($jsonPath)) {
    $json = json_decode(Illuminate\Support\Facades\Storage::disk('public')->get($jsonPath), true);
    $page = $json['pages'][0] ?? [];
    $nodes = $page['nodes'] ?? $page['layers'] ?? [];
    
    echo "\nLayers in design JSON (" . count($nodes) . "):\n";
    foreach ($nodes as $i => $node) {
        $type = $node['type'] ?? 'unknown';
        $name = $node['name'] ?? 'unnamed';
        $visible = isset($node['visible']) ? ($node['visible'] ? 'true' : 'false') : 'undefined';
        
        // For images, check if src/content exists
        $hasSource = '';
        if ($type === 'image') {
            $src = $node['src'] ?? $node['content'] ?? null;
            if ($src) {
                $srcLen = strlen($src);
                $hasSource = " [src length: $srcLen]";
                if (strpos($src, 'data:image') === 0) {
                    $hasSource .= " [data URL]";
                } elseif (strpos($src, 'http') === 0) {
                    $hasSource .= " [http URL]";
                } else {
                    $hasSource .= " [raw base64 or path]";
                }
            } else {
                $hasSource = " [NO SOURCE!]";
            }
        }
        
        echo "  $i. $type - $name (visible: $visible)$hasSource\n";
    }
} else {
    echo "JSON file not found at: $jsonPath\n";
}
