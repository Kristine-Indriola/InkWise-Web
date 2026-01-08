<?php
echo "Testing server connectivity...\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$result = file_get_contents('http://127.0.0.1:8000/', false, $context);

if ($result !== false) {
    echo "SUCCESS: Server responded\n";
    // Get the HTTP response code
    $headers = $http_response_header;
    if (!empty($headers)) {
        $statusLine = $headers[0];
        echo "Response: $statusLine\n";
    }
} else {
    echo "ERROR: Could not connect to server\n";
}
?>
