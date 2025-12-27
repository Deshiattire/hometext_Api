<?php

/**
 * Quick Sales Manager API Test
 * Tests if sales manager can access protected routes
 */

$baseUrl = 'http://127.0.0.1:8000/api';

// Test data - Try multiple accounts
$salesManagerAccounts = [
    ['email' => 'rabby.ftms@gmail.com', 'password' => 'password'],
    ['email' => 'admin@hometexbd.ltd', 'password' => 'password'],
    ['email' => 'sales@new.com', 'password' => 'password'],
    ['email' => 'sales@test.com', 'password' => 'password'],
];

echo "=== Sales Manager API Test ===\n\n";

// Function to make API calls
function callApi($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    
    $url = $baseUrl . '/' . ltrim($endpoint, '/');
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'body' => json_decode($response, true),
        'raw' => $response,
        'error' => $error
    ];
}

// Step 1: Login as Sales Manager
echo "1. Testing Sales Manager Login...\n";

$token = null;
$loginSuccess = false;

foreach ($salesManagerAccounts as $account) {
    echo "   Trying: {$account['email']}... ";
    
    $loginData = [
        'email' => $account['email'],
        'password' => $account['password'],
        'user_type' => 2 // Sales Manager
    ];
    
    $loginResponse = callApi('POST', 'login', $loginData);
    
    if ($loginResponse['status'] === 200 && isset($loginResponse['body']['token'])) {
        $token = $loginResponse['body']['token'];
        echo "✓ Success!\n";
        echo "   Token: " . substr($token, 0, 20) . "...\n";
        echo "   Name: " . ($loginResponse['body']['name'] ?? 'N/A') . "\n";
        echo "   Role: " . ($loginResponse['body']['role'] ?? 'N/A') . "\n\n";
        $loginSuccess = true;
        break;
    } else {
        echo "✗ [{$loginResponse['status']}]\n";
    }
}

if ($loginSuccess) {
    
    // Test protected endpoints
    $protectedEndpoints = [
        ['GET', 'get-product-list-for-bar-code', 'Product List for Barcode'],
        ['GET', 'get-attribute-list', 'Attribute List'],
        ['GET', 'get-supplier-list', 'Supplier List'],
        ['GET', 'get-brand-list', 'Brand List'],
        ['GET', 'get-category-list', 'Category List'],
        ['GET', 'get-shop-list', 'Shop List'],
        ['GET', 'get-payment-methods', 'Payment Methods'],
        ['GET', 'product', 'Products Index'],
        ['GET', 'category', 'Categories Index'],
        ['GET', 'brand', 'Brands Index'],
        ['GET', 'order', 'Orders Index'],
    ];
    
    echo "2. Testing Protected Endpoints:\n\n";
    
    $passed = 0;
    $failed = 0;
    
    foreach ($protectedEndpoints as $endpoint) {
        [$method, $path, $name] = $endpoint;
        echo "   Testing: $name ($method /$path)... ";
        
        $response = callApi($method, $path, null, $token);
        
        if ($response['status'] === 200) {
            echo "✓ PASS [{$response['status']}]\n";
            $passed++;
        } elseif ($response['status'] === 401 || $response['status'] === 403) {
            echo "✗ FAIL [{$response['status']}] - Authentication/Authorization Issue\n";
            $failed++;
            if (isset($response['body']['message'])) {
                echo "      Message: {$response['body']['message']}\n";
            }
        } else {
            echo "⚠ [{$response['status']}]\n";
            $failed++;
            if (isset($response['body']['message'])) {
                echo "      Message: {$response['body']['message']}\n";
            }
        }
    }
    
    echo "\n=== Results ===\n";
    echo "Passed: $passed\n";
    echo "Failed: $failed\n";
    echo "Total: " . ($passed + $failed) . "\n";
    
    if ($failed > 0) {
        echo "\n⚠️  Some endpoints failed. Sales Manager may not have proper access.\n";
    } else {
        echo "\n✓ All endpoints working! Sales Manager has proper access.\n";
    }
    
} else {
    echo "\n   ✗ All login attempts failed!\n";
    echo "   Please check:\n";
    echo "   - Do the sales manager accounts exist?\n";
    echo "   - Are the passwords correct?\n";
    echo "   - Run: php check_sales_manager.php\n";
}

echo "\n";
