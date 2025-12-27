<?php

/**
 * Product Update API Test Examples
 * 
 * This demonstrates how to use the product update API
 * with partial updates - only send the fields you want to update
 */

$baseUrl = 'http://127.0.0.1:8000/api';

echo "=== Product Update API Examples ===\n\n";

// Login as admin first
echo "1. Logging in as admin...\n";
$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@example.com',
    'password' => 'password',
    'user_type' => 1 // Admin
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$response = json_decode(curl_exec($ch), true);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200 || !isset($response['token'])) {
    echo "   ✗ Login failed (Status: $status)\n";
    echo "   Using sales manager credentials instead...\n\n";
    
    // Try sales manager
    $ch = curl_init("$baseUrl/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'sales@new.com',
        'password' => 'password',
        'user_type' => 2 // Sales Manager
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
}

if (!isset($response['token'])) {
    die("✗ Could not authenticate. Please check credentials.\n");
}

$token = $response['token'];
echo "   ✓ Authenticated successfully\n\n";

// Example 1: Update only the price
echo "Example 1: Update only the price of product ID 1\n";
echo "---------------------------------------------------\n";
$updateData = [
    'price' => 1299.99
];

$ch = curl_init("$baseUrl/product/1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Request: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n";
echo "Status: $status\n";
if ($status === 200) {
    $data = json_decode($response, true);
    echo "✓ Success! Updated fields: " . implode(', ', $data['data']['updated_fields'] ?? []) . "\n";
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Example 2: Update multiple fields
echo "Example 2: Update name, price, and stock\n";
echo "---------------------------------------------------\n";
$updateData = [
    'name' => 'Premium Cotton T-Shirt - Updated',
    'price' => 899.99,
    'stock' => 150
];

$ch = curl_init("$baseUrl/product/1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Request: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n";
echo "Status: $status\n";
if ($status === 200) {
    $data = json_decode($response, true);
    echo "✓ Success! Updated fields: " . implode(', ', $data['data']['updated_fields'] ?? []) . "\n";
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Example 3: Update with discount
echo "Example 3: Add discount to product\n";
echo "---------------------------------------------------\n";
$updateData = [
    'discount_percent' => 20,
    'discount_start' => date('Y-m-d'),
    'discount_end' => date('Y-m-d', strtotime('+30 days'))
];

$ch = curl_init("$baseUrl/product/1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Request: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n";
echo "Status: $status\n";
if ($status === 200) {
    $data = json_decode($response, true);
    echo "✓ Success! Updated fields: " . implode(', ', $data['data']['updated_fields'] ?? []) . "\n";
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}
echo "\n";

// Example 4: Toggle featured status
echo "Example 4: Toggle featured status\n";
echo "---------------------------------------------------\n";
$updateData = [
    'isFeatured' => 1,
    'isTrending' => 1
];

$ch = curl_init("$baseUrl/product/1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Request: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n";
echo "Status: $status\n";
if ($status === 200) {
    $data = json_decode($response, true);
    echo "✓ Success! Updated fields: " . implode(', ', $data['data']['updated_fields'] ?? []) . "\n";
} else {
    echo "Response: " . substr($response, 0, 200) . "\n";
}
echo "\n";

echo "=== Summary ===\n";
echo "The product update API supports partial updates.\n";
echo "Only send the fields you want to update - all fields are optional.\n";
echo "The API will validate and update only the provided fields.\n";
echo "\nSupported update fields include:\n";
echo "  - Basic info: name, sku, description\n";
echo "  - Pricing: price, cost, discount_percent, discount_fixed\n";
echo "  - Stock: stock, stock_status, low_stock_threshold\n";
echo "  - Categories: category_id, sub_category_id, child_sub_category_id\n";
echo "  - Relations: brand_id, supplier_id, country_id\n";
echo "  - Status: status, isFeatured, isNew, isTrending\n";
echo "  - And many more...\n\n";
