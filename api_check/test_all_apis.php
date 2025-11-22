<?php

/**
 * API Health Check Script
 * Tests all API endpoints and provides a comprehensive report
 * 
 * Usage: php api_check/test_all_apis.php [--base-url=URL] [--admin-email=EMAIL] [--admin-password=PASSWORD]
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiTester
{
    private $client;
    private $baseUrl;
    private $adminToken = null;
    private $salesManagerToken = null;
    private $userToken = null;
    private $results = [];
    private $stats = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function __construct($baseUrl = 'http://127.0.0.1:8000/api')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        // Don't use base_uri to avoid path combination issues - we'll use full URLs
        $this->client = new Client([
            'timeout' => 10,  // Reduced timeout
            'connect_timeout' => 5,  // Connection timeout
            'http_errors' => false,
            'verify' => false,
            'allow_redirects' => true
        ]);
    }

    /**
     * Test a single endpoint
     */
    private function testEndpoint($method, $endpoint, $requiresAuth = false, $authType = null, $data = null, $headers = [])
    {
        $this->stats['total']++;
        $endpointFull = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Show progress
        echo "Testing: {$method} {$endpoint}... ";
        flush();
        
        $testResult = [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => 'pending',
            'status_code' => null,
            'response_time' => null,
            'error' => null,
            'requires_auth' => $requiresAuth,
            'auth_type' => $authType
        ];

        try {
            // Skip if authentication required but token not available
            if ($requiresAuth) {
                $token = null;
                if ($authType === 'admin' && !$this->adminToken) {
                    $testResult['status'] = 'skipped';
                    $testResult['error'] = 'Admin token not available';
                $this->stats['skipped']++;
                $this->results[] = $testResult;
                echo "⊘ Skipped (no token)\n";
                return $testResult;
                } elseif ($authType === 'sales_manager' && !$this->salesManagerToken) {
                    $testResult['status'] = 'skipped';
                    $testResult['error'] = 'Sales Manager token not available';
                $this->stats['skipped']++;
                $this->results[] = $testResult;
                echo "⊘ Skipped (no token)\n";
                return $testResult;
                } elseif ($authType === 'user' && !$this->userToken) {
                    $testResult['status'] = 'skipped';
                    $testResult['error'] = 'User token not available';
                $this->stats['skipped']++;
                $this->results[] = $testResult;
                echo "⊘ Skipped (no token)\n";
                return $testResult;
                }

                // Set appropriate token
                if ($authType === 'admin') {
                    $token = $this->adminToken;
                } elseif ($authType === 'sales_manager') {
                    $token = $this->salesManagerToken;
                } elseif ($authType === 'user') {
                    $token = $this->userToken;
                }

                if ($token) {
                    $headers['Authorization'] = 'Bearer ' . $token;
                }
            }

            $options = [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ], $headers)
            ];

            if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            $startTime = microtime(true);
            // Use absolute URL to avoid base_uri path combination issues
            // If endpoint starts with /, use it directly, otherwise combine with baseUrl
            if (strpos($endpoint, '/') === 0) {
                $fullUrl = $this->baseUrl . $endpoint;
            } else {
                $fullUrl = $this->baseUrl . '/' . $endpoint;
            }
            $response = $this->client->request($method, $fullUrl, $options);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $statusCode = $response->getStatusCode();
            $testResult['status_code'] = $statusCode;
            $testResult['response_time'] = $responseTime . 'ms';

            // Consider 2xx and 3xx as success, 4xx/5xx as failure
            if ($statusCode >= 200 && $statusCode < 400) {
                $testResult['status'] = 'passed';
                $this->stats['passed']++;
                echo "✓ [{$statusCode}] ({$responseTime}ms)\n";
                flush();
            } else {
                $testResult['status'] = 'failed';
                $testResult['error'] = 'HTTP ' . $statusCode;
                $body = $response->getBody()->getContents();
                if (strlen($body) < 200) {
                    $testResult['error'] .= ': ' . substr($body, 0, 200);
                }
                $this->stats['failed']++;
                echo "✗ [{$statusCode}] ({$responseTime}ms)\n";
                flush();
            }

        } catch (RequestException $e) {
            $testResult['status'] = 'failed';
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Connection') !== false || strpos($errorMsg, 'timeout') !== false) {
                $errorMsg = 'Connection failed or timeout - Server may not be running';
            }
            $testResult['error'] = $errorMsg;
            $this->stats['failed']++;
            echo "✗ Connection Error: " . substr($errorMsg, 0, 50) . "\n";
            flush();
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $testResult['status'] = 'failed';
            $testResult['error'] = 'Connection refused - Server may not be running at ' . $this->baseUrl;
            $this->stats['failed']++;
            echo "✗ Connection Refused - Check if server is running\n";
            flush();
        } catch (\Exception $e) {
            $testResult['status'] = 'failed';
            $testResult['error'] = $e->getMessage();
            $this->stats['failed']++;
            echo "✗ Error: " . substr($e->getMessage(), 0, 50) . "\n";
            flush();
        }

        $this->results[] = $testResult;
        return $testResult;
    }

    /**
     * Test all CRUD operations for an API resource
     * Laravel apiResource creates: GET (index), POST (store), GET/{id} (show), PUT/{id} (update), DELETE/{id} (destroy)
     */
    private function testApiResource($resource, $requiresAuth = false, $authType = null)
    {
        // GET /resource (index)
        $this->testEndpoint('GET', $resource, $requiresAuth, $authType);
        
        // POST /resource (store)
        $this->testEndpoint('POST', $resource, $requiresAuth, $authType, ['name' => 'Test ' . $resource]);
        
        // GET /resource/{id} (show)
        $this->testEndpoint('GET', $resource . '/1', $requiresAuth, $authType);
        
        // PUT /resource/{id} (update)
        $this->testEndpoint('PUT', $resource . '/1', $requiresAuth, $authType, ['name' => 'Updated ' . $resource]);
        
        // DELETE /resource/{id} (destroy)
        $this->testEndpoint('DELETE', $resource . '/1', $requiresAuth, $authType);
    }

    /**
     * Authenticate as admin
     */
    public function authenticateAdmin($email, $password)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'user_type' => 1  // 1 = ADMIN_USER, 2 = SALES_MANAGER
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            if ($response->getStatusCode() === 200 && isset($body['token'])) {
                $this->adminToken = $body['token'];
                return true;
            } else {
                echo "Authentication failed: Status " . $response->getStatusCode() . "\n";
                if (isset($body['message'])) {
                    echo "Message: " . $body['message'] . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "Failed to authenticate admin: " . $e->getMessage() . "\n";
        }
        return false;
    }

    /**
     * Authenticate as user
     */
    public function authenticateUser($email, $password)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/user-login', [
                'json' => [
                    'email' => $email,
                    'password' => $password
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            if ($response->getStatusCode() === 200 && isset($body['token'])) {
                $this->userToken = $body['token'];
                return true;
            } else {
                echo "User authentication failed: Status " . $response->getStatusCode() . "\n";
                if (isset($body['message'])) {
                    echo "Message: " . $body['message'] . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "Failed to authenticate user: " . $e->getMessage() . "\n";
        }
        return false;
    }

    /**
     * Test server connectivity
     */
    public function testConnection()
    {
        echo "Checking server connectivity... ";
        flush();
        try {
            // Use absolute URL to avoid base_uri path combination issues
            $testUrl = $this->baseUrl . '/testing';
            $response = $this->client->get($testUrl, [
                'timeout' => 5,
                'connect_timeout' => 3
            ]);
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                echo "✓ Server is reachable\n\n";
                return true;
            } elseif ($statusCode >= 200 && $statusCode < 500) {
                echo "⚠ Server responded with status {$statusCode}\n\n";
                return true; // Server is reachable even if endpoint doesn't exist
            } else {
                echo "✗ Server returned status {$statusCode}\n\n";
                return false;
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            echo "✗ Connection failed!\n";
            echo "  Error: Cannot connect to {$this->baseUrl}\n";
            echo "  Please check:\n";
            echo "  1. Is the Laravel server running? (Try: php artisan serve)\n";
            echo "  2. Is the base URL correct? (Current: {$this->baseUrl})\n";
            echo "  3. Is there a firewall blocking the connection?\n";
            echo "  4. Try using 127.0.0.1 instead of localhost\n\n";
            return false;
        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }

    /**
     * Run all API tests
     */
    public function runAllTests()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "  HOMETEX API HEALTH CHECK\n";
        echo str_repeat("=", 80) . "\n\n";
        echo "Base URL: {$this->baseUrl}\n";
        
        // Test connection first
        if (!$this->testConnection()) {
            echo "Aborting tests due to connection failure.\n";
            echo "Please start your Laravel server and try again.\n";
            return;
        }
        
        echo "Starting tests...\n\n";

        // ========== PUBLIC ENDPOINTS ==========
        echo "Testing Public Endpoints...\n";
        echo str_repeat("-", 80) . "\n";

        // Simple test endpoint
        $this->testEndpoint('GET', 'testing');

        // CSV endpoint
        $this->testEndpoint('POST', 'save-csv', false, null, ['test' => 'data']);

        // Product endpoints
        $this->testEndpoint('GET', 'products-web');
        $this->testEndpoint('GET', 'products-web/1');
        $this->testEndpoint('GET', 'products-details-web/1');
        $this->testEndpoint('GET', 'product/menu');

        // Location endpoints
        $this->testEndpoint('GET', 'divisions');
        $this->testEndpoint('GET', 'district/1');
        $this->testEndpoint('GET', 'area/1');

        // Checkout endpoints
        $this->testEndpoint('POST', 'check-out', false, null, [
            'items' => [],
            'customer_info' => []
        ]);
        $this->testEndpoint('POST', 'check-out-logein-user', false, null, [
            'items' => [],
            'customer_info' => []
        ]);

        // Payment endpoints
        $this->testEndpoint('GET', 'get-payment-details');
        $this->testEndpoint('POST', 'payment-success', false, null, ['transaction_id' => 'test']);
        $this->testEndpoint('GET', 'payment-cancel');
        $this->testEndpoint('GET', 'payment-fail');

        // Order endpoints
        $this->testEndpoint('GET', 'my-order');

        // User endpoints
        $this->testEndpoint('POST', 'user-registration', false, null, [
            'first_name' => 'Test',
            'email' => 'test' . time() . '@example.com',
            'phone' => '01900000000',
            'password' => '123456',
            'conf_password' => '123456'
        ]);
        $this->testEndpoint('POST', 'user-login', false, null, [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        $this->testEndpoint('GET', 'my-profile');
        $this->testEndpoint('POST', 'my-profile-update', false, null, []);

        // Wishlist endpoints
        $this->testEndpoint('POST', 'wish-list', false, null, ['product_id' => 1]);
        $this->testEndpoint('POST', 'get-wish-list', false, null, []);
        $this->testEndpoint('POST', 'delete-wish-list', false, null, ['product_id' => 1]);

        // Payment Gateway
        $this->testEndpoint('GET', 'get-token');

        // Product duplicate (special endpoint)
        $this->testEndpoint('GET', 'product/duplicate/@_jkL_qwErtOp~_lis/1');

        // Public Product API Resource (line 176 in routes)
        echo "\nTesting Public Product API Resource...\n";
        echo str_repeat("-", 80) . "\n";
        $this->testApiResource('product', false, null);

        // ========== ADMIN AUTHENTICATED ENDPOINTS ==========
        if ($this->adminToken) {
            echo "\nTesting Admin Authenticated Endpoints...\n";
            echo str_repeat("-", 80) . "\n";

            $this->testEndpoint('POST', 'logout', true, 'admin');
            $this->testEndpoint('GET', 'get-attribute-list', true, 'admin');
            $this->testEndpoint('GET', 'get-supplier-list', true, 'admin');
            $this->testEndpoint('GET', 'get-country-list', true, 'admin');
            $this->testEndpoint('GET', 'get-brand-list', true, 'admin');
            $this->testEndpoint('GET', 'get-category-list', true, 'admin');
            $this->testEndpoint('GET', 'get-shop-list', true, 'admin');
            $this->testEndpoint('GET', 'get-product-list-for-bar-code', true, 'admin');
            $this->testEndpoint('GET', 'get-sub-category-list/1', true, 'admin');
            $this->testEndpoint('GET', 'get-payment-methods', true, 'admin');
            $this->testEndpoint('GET', 'products/1', true, 'admin');

            // API Resources - Test all CRUD operations
            echo "\nTesting Admin API Resources (CRUD operations)...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testApiResource('product', true, 'admin');
            $this->testApiResource('category', true, 'admin');
            $this->testApiResource('sub-category', true, 'admin');
            $this->testApiResource('brand', true, 'admin');
            $this->testApiResource('supplier', true, 'admin');
            $this->testApiResource('attribute', true, 'admin');
            $this->testApiResource('attribute-value', true, 'admin');
            $this->testApiResource('photo', true, 'admin');
            $this->testApiResource('shop', true, 'admin');
            $this->testApiResource('customer', true, 'admin');
            $this->testApiResource('order', true, 'admin');

            // Additional Admin Endpoints
            echo "\nTesting Additional Admin Endpoints...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testEndpoint('POST', 'product-photo-upload/1', true, 'admin', ['photo' => 'test']);
            $this->testEndpoint('PUT', 'products/1', true, 'admin', ['name' => 'test']);

            // Transfers - Test all operations
            echo "\nTesting Transfers Endpoints...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testEndpoint('GET', 'transfers', true, 'admin');
            $this->testEndpoint('POST', 'transfers', true, 'admin', ['from_shop_id' => 1, 'to_shop_id' => 2, 'products' => []]);
            $this->testEndpoint('GET', 'transfers/1', true, 'admin');
            $this->testEndpoint('PUT', 'transfers/1/approve', true, 'admin');
            $this->testEndpoint('PUT', 'transfers/1/reject', true, 'admin');
        } else {
            echo "\nSkipping Admin Authenticated Endpoints (no admin token)\n";
        }

        // ========== SALES MANAGER AUTHENTICATED ENDPOINTS ==========
        if ($this->salesManagerToken) {
            echo "\nTesting Sales Manager Authenticated Endpoints...\n";
            echo str_repeat("-", 80) . "\n";

            $this->testEndpoint('POST', 'logout', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-attribute-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-supplier-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-country-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-brand-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-category-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-sub-category-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-child-sub-category-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-shop-list', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-product-list-for-bar-code', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-sub-category-list/1', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-child-sub-category-list/1', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-payment-methods', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-reports', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-add-product-data', true, 'sales_manager');
            $this->testEndpoint('GET', 'products/1', true, 'sales_manager');
            $this->testEndpoint('GET', 'get-product-columns', true, 'sales_manager');

            // API Resources - Test all CRUD operations
            echo "\nTesting Sales Manager API Resources (CRUD operations)...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testApiResource('sales-manager', true, 'sales_manager');
            $this->testApiResource('product', true, 'sales_manager');
            $this->testApiResource('category', true, 'sales_manager');
            $this->testApiResource('sub-category', true, 'sales_manager');
            $this->testApiResource('child-sub-category', true, 'sales_manager');
            $this->testApiResource('brand', true, 'sales_manager');
            $this->testApiResource('formula', true, 'sales_manager');
            $this->testApiResource('supplier', true, 'sales_manager');
            $this->testApiResource('attribute', true, 'sales_manager');
            $this->testApiResource('attribute-value', true, 'sales_manager');
            $this->testApiResource('photo', true, 'sales_manager');
            $this->testApiResource('shop', true, 'sales_manager');
            $this->testApiResource('customer', true, 'sales_manager');
            $this->testApiResource('order', true, 'sales_manager');

            // Additional Sales Manager Endpoints
            echo "\nTesting Additional Sales Manager Endpoints...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testEndpoint('POST', 'product/1/duplicate', true, 'sales_manager');
            $this->testEndpoint('POST', 'product-photo-upload/1', true, 'sales_manager', ['photo' => 'test']);
            $this->testEndpoint('PUT', 'products/1', true, 'sales_manager', ['name' => 'test']);

            // Transfers - Test all operations
            echo "\nTesting Transfers Endpoints...\n";
            echo str_repeat("-", 80) . "\n";
            $this->testEndpoint('GET', 'transfers', true, 'sales_manager');
            $this->testEndpoint('POST', 'transfers', true, 'sales_manager', ['from_shop_id' => 1, 'to_shop_id' => 2, 'products' => []]);
            $this->testEndpoint('GET', 'transfers/1', true, 'sales_manager');
            $this->testEndpoint('PUT', 'transfers/1/approve', true, 'sales_manager');
            $this->testEndpoint('PUT', 'transfers/1/reject', true, 'sales_manager');
        } else {
            echo "\nSkipping Sales Manager Authenticated Endpoints (no sales manager token)\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "  TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n\n";
    }

    /**
     * Print detailed results
     */
    public function printResults()
    {
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->results as $result) {
            $status = $result['status'];
            $method = str_pad($result['method'], 6);
            $endpoint = $result['endpoint'];
            $statusCode = $result['status_code'] ?: 'N/A';
            $responseTime = $result['response_time'] ?: 'N/A';
            $auth = $result['requires_auth'] ? '[' . $result['auth_type'] . ']' : '';

            if ($status === 'passed') {
                $passed++;
                echo "✓ ";
            } elseif ($status === 'failed') {
                $failed++;
                echo "✗ ";
            } else {
                $skipped++;
                echo "⊘ ";
            }

            echo sprintf(
                "%s %-50s [%s] %s %s",
                $method,
                substr($endpoint, 0, 50),
                $statusCode,
                $responseTime,
                $auth
            );

            if ($result['error']) {
                echo " - " . substr($result['error'], 0, 50);
            }

            echo "\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo sprintf("Total: %d | Passed: %d | Failed: %d | Skipped: %d\n", 
            $this->stats['total'], 
            $passed, 
            $failed, 
            $skipped
        );
        echo str_repeat("=", 80) . "\n";

        // Show failed endpoints
        if ($failed > 0) {
            echo "\nFailed Endpoints:\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->results as $result) {
                if ($result['status'] === 'failed') {
                    echo sprintf(
                        "✗ %s %s [%s] - %s\n",
                        $result['method'],
                        $result['endpoint'],
                        $result['status_code'] ?: 'N/A',
                        $result['error'] ?: 'Unknown error'
                    );
                }
            }
        }
    }

    /**
     * Export results to JSON
     */
    public function exportToJson($filename = 'api_test_results.json')
    {
        $output = [
            'timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'stats' => $this->stats,
            'results' => $this->results
        ];

        file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT));
        echo "\nResults exported to: {$filename}\n";
    }
}

// Parse command line arguments
$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = null;
$adminPassword = null;
$userEmail = null;
$userPassword = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = substr($arg, 11);
    } elseif (strpos($arg, '--admin-email=') === 0) {
        $adminEmail = substr($arg, 14);
    } elseif (strpos($arg, '--admin-password=') === 0) {
        $adminPassword = substr($arg, 17);
    } elseif (strpos($arg, '--user-email=') === 0) {
        $userEmail = substr($arg, 13);
    } elseif (strpos($arg, '--user-password=') === 0) {
        $userPassword = substr($arg, 16);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php api_check/test_all_apis.php [OPTIONS]\n\n";
        echo "Options:\n";
        echo "  --base-url=URL              Base URL for API (default: http://localhost/hometext_Api/public/api)\n";
        echo "  --admin-email=EMAIL         Admin email for authentication\n";
        echo "  --admin-password=PASSWORD  Admin password for authentication\n";
        echo "  --user-email=EMAIL          User email for authentication\n";
        echo "  --user-password=PASSWORD    User password for authentication\n";
        echo "  --help, -h                  Show this help message\n\n";
        exit(0);
    }
}

// Run tests
$tester = new ApiTester($baseUrl);

// Authenticate if credentials provided
if ($adminEmail && $adminPassword) {
    echo "Authenticating as admin...\n";
    if ($tester->authenticateAdmin($adminEmail, $adminPassword)) {
        echo "✓ Admin authentication successful\n\n";
    } else {
        echo "✗ Admin authentication failed\n\n";
    }
}

if ($userEmail && $userPassword) {
    echo "Authenticating as user...\n";
    if ($tester->authenticateUser($userEmail, $userPassword)) {
        echo "✓ User authentication successful\n\n";
    } else {
        echo "✗ User authentication failed\n\n";
    }
}

// Run all tests
$tester->runAllTests();
$tester->printResults();
$tester->exportToJson('api_test_results.json');

echo "\nTest completed!\n";

