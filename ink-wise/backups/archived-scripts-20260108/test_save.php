<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = App\Models\Template::find(112);

if (!$template) {
    echo "Template not found\n";
    exit;
}

echo "Before test save:\n";
echo "Preview: " . ($template->preview ?? 'NULL') . "\n";
echo "SVG: " . ($template->svg_path ?? 'NULL') . "\n";
echo "Metadata: " . json_encode($template->metadata) . "\n";

$controller = new App\Http\Controllers\Admin\TemplateController();
$request = new Illuminate\Http\Request();

try {
    $response = $controller->testSave($request, 112);
    echo "Test save response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Reload
$template->refresh();

echo "After test save:\n";
echo "Preview: " . ($template->preview ?? 'NULL') . "\n";
echo "SVG: " . ($template->svg_path ?? 'NULL') . "\n";
echo "Metadata: " . json_encode($template->metadata) . "\n";

?>