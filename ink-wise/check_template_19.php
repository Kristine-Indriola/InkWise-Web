<?php

// Simple script to check template 19
require_once 'vendor/autoload.php';

// Bootstrap Laravel minimally
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::find(19);

if ($template) {
    echo "Template found: {$template->name}\n";
    echo "Has design data: " . ($template->design ? 'YES' : 'NO') . "\n";
    echo "SVG path: {$template->svg_path}\n";

    if ($template->design) {
        $design = is_array($template->design) ? $template->design : json_decode($template->design, true);
        echo "Design data loaded\n";

        // Extract layers
        $layers = [];
        if (isset($design['pages'])) {
            $pages = $design['pages'];
            if (is_array($pages)) {
                foreach ($pages as $pageKey => $page) {
                    if (isset($page['layers'])) {
                        $layers = $page['layers'];
                        break;
                    } elseif (isset($page['nodes'])) {
                        $layers = $page['nodes'];
                        break;
                    }
                }
            }
        }

        echo "Layers found: " . count($layers) . "\n";

        // Generate SVG
        $controller = new \App\Http\Controllers\Admin\TemplateController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('generateSvgFromTemplate');
        $method->setAccessible(true);
        $generatedSvg = $method->invoke($controller, $template->id);

        if ($generatedSvg) {
            echo "SVG generated successfully!\n";
            // Update the template
            $template->svg_path = 'templates/front/svg/template_19_regenerated.svg';
            Storage::disk('public')->put($template->svg_path, base64_decode(str_replace('data:image/svg+xml;base64,', '', $generatedSvg)));
            $template->save();
            echo "Updated template SVG path to: {$template->svg_path}\n";
        } else {
            echo "SVG generation failed\n";
        }
    }
} else {
    echo "Template not found\n";
}