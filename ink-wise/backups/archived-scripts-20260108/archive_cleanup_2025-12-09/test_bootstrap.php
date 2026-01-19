<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(67);
if ($template) {
    $designPayload = json_decode($template->design ?? '[]', true) ?? [];
    if (is_array($template->design)) {
        $designPayload = $template->design;
    }
    $pageCount = isset($designPayload['pages']) && is_array($designPayload['pages']) ? count($designPayload['pages']) : 1;

    $bootstrap = [
        'csrfToken' => csrf_token(),
        'template' => [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $designPayload['category'] ?? null,
            'design' => $designPayload,
            'status' => $template->status ?? null,
            'slug' => $template->slug ?? null,
            'updated_at' => optional($template->updated_at)->toIso8601String(),
        ],
        'routes' => [
            'index' => route('staff.templates.index'),
            'create' => route('staff.templates.create'),
            'update' => route('staff.templates.update', $template->id),
            'saveTemplate' => route('staff.templates.saveTemplate', $template->id),
            'saveCanvas' => route('staff.templates.saveCanvas', $template->id),
            'saveTemplate' => route('staff.templates.saveTemplate', $template->id),
            'saveSvg' => route('staff.templates.saveSvg', $template->id),
            'uploadPreview' => route('staff.templates.uploadPreview', $template->id),
            'saveVersion' => route('staff.templates.saveVersion', $template->id),
            'loadDesign' => route('staff.templates.loadDesign', $template->id),
            'searchAssets' => route('staff.templates.searchAssets', $template->id),
            'autosave' => route('staff.templates.autosave', $template->id),
            'figmaAnalyze' => route('staff.templates.figma.analyze'),
            'figmaPreview' => route('staff.templates.figma.preview'),
            'figmaImport' => route('staff.templates.figma.import'),
        ],
        'flags' => [
            'betaMockupPreview' => (bool) config('services.inkwise.enable_mockup_preview', false),
            'enableFilters' => true,
        ],
        'user' => [
            'id' => 1, // Simulate logged in user
            'name' => 'Test User',
        ],
    ];

    echo 'Bootstrap JSON is valid: ' . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO - ' . json_last_error_msg()) . PHP_EOL;
    echo 'Design data length: ' . strlen(json_encode($designPayload)) . PHP_EOL;
    echo 'Page count: ' . $pageCount . PHP_EOL;
} else {
    echo 'Template not found' . PHP_EOL;
}