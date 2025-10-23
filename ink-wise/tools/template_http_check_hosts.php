<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use App\Support\ImageResolver;

$templates = Template::select('id','name','front_image','back_image','preview')->get();

$client = new \GuzzleHttp\Client(['http_errors' => false, 'verify' => false, 'timeout' => 5]);

$hosts = [
    'http://127.0.0.1:8000',
    'http://localhost',
];

foreach ($templates as $t) {
    echo "Template {$t->id} - {$t->name}\n";
    $paths = [
        'front' => $t->front_image,
        'back' => $t->back_image,
        'preview' => $t->preview,
    ];

    foreach ($paths as $k => $p) {
        $url = ImageResolver::url($p);
        echo "  $k path: " . ($p ?? '(null)') . "\n";
        echo "  $k resolver url: $url\n";

        // filesystem check
        $normalized = ltrim(preg_replace('#^/?storage/#i', '', $p ?? ''), '/');
        $fs1 = storage_path('app/public/' . $normalized);
        $fs2 = public_path('storage/' . $normalized);
        echo "    fs storage path: $fs1 -> " . (file_exists($fs1) ? 'exists' : 'missing') . "\n";
        echo "    fs public/storage path: $fs2 -> " . (file_exists($fs2) ? 'exists' : 'missing') . "\n";

        foreach ($hosts as $host) {
            // if resolver returned root-relative, prepend host
            $testUrl = $url;
            if ($testUrl && strpos($testUrl, '//') !== 0 && strpos($testUrl, 'http') !== 0) {
                $testUrl = rtrim($host, '/') . '/' . ltrim($testUrl, '/');
            }
            if (!$testUrl) {
                echo "    skip host $host (no url)\n";
                continue;
            }
            try {
                $resp = $client->head($testUrl);
                $status = $resp->getStatusCode();
                $ctype = $resp->getHeaderLine('Content-Type');
                echo "    [$host] -> $testUrl HTTP: $status, Content-Type: $ctype\n";
            } catch (Exception $e) {
                echo "    [$host] request error: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "------\n";
}
