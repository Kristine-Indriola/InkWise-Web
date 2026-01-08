<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int)($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Usage: php debug_template_preview.php <template_id>\n");
    exit(1);
}

templateInfo($id);

function templateInfo(int $id): void {
    $template = App\Models\Template::find($id);
    if (!$template) {
        fwrite(STDERR, "Template {$id} not found\n");
        return;
    }

    $data = [
        'id' => $template->id,
        'name' => $template->name,
        'front_image' => $template->front_image,
        'preview' => $template->preview,
        'preview_front' => $template->preview_front,
        'metadata' => $template->metadata,
    ];

    fwrite(STDOUT, json_encode($data, JSON_PRETTY_PRINT) . "\n");
}
