<?php
// Debug test to see what's actually returned
$url = 'http://127.0.0.1:8000/staff/templates/create?type=invitation';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: $error\n";
echo "Response Length: " . strlen($response) . "\n";
echo "First 1000 chars:\n";
echo substr($response, 0, 1000) . "\n";
echo "---\n";

// Look for any error messages
if (stripos($response, 'error') !== false) {
    echo "Found error in response\n";
}
if (stripos($response, 'exception') !== false) {
    echo "Found exception in response\n";
}
?>