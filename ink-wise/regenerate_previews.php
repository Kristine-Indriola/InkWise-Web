<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\GenerateTemplatePreview;
use App\Models\Template;
use Illuminate\Support\Arr;

$templates = Template::where('status', '!=', 'uploaded')->get();

echo 'Regenerating previews for ' . $templates->count() . " templates...\n";

foreach ($templates as $template) {
    $metadata = $template->metadata ?? [];
    if (!is_array($metadata)) {
        $metadata = json_decode(json_encode($metadata), true) ?: [];
    }
    $designPath = Arr::get($metadata, 'json_path', '');

    $job = new GenerateTemplatePreview($template->id, $designPath ?: '');
    $job->handle();

    echo '  ✓ Template ID ' . $template->id . " done\n";
}

echo "\nAll previews regenerated.\n";
