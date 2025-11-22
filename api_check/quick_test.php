<?php
/**
 * Quick connection test to verify server is reachable
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

$baseUrl = $argv[1] ?? 'http://127.0.0.1:8000/api';
$baseUrl = rtrim($baseUrl, '/');

echo "Testing connection to: {$baseUrl}\n\n";

$client = new Client([
    'base_uri' => $baseUrl,
    'timeout' => 5,
    'connect_timeout' => 3,
    'http_errors' => false,
    'verify' => false
]);

try {
    echo "Attempting to connect...\n";
    // Use absolute URL to avoid base_uri issues
    $response = $client->get($baseUrl . '/testing');
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    
    if ($statusCode === 200) {
        echo "✓ Connection successful!\n";
        echo "Status Code: {$statusCode}\n";
        echo "Response: " . substr($body, 0, 100) . "\n";
    } else {
        echo "⚠ Connection works but got status {$statusCode}\n";
        echo "Response: " . substr($body, 0, 100) . "\n";
    }
    
} catch (ConnectException $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Possible issues:\n";
    echo "1. Laravel server is not running\n";
    echo "2. Wrong base URL (current: {$baseUrl})\n";
    echo "3. Firewall blocking connection\n";
    echo "4. Server is running on a different port\n\n";
    echo "To start Laravel server, run: php artisan serve\n";
    exit(1);
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nServer is ready for testing!\n";

