# API Endpoints Coverage

## Total Endpoints Being Tested

The updated script now tests **ALL** API endpoints including full CRUD operations for all resources.

### Public Endpoints (25+)
- Basic endpoints (testing, login, etc.)
- Product endpoints
- Location endpoints (divisions, districts, areas)
- Checkout endpoints
- Payment endpoints
- User endpoints
- Wishlist endpoints
- **Public Product API Resource** (5 CRUD operations)

### Admin Authenticated Endpoints (70+)
- Logout
- List endpoints (get-attribute-list, get-supplier-list, etc.)
- **11 API Resources × 5 CRUD operations = 55 endpoints**:
  - product (GET, POST, GET/{id}, PUT/{id}, DELETE/{id})
  - category
  - sub-category
  - brand
  - supplier
  - attribute
  - attribute-value
  - photo
  - shop
  - customer
  - order
- Additional endpoints (product-photo-upload, products/{id}, etc.)
- **Transfers endpoints** (5 operations):
  - GET /transfers (index)
  - POST /transfers (store)
  - GET /transfers/{id} (show)
  - PUT /transfers/{id}/approve
  - PUT /transfers/{id}/reject

### Sales Manager Authenticated Endpoints (85+)
- Logout
- List endpoints
- **14 API Resources × 5 CRUD operations = 70 endpoints**:
  - sales-manager
  - product
  - category
  - sub-category
  - child-sub-category
  - brand
  - formula
  - supplier
  - attribute
  - attribute-value
  - photo
  - shop
  - customer
  - order
- Additional endpoints
- **Transfers endpoints** (5 operations)

## Total Estimated Endpoints
- **Public**: ~30 endpoints
- **Admin**: ~70 endpoints
- **Sales Manager**: ~85 endpoints
- **Grand Total**: ~185 endpoints

## What Changed

### Before
- Only tested GET requests for some resources
- Missing POST, PUT, DELETE operations
- Missing many individual endpoints
- Total: ~25 endpoints tested

### After
- Tests ALL CRUD operations (GET, POST, PUT, DELETE) for all resources
- Tests all individual endpoints
- Tests all transfer operations
- Total: ~185 endpoints tested

## Running the Tests

```bash
# Test public endpoints only
php api_check/test_all_apis.php

# Test with admin authentication (tests all admin endpoints)
php api_check/test_all_apis.php --admin-email=admin@example.com --admin-password=password

# Test with sales manager authentication (tests all sales manager endpoints)
php api_check/test_all_apis.php --admin-email=sales@example.com --admin-password=password
```

## Note
Many endpoints require authentication. To get a complete picture, run the script with valid admin or sales manager credentials.




