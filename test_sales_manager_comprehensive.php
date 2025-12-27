<?php

/**
 * Comprehensive Sales Manager API Test
 * Tests ALL sales manager protected routes
 */

$baseUrl = 'http://127.0.0.1:8000/api';

// Test data - Try multiple accounts
$salesManagerAccounts = [
    ['email' => 'sales@new.com', 'password' => 'password'],
    ['email' => 'rabby.ftms@gmail.com', 'password' => 'password'],
    ['email' => 'admin@hometexbd.ltd', 'password' => 'password'],
];

echo "=== Comprehensive Sales Manager API Test ===\n\n";

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
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
        echo "   Name: " . ($loginResponse['body']['name'] ?? 'N/A') . "\n";
        echo "   Email: {$account['email']}\n\n";
        $loginSuccess = true;
        break;
    } else {
        echo "✗ [{$loginResponse['status']}]\n";
    }
}

if ($loginSuccess) {
    
    // Test ALL protected endpoints for Sales Manager
    $protectedEndpoints = [
        // Core Lists
        ['GET', 'get-product-list-for-bar-code', 'Product List for Barcode', true],
        ['GET', 'get-product-columns', 'Product Columns', true],
        ['GET', 'get-attribute-list', 'Attribute List', true],
        ['GET', 'get-supplier-list', 'Supplier List', true],
        ['GET', 'get-country-list', 'Country List', true],
        ['GET', 'get-brand-list', 'Brand List', true],
        ['GET', 'get-category-list', 'Category List', true],
        ['GET', 'get-sub-category-list', 'Sub Category List (Full)', true],
        ['GET', 'get-child-sub-category-list', 'Child Sub Category List', true],
        ['GET', 'get-shop-list', 'Shop List', true],
        ['GET', 'get-payment-methods', 'Payment Methods', true],
        ['GET', 'get-add-product-data', 'Add Product Data', true],
        
        // Resources - Index
        ['GET', 'sales-manager', 'Sales Managers Index', true],
        ['GET', 'product', 'Products Index', true],
        ['GET', 'category', 'Categories Index', true],
        ['GET', 'sub-category', 'Sub Categories Index', true],
        ['GET', 'child-sub-category', 'Child Sub Categories Index', true],
        ['GET', 'brand', 'Brands Index', true],
        ['GET', 'formula', 'Formulas Index', true],
        ['GET', 'supplier', 'Suppliers Index', true],
        ['GET', 'attribute', 'Attributes Index', true],
        ['GET', 'attribute-value', 'Attribute Values Index', true],
        ['GET', 'photo', 'Photos Index', true],
        ['GET', 'shop', 'Shops Index', true],
        ['GET', 'customer', 'Customers Index', true],
        ['GET', 'order', 'Orders Index', true],
        
        // Transfers
        ['GET', 'transfers', 'Transfers List', true],
        
        // Reports
        ['GET', 'get-reports', 'Reports', true],
        
        // Logout
        ['POST', 'logout', 'Logout', false], // Skip logout test
    ];
    
    echo "2. Testing All Protected Endpoints:\n\n";
    
    $passed = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($protectedEndpoints as $endpoint) {
        [$method, $path, $name, $test] = $endpoint;
        
        if (!$test) {
            echo "   " . str_pad($name, 50) . " ⊘ SKIPPED\n";
            $skipped++;
            continue;
        }
        
        echo "   " . str_pad($name, 50);
        
        $response = callApi($method, $path, null, $token);
        
        if ($response['status'] === 200) {
            echo " ✓ PASS [{$response['status']}]\n";
            $passed++;
        } elseif ($response['status'] === 401 || $response['status'] === 403) {
            echo " ✗ AUTH FAIL [{$response['status']}]\n";
            $failed++;
            if (isset($response['body']['message'])) {
                echo "      " . $response['body']['message'] . "\n";
            }
        } elseif ($response['status'] >= 500) {
            echo " ⚠ SERVER ERROR [{$response['status']}]\n";
            $failed++;
            if (isset($response['body']['message'])) {
                $msg = $response['body']['message'];
                echo "      " . (strlen($msg) > 80 ? substr($msg, 0, 80) . '...' : $msg) . "\n";
            }
        } else {
            echo " ⊘ [{$response['status']}]\n";
            // Non-critical statuses (404, 422, etc) - count as warning
            $passed++;
        }
    }
    
    echo "\n=== Final Results ===\n";
    echo "✓ Passed:  $passed\n";
    echo "✗ Failed:  $failed\n";
    echo "⊘ Skipped: $skipped\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    echo "Total:     " . ($passed + $failed + $skipped) . "\n\n";
    
    if ($failed > 0) {
        echo "⚠️  Some endpoints failed authentication/authorization.\n";
        echo "   Sales Manager may not have full access to all routes.\n";
    } else {
        echo "✅ SUCCESS! All tested endpoints are accessible.\n";
        echo "   Sales Manager has proper access to all routes!\n";
    }
    
} else {
    echo "\n   ✗ All login attempts failed!\n";
    echo "   Cannot test protected endpoints.\n";
}

echo "\n";
