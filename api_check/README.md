# API Health Check Script

This script tests all API endpoints in the Hometex backend to verify they are running correctly.

## Features

- ✅ Tests all public endpoints
- ✅ Tests authenticated endpoints (if credentials provided)
- ✅ Provides detailed status reports
- ✅ Measures response times
- ✅ Exports results to JSON
- ✅ Color-coded output (✓ passed, ✗ failed, ⊘ skipped)

## Usage

### Basic Usage (Public Endpoints Only)

```bash
php api_check/test_all_apis.php
```

### With Admin Authentication

```bash
php api_check/test_all_apis.php --admin-email=admin@example.com --admin-password=password
```

### With Custom Base URL

```bash
php api_check/test_all_apis.php --base-url=http://your-domain.com/api
```

### Full Example

```bash
php api_check/test_all_apis.php \
    --base-url=http://localhost/hometext_Api/public/api \
    --admin-email=admin@example.com \
    --admin-password=admin123 \
    --user-email=user@example.com \
    --user-password=user123
```

### Using Helper Scripts

**Windows:**
```cmd
api_check\run_tests.bat
```

**Linux/Mac:**
```bash
chmod +x api_check/run_tests.sh
./api_check/run_tests.sh
```

## Command Line Options

- `--base-url=URL` - Base URL for API (default: `http://localhost/hometext_Api/public/api`)
- `--admin-email=EMAIL` - Admin email for authentication
- `--admin-password=PASSWORD` - Admin password for authentication
- `--user-email=EMAIL` - User email for authentication
- `--user-password=PASSWORD` - User password for authentication
- `--help` or `-h` - Show help message

## Output

The script provides:

1. **Console Output**: Real-time test results with status indicators
2. **Summary Report**: Total tests, passed, failed, and skipped counts
3. **JSON Export**: Detailed results saved to `api_test_results.json`

### Example Output

```
================================================================================
  HOMETEX API HEALTH CHECK
================================================================================

Base URL: http://localhost/hometext_Api/public/api
Starting tests...

Testing Public Endpoints...
--------------------------------------------------------------------------------
✓ GET    testing                                          [200] 45.23ms
✓ GET    products-web                                      [200] 120.45ms
✗ POST   check-out                                        [422] 89.12ms - Validation error
...

================================================================================
  TEST SUMMARY
================================================================================

Total: 45 | Passed: 38 | Failed: 5 | Skipped: 2
================================================================================
```

## Tested Endpoints

### Public Endpoints
- `/testing` - Health check
- `/save-csv` - CSV upload
- `/login` - Admin login
- `/products-web` - Product listing
- `/products-details-web/{id}` - Product details
- `/product/menu` - Product menu
- `/divisions` - Get divisions
- `/district/{id}` - Get districts
- `/area/{id}` - Get areas
- `/check-out` - Checkout
- `/check-out-logein-user` - Checkout for logged-in users
- `/get-payment-details` - Payment details
- `/payment-success` - Payment success callback
- `/payment-cancel` - Payment cancel
- `/payment-fail` - Payment fail
- `/my-order` - User orders
- `/user-registration` - User registration
- `/user-login` - User login
- `/my-profile` - User profile
- `/my-profile-update` - Update profile
- `/wish-list` - Wishlist operations
- `/get-token` - Payment gateway token

### Admin Authenticated Endpoints
- `/logout` - Logout
- `/get-attribute-list` - Attribute list
- `/get-supplier-list` - Supplier list
- `/get-country-list` - Country list
- `/get-brand-list` - Brand list
- `/get-category-list` - Category list
- `/get-shop-list` - Shop list
- `/product` - Product CRUD
- `/category` - Category CRUD
- `/sub-category` - Sub-category CRUD
- `/brand` - Brand CRUD
- `/supplier` - Supplier CRUD
- `/attribute` - Attribute CRUD
- `/attribute-value` - Attribute value CRUD
- `/photo` - Photo CRUD
- `/shop` - Shop CRUD
- `/customer` - Customer CRUD
- `/order` - Order CRUD
- `/transfers` - Product transfers

### Sales Manager Authenticated Endpoints
- All admin endpoints plus:
- `/get-reports` - Reports
- `/get-add-product-data` - Product data
- `/get-product-columns` - Product columns
- `/child-sub-category` - Child sub-category CRUD
- `/formula` - Formula CRUD
- `/sales-manager` - Sales manager CRUD

## Requirements

- PHP 8.1 or higher
- Composer dependencies installed (`composer install`)
- Guzzle HTTP client (included in composer.json)
- Laravel backend running

## Notes

- Endpoints requiring authentication will be skipped if credentials are not provided
- Some endpoints may return 422 (validation errors) or 401 (unauthorized) which are expected for test data
- Response times are measured in milliseconds
- The script uses a 30-second timeout for each request




