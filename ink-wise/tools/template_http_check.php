<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use App\Support\ImageResolver;

$templates = Template::select('id','name','front_image','back_image','preview')->get();

$client = new \GuzzleHttp\Client(['http_errors' => false, 'verify' => false]);

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
        echo "  $k url: $url\n";

        if ($url) {
            try {
                $resp = $client->head($url);
                $status = $resp->getStatusCode();
                $ctype = $resp->getHeaderLine('Content-Type');
                echo "    HTTP: $status, Content-Type: $ctype\n";
            } catch (Exception $e) {
                echo "    Request error: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "------\n";
}
