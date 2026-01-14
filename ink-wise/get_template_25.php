<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$kernel = app(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::find(25);

if ($t) {
    echo "Name: " . $t->name . PHP_EOL;
    echo "Preview: " . $t->preview . PHP_EOL;
    echo "SVG Path: " . $t->svg_path . PHP_EOL;
    
    $design = $t->design;
    if (is_array($design) && isset($design['pages'])) {
        echo "Pages: " . count($design['pages']) . PHP_EOL;
        foreach ($design['pages'] as $i => $page) {
            echo "Page $i nodes: " . (isset($page['nodes']) ? count($page['nodes']) : 0) . PHP_EOL;
        }
    }
    
    file_put_contents('template_25_design.json', json_encode($design, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Design saved to template_25_design.json" . PHP_EOL;
} else {
    echo "Template not found" . PHP_EOL;
}
