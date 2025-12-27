<?php

/**
 * Test the two remaining failing endpoints
 */

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

// Login
echo "Logging in...\n";
$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'sales@new.com',
    'password' => 'password',
    'user_type' => 2
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['token'])) {
    $token = $response['token'];
    echo "✓ Login successful\n\n";
} else {
    die("✗ Login failed\n");
}

// Test 1: get-child-sub-category-list (without parameter - should fail)
echo "1. Testing get-child-sub-category-list (no param)...\n";
$ch = curl_init("$baseUrl/get-child-sub-category-list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Status: $status\n";
$data = json_decode($response, true);
if ($status !== 200) {
    echo "   Error: " . ($data['message'] ?? 'Unknown') . "\n";
}
echo "\n";

// Test 2: attribute-value index
echo "2. Testing attribute-value index...\n";
$ch = curl_init("$baseUrl/attribute-value");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Status: $status\n";
$data = json_decode($response, true);
if ($status !== 200) {
    echo "   Error: " . ($data['message'] ?? 'Unknown') . "\n";
} else {
    echo "   ✓ Success - returned " . (is_array($data) ? count($data) : 0) . " items\n";
}
echo "\n";

echo "Done!\n";
