<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = new \GuzzleHttp\Client(['http_errors' => false, 'verify' => false]);
$hosts = ['http://127.0.0.1:8000', 'http://localhost'];

foreach ($hosts as $host) {
    $url = rtrim($host, '/') . '/admin/templates';
    echo "Fetching $url\n";
    try {
        $resp = $client->get($url);
        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();
        echo "Status: $status\n";
        // extract img src attributes
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $body, $matches);
        if (!empty($matches[1])) {
            echo "Found images (first 10):\n";
            foreach (array_slice($matches[1], 0, 10) as $src) {
                echo "  $src\n";
            }
        } else {
            echo "No <img> tags found\n";
        }
    } catch (Exception $e) {
        echo "Request error: " . $e->getMessage() . "\n";
    }
    echo "------\n";
}
