# Product Update API Documentation

## Overview
The Product Update API allows you to update existing products with partial updates - only the fields you send will be updated, leaving other fields unchanged.

## Endpoint
```
PUT /api/product/{id}
```

## Authentication
Requires authentication token (Bearer token) from either:
- Admin (user_type: 1)
- Sales Manager (user_type: 2)

## Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_token}
```

## Request Body
All fields are **optional** - only include fields you want to update.

### Basic Information
```json
{
  "name": "string (min:3, max:255)",
  "slug": "string (min:3, max:255, unique)",
  "sku": "string (min:3, max:255, unique)",
  "description": "string (max:5000)",
  "short_description": "string (max:500)"
}
```

### Categories & Relations
```json
{
  "category_id": "number (nullable, exists:categories)",
  "sub_category_id": "number (nullable, exists:sub_categories)",
  "child_sub_category_id": "number (nullable, exists:child_sub_categories)",
  "brand_id": "number (nullable, exists:brands)",
  "supplier_id": "number (nullable, exists:suppliers)",
  "country_id": "number (nullable, exists:countries)"
}
```

### Pricing
```json
{
  "cost": "number (min:0)",
  "price": "number (min:0)",
  "price_formula": "string (nullable)",
  "field_limit": "string (nullable)"
}
```

### Discounts
```json
{
  "discount_fixed": "number (nullable, min:0)",
  "discount_percent": "number (nullable, min:0, max:100)",
  "discount_start": "date (nullable, format: Y-m-d)",
  "discount_end": "date (nullable, must be >= discount_start)"
}
```

### Stock Management
```json
{
  "stock": "number (min:0)",
  "stock_status": "string (in_stock|out_of_stock|on_backorder|preorder)",
  "low_stock_threshold": "number (nullable, min:0)",
  "manage_stock": "boolean",
  "allow_backorders": "boolean"
}
```

### Status & Visibility
```json
{
  "status": "number (0|1) - 0:Inactive, 1:Active",
  "visibility": "string (visible|catalog|search|hidden)"
}
```

### Featured Flags
```json
{
  "isFeatured": "number (0|1)",
  "isNew": "number (0|1)",
  "isTrending": "number (0|1)",
  "is_bestseller": "boolean",
  "is_limited_edition": "boolean",
  "is_exclusive": "boolean",
  "is_eco_friendly": "boolean"
}
```

### Shipping & Dimensions
```json
{
  "free_shipping": "boolean",
  "express_available": "boolean",
  "weight": "number (nullable, min:0) - in kg",
  "length": "number (nullable, min:0) - in cm",
  "width": "number (nullable, min:0) - in cm",
  "height": "number (nullable, min:0) - in cm"
}
```

### Tax & Policies
```json
{
  "tax_rate": "number (nullable, min:0, max:100)",
  "tax_included": "boolean",
  "has_warranty": "boolean",
  "warranty_duration_months": "number (nullable, min:0)",
  "returnable": "boolean",
  "return_period_days": "number (nullable, min:0)"
}
```

### Attributes
```json
{
  "attributes": [
    {
      "attribute_id": "number (required, exists:attributes)",
      "attribute_value_id": "number (required, exists:attribute_values)"
    }
  ]
}
```

### Specifications
```json
{
  "specifications": [
    {
      "name": "string (required, max:255)",
      "value": "string (required, max:1000)"
    }
  ]
}
```

### SEO Metadata
```json
{
  "meta": {
    "meta_title": "string (nullable, max:255)",
    "meta_description": "string (nullable, max:500)",
    "meta_keywords": "string (nullable, max:500)"
  }
}
```

### Shop Quantities
```json
{
  "shop_quantities": [
    {
      "shop_id": "number (required, exists:shops)",
      "quantity": "number (required, min:0)"
    }
  ]
}
```

## Response Format

### Success Response (200)
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "Updated Product Name",
      "slug": "updated-product-name",
      "sku": "PROD-001",
      "price": 1299.99,
      "stock": 150,
      "status": 1,
      "category": {
        "id": 1,
        "name": "Electronics"
      },
      "brand": {
        "id": 5,
        "name": "Samsung"
      },
      // ... other product fields and relationships
    },
    "updated_fields": [
      "name",
      "price",
      "stock",
      "updated_by_id"
    ]
  },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2025-12-26T10:30:45Z",
    "response_time_ms": 245.32
  }
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "price": [
      "The price must be at least 0."
    ],
    "discount_percent": [
      "The discount percent may not be greater than 100."
    ]
  }
}
```

### Error Response (500)
```json
{
  "success": false,
  "message": "Failed to update product",
  "errors": "An error occurred while updating the product"
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product not found"
}
```

## Usage Examples

### Example 1: Update Only Price
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "price": 1299.99
  }'
```

### Example 2: Update Multiple Fields
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Premium Cotton T-Shirt",
    "price": 899.99,
    "stock": 150,
    "status": 1
  }'
```

### Example 3: Add Discount
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "discount_percent": 20,
    "discount_start": "2025-12-26",
    "discount_end": "2026-01-25"
  }'
```

### Example 4: Update Attributes and Specifications
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "attributes": [
      {
        "attribute_id": 1,
        "attribute_value_id": 5
      },
      {
        "attribute_id": 2,
        "attribute_value_id": 10
      }
    ],
    "specifications": [
      {
        "name": "Material",
        "value": "100% Cotton"
      },
      {
        "name": "Care Instructions",
        "value": "Machine wash cold"
      }
    ]
  }'
```

### Example 5: Update Featured Status
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "isFeatured": 1,
    "isTrending": 1,
    "is_bestseller": true
  }'
```

### Example 6: Update Shop Quantities
```bash
curl -X PUT http://127.0.0.1:8000/api/product/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "shop_quantities": [
      {
        "shop_id": 1,
        "quantity": 50
      },
      {
        "shop_id": 2,
        "quantity": 75
      }
    ]
  }'
```

## Key Features

1. **Partial Updates**: Only send fields you want to update
2. **Validation**: All fields are validated according to their rules
3. **Unique Checks**: SKU and slug uniqueness is verified (excluding current product)
4. **Relationships**: Update related data like attributes, specifications, and SEO metadata
5. **Cache Clearing**: Product filter caches are automatically cleared after update
6. **Transaction Safety**: Uses database transactions for data integrity
7. **Detailed Logging**: Errors are logged with full context for debugging
8. **Standardized Response**: Consistent JSON response format with meta information
9. **Updated Fields Tracking**: Response includes list of fields that were actually updated

## Notes

- All numeric IDs must exist in their respective tables (validated by `exists` rule)
- Dates must be in `Y-m-d` format (e.g., "2025-12-26")
- Boolean fields accept both `true/false` and `1/0`
- Discount end date must be after or equal to start date
- Discount percentage cannot exceed 100%
- The `updated_by_id` field is automatically set to the authenticated user's ID
- Product filter caches are cleared automatically after successful update
- All updates are wrapped in database transactions for safety

## Testing

Use the provided test script to see the API in action:
```bash
php test_product_update_api.php
```

## Route Definition

The route is already defined in your `routes/api.php`:
```php
Route::put('/products/{product}', [ProductController::class, 'update']);
```

This route is protected by both `admin` and `sales_manager` middleware, so both user types can update products.
