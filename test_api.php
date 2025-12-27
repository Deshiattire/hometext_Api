<?php

// Test script to check API authentication

$baseUrl = 'http://127.0.0.1:8000/api';

echo "=== Testing Sales Manager Login ===\n";

// Step 1: Login
$loginData = [
    'email' => 'sales@new.com',
    'password' => 'password',
    'user_type' => 2
];

$ch = curl_init($baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response (HTTP $httpCode):\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        $token = $data['token'];
        echo "Token: $token\n\n";
        
        // Step 2: Test get-category-list
        echo "=== Testing get-category-list ===\n";
        
        $ch = curl_init($baseUrl . '/get-category-list');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Category List Response (HTTP $httpCode):\n";
        echo $response . "\n";
    } else {
        echo "No token in response\n";
    }
} else {
    echo "Login failed\n";
}
