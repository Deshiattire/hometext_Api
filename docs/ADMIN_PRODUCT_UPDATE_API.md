# Admin Product Update API Documentation

## Overview
This document provides complete technical specifications for the Product Update API, designed for frontend admin panel integration.

---

## Endpoint

```
PUT /api/products/{product_id}
```

**Alternative endpoint (both work):**
```
PUT /api/product/{product_id}
```

---

## Authentication

| Header | Value |
|--------|-------|
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |
| `Authorization` | `Bearer {token}` |

**Required Role:** Admin (`user_type: 1`) or Sales Manager (`user_type: 2`)

---

## Request Structure

### Important Notes:
1. **Partial Updates Supported** - Only send fields you want to update
2. **All fields are optional** - The API will only update provided fields
3. **Existing data preserved** - Omitted fields retain their current values

---

## Complete Field Reference

### 1. Basic Information

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `name` | string | min:3, max:255 | Product name |
| `slug` | string | min:3, max:255, unique | URL-friendly identifier |
| `sku` | string | min:3, max:255, unique | Stock Keeping Unit |
| `description` | string | max:5000 | Full product description |
| `short_description` | string | max:500 | Brief summary |

**Example:**
```json
{
  "name": "Premium Cotton Bedsheet Set",
  "slug": "premium-cotton-bedsheet-set",
  "sku": "BED-COT-001",
  "description": "High-quality 100% cotton bedsheet with 400 thread count...",
  "short_description": "Luxury cotton bedsheet for ultimate comfort"
}
```

---

### 2. Category & Relations

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `category_id` | number | nullable, exists:categories | Main category ID |
| `sub_category_id` | number | nullable, exists:sub_categories | Sub-category ID |
| `child_sub_category_id` | number | nullable, exists:child_sub_categories | Child sub-category ID |
| `brand_id` | number | nullable, exists:brands | Brand ID |
| `supplier_id` | number | nullable, exists:suppliers | Supplier ID |
| `country_id` | number | nullable, exists:countries | Country of origin ID |

**Example:**
```json
{
  "category_id": 5,
  "sub_category_id": 12,
  "child_sub_category_id": 24,
  "brand_id": 3,
  "supplier_id": 7,
  "country_id": 1
}
```

**Note:** Use these helper endpoints to get valid IDs:
- `GET /api/get-category-list`
- `GET /api/get-sub-category-list`
- `GET /api/get-child-sub-category-list`
- `GET /api/get-brand-list`
- `GET /api/get-supplier-list`
- `GET /api/get-country-list`

---

### 3. Pricing

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `cost` | number | min:0 | Cost/purchase price |
| `price` | number | min:0 | Selling price |
| `price_formula` | string | nullable, max:255 | Custom price formula |
| `field_limit` | string | nullable, max:255 | Field limit value |

**Example:**
```json
{
  "cost": 500.00,
  "price": 899.99,
  "price_formula": null,
  "field_limit": null
}
```

---

### 4. Discounts

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `discount_fixed` | number | nullable, min:0 | Fixed discount amount |
| `discount_percent` | number | nullable, min:0, max:100 | Percentage discount |
| `discount_start` | string | nullable, format: YYYY-MM-DD | Discount start date |
| `discount_end` | string | nullable, format: YYYY-MM-DD | Discount end date |

**Rules:**
- `discount_end` must be ≥ `discount_start`
- `discount_percent` cannot exceed 100

**Example (Percentage Discount):**
```json
{
  "discount_percent": 15,
  "discount_start": "2026-01-17",
  "discount_end": "2026-02-17"
}
```

**Example (Fixed Discount):**
```json
{
  "discount_fixed": 100,
  "discount_start": "2026-01-17",
  "discount_end": "2026-01-31"
}
```

**Example (Remove Discount):**
```json
{
  "discount_percent": null,
  "discount_fixed": null,
  "discount_start": null,
  "discount_end": null
}
```

---

### 5. Stock Management

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `stock` | number | min:0 | Available stock quantity |
| `stock_status` | string | in_stock, out_of_stock, on_backorder, preorder | Stock status |
| `low_stock_threshold` | number | nullable, min:0 | Alert threshold |
| `manage_stock` | boolean | true/false | Enable stock management |
| `allow_backorders` | boolean | true/false | Allow backorders |

**Stock Status Values:**
| Value | Description |
|-------|-------------|
| `in_stock` | Product is available |
| `out_of_stock` | Product is not available |
| `on_backorder` | Available for backorder |
| `preorder` | Available for pre-order |

**Example:**
```json
{
  "stock": 150,
  "stock_status": "in_stock",
  "low_stock_threshold": 10,
  "manage_stock": true,
  "allow_backorders": false
}
```

---

### 6. Status & Visibility

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `status` | number | 0 or 1 | 0=Inactive, 1=Active |
| `visibility` | string | visible, catalog, search, hidden | Product visibility |

**Visibility Values:**
| Value | Description |
|-------|-------------|
| `visible` | Visible everywhere |
| `catalog` | Only in catalog listings |
| `search` | Only in search results |
| `hidden` | Not visible anywhere |

**Example:**
```json
{
  "status": 1,
  "visibility": "visible"
}
```

---

### 7. Featured Flags

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `isFeatured` | number | 0 or 1 | Featured product |
| `isNew` | number | 0 or 1 | New arrival |
| `isTrending` | number | 0 or 1 | Trending product |
| `is_bestseller` | boolean | true/false | Best seller |
| `is_limited_edition` | boolean | true/false | Limited edition |
| `is_exclusive` | boolean | true/false | Exclusive product |
| `is_eco_friendly` | boolean | true/false | Eco-friendly product |

**Example:**
```json
{
  "isFeatured": 1,
  "isNew": 1,
  "isTrending": 0,
  "is_bestseller": true,
  "is_limited_edition": false,
  "is_exclusive": false,
  "is_eco_friendly": true
}
```

---

### 8. Shipping & Dimensions

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `free_shipping` | boolean | true/false | Free shipping offered |
| `express_available` | boolean | true/false | Express shipping available |
| `weight` | number | nullable, min:0 | Weight in kg |
| `length` | number | nullable, min:0 | Length in cm |
| `width` | number | nullable, min:0 | Width in cm |
| `height` | number | nullable, min:0 | Height in cm |

**Example:**
```json
{
  "free_shipping": true,
  "express_available": true,
  "weight": 1.5,
  "length": 50,
  "width": 40,
  "height": 10
}
```

---

### 9. Tax & Policies

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `tax_rate` | number | nullable, min:0, max:100 | Tax rate percentage |
| `tax_included` | boolean | true/false | Price includes tax |
| `has_warranty` | boolean | true/false | Product has warranty |
| `warranty_duration_months` | number | nullable, min:0 | Warranty duration |
| `returnable` | boolean | true/false | Product is returnable |
| `return_period_days` | number | nullable, min:0 | Return period in days |

**Example:**
```json
{
  "tax_rate": 5,
  "tax_included": false,
  "has_warranty": true,
  "warranty_duration_months": 12,
  "returnable": true,
  "return_period_days": 7
}
```

---

### 10. Product Attributes

Attributes define product variations like Color, Size, Material.

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `attributes` | array | - | Array of attribute objects |
| `attributes.*.attribute_id` | number | required, exists:attributes | Attribute type ID |
| `attributes.*.attribute_value_id` | number | required, exists:attribute_values | Attribute value ID |

**Example:**
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "attribute_value_id": 5
    },
    {
      "attribute_id": 2,
      "attribute_value_id": 12
    }
  ]
}
```

**Get available attributes:**
- `GET /api/get-attribute-list`

---

### 11. Product Specifications

Specifications are key-value pairs for product details.

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `specifications` | array | - | Array of specification objects |
| `specifications.*.name` | string | required, max:255 | Specification name |
| `specifications.*.value` | string | required, max:1000 | Specification value |

**Example:**
```json
{
  "specifications": [
    {
      "name": "Material",
      "value": "100% Egyptian Cotton"
    },
    {
      "name": "Thread Count",
      "value": "400"
    },
    {
      "name": "Care Instructions",
      "value": "Machine wash cold, tumble dry low"
    },
    {
      "name": "Country of Manufacture",
      "value": "Bangladesh"
    }
  ]
}
```

---

### 12. SEO Metadata

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `meta` | object | - | SEO metadata object |
| `meta.meta_title` | string | nullable, max:255 | SEO title |
| `meta.meta_description` | string | nullable, max:500 | SEO description |
| `meta.meta_keywords` | string | nullable, max:500 | SEO keywords |

**Example:**
```json
{
  "meta": {
    "meta_title": "Premium Cotton Bedsheet Set | Hometex Bangladesh",
    "meta_description": "Buy premium 400 thread count cotton bedsheet set. Free shipping on orders over 2000 BDT.",
    "meta_keywords": "bedsheet, cotton bedsheet, premium bedsheet, hometex"
  }
}
```

---

### 13. Shop Quantities (Multi-Shop Inventory)

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `shop_quantities` | array | - | Array of shop quantity objects |
| `shop_quantities.*.shop_id` | number | required, exists:shops | Shop ID |
| `shop_quantities.*.quantity` | number | required, min:0 | Quantity at shop |

**Example:**
```json
{
  "shop_quantities": [
    {
      "shop_id": 1,
      "quantity": 50
    },
    {
      "shop_id": 2,
      "quantity": 75
    },
    {
      "shop_id": 3,
      "quantity": 25
    }
  ]
}
```

**Get available shops:**
- `GET /api/get-shop-list`

---

## Response Format

### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "product": {
      "id": 123,
      "sku": "BED-COT-001",
      "name": "Premium Cotton Bedsheet Set",
      "slug": "premium-cotton-bedsheet-set",
      "description": "High-quality 100% cotton bedsheet...",
      "short_description": "Luxury cotton bedsheet",
      "status": "active",
      "visibility": "visible",
      "type": "simple",
      
      "category": {
        "id": 5,
        "name": "Bedding",
        "slug": "bedding",
        "level": 1
      },
      "sub_category": {
        "id": 12,
        "name": "Bedsheets",
        "slug": "bedsheets",
        "level": 2
      },
      "child_sub_category": {
        "id": 24,
        "name": "Cotton Bedsheets",
        "slug": "cotton-bedsheets",
        "level": 3
      },
      
      "brand": {
        "id": 3,
        "name": "Hometex",
        "slug": "hometex",
        "logo": "https://example.com/brands/hometex.png"
      },
      
      "pricing": {
        "currency": "BDT",
        "currency_symbol": "৳",
        "cost_price": 500.00,
        "regular_price": 899.99,
        "sale_price": 764.99,
        "final_price": 764.99,
        "discount": {
          "type": "percentage",
          "percentage": 15,
          "fixed_amount": null,
          "savings": 135.00,
          "start_date": "2026-01-17",
          "end_date": "2026-02-17",
          "is_active": true,
          "remaining_days": 30
        }
      },
      
      "stock": {
        "quantity": 150,
        "status": "in_stock",
        "low_stock_threshold": 10,
        "is_low_stock": false,
        "manage_stock": true,
        "allow_backorders": false,
        "availability_text": "In Stock"
      },
      
      "attributes": [
        {
          "attribute": {
            "id": 1,
            "name": "Color"
          },
          "value": {
            "id": 5,
            "name": "White"
          }
        },
        {
          "attribute": {
            "id": 2,
            "name": "Size"
          },
          "value": {
            "id": 12,
            "name": "King"
          }
        }
      ],
      
      "specifications": [
        {
          "name": "Material",
          "value": "100% Egyptian Cotton"
        },
        {
          "name": "Thread Count",
          "value": "400"
        }
      ],
      
      "seo": {
        "meta_title": "Premium Cotton Bedsheet Set | Hometex Bangladesh",
        "meta_description": "Buy premium 400 thread count cotton bedsheet set...",
        "meta_keywords": "bedsheet, cotton bedsheet, premium bedsheet"
      },
      
      "shops": [
        {
          "id": 1,
          "name": "Main Warehouse",
          "quantity": 50
        },
        {
          "id": 2,
          "name": "Dhaka Store",
          "quantity": 75
        }
      ],
      
      "timestamps": {
        "created_at": "2025-06-15T10:30:00Z",
        "updated_at": "2026-01-17T14:25:30Z"
      }
    },
    "updated_fields": [
      "name",
      "price",
      "discount_percent",
      "stock",
      "updated_by_id"
    ]
  },
  "meta": {
    "request_id": "req_abc123xyz",
    "timestamp": "2026-01-17T14:25:30Z",
    "response_time_ms": 245.32
  }
}
```

---

### Validation Error Response (HTTP 422)

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
    ],
    "discount_end": [
      "Discount end date must be after or equal to start date."
    ],
    "slug": [
      "This slug is already taken by another product."
    ]
  }
}
```

---

### Not Found Response (HTTP 404)

```json
{
  "success": false,
  "message": "Product not found",
  "errors": null
}
```

---

### Server Error Response (HTTP 500)

```json
{
  "success": false,
  "message": "Failed to update product",
  "errors": "An error occurred while updating the product"
}
```

---

## Complete Request Examples

### Example 1: Basic Price & Stock Update

```http
PUT /api/products/123
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

{
  "price": 999.99,
  "stock": 200
}
```

---

### Example 2: Full Product Update

```http
PUT /api/products/123
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

{
  "name": "Premium Cotton Bedsheet Set - Updated",
  "slug": "premium-cotton-bedsheet-set-v2",
  "sku": "BED-COT-002",
  "description": "Updated description with more details...",
  "short_description": "Updated luxury cotton bedsheet",
  
  "category_id": 5,
  "sub_category_id": 12,
  "child_sub_category_id": 24,
  "brand_id": 3,
  "supplier_id": 7,
  "country_id": 1,
  
  "cost": 550.00,
  "price": 999.99,
  
  "discount_percent": 20,
  "discount_start": "2026-01-17",
  "discount_end": "2026-02-28",
  
  "stock": 200,
  "stock_status": "in_stock",
  "low_stock_threshold": 15,
  "manage_stock": true,
  "allow_backorders": false,
  
  "status": 1,
  "visibility": "visible",
  
  "isFeatured": 1,
  "isNew": 1,
  "isTrending": 1,
  "is_bestseller": true,
  "is_limited_edition": false,
  "is_exclusive": false,
  "is_eco_friendly": true,
  
  "free_shipping": true,
  "express_available": true,
  "weight": 1.5,
  "length": 50,
  "width": 40,
  "height": 10,
  
  "tax_rate": 5,
  "tax_included": false,
  "has_warranty": true,
  "warranty_duration_months": 12,
  "returnable": true,
  "return_period_days": 7,
  
  "attributes": [
    { "attribute_id": 1, "attribute_value_id": 5 },
    { "attribute_id": 2, "attribute_value_id": 12 }
  ],
  
  "specifications": [
    { "name": "Material", "value": "100% Egyptian Cotton" },
    { "name": "Thread Count", "value": "400" }
  ],
  
  "meta": {
    "meta_title": "Premium Cotton Bedsheet Set | Hometex",
    "meta_description": "Buy premium cotton bedsheet with free shipping",
    "meta_keywords": "bedsheet, cotton, premium, hometex"
  },
  
  "shop_quantities": [
    { "shop_id": 1, "quantity": 100 },
    { "shop_id": 2, "quantity": 100 }
  ]
}
```

---

### Example 3: Toggle Product Status

```http
PUT /api/products/123
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

{
  "status": 0
}
```

---

### Example 4: Set Product as Featured

```http
PUT /api/products/123
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

{
  "isFeatured": 1,
  "isTrending": 1,
  "is_bestseller": true
}
```

---

### Example 5: Update Only SEO

```http
PUT /api/products/123
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

{
  "meta": {
    "meta_title": "New SEO Title | Hometex",
    "meta_description": "Updated meta description for better SEO",
    "meta_keywords": "keyword1, keyword2, keyword3"
  }
}
```

---

## Helper Endpoints for Dropdowns

These endpoints provide data for form dropdowns:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/get-category-list` | GET | List of categories |
| `/api/get-sub-category-list` | GET | List of all sub-categories |
| `/api/get-sub-category-list/{category_id}` | GET | Sub-categories for specific category |
| `/api/get-child-sub-category-list` | GET | List of all child sub-categories |
| `/api/get-child-sub-category-list/{category_id}` | GET | Child sub-categories for specific category |
| `/api/get-brand-list` | GET | List of brands |
| `/api/get-supplier-list` | GET | List of suppliers |
| `/api/get-country-list` | GET | List of countries |
| `/api/get-attribute-list` | GET | List of attributes with values |
| `/api/get-shop-list` | GET | List of shops |

---

## Frontend Implementation Tips

### 1. Form State Management
```javascript
// Only include changed fields in the update request
const changedFields = {};
Object.keys(formData).forEach(key => {
  if (formData[key] !== originalData[key]) {
    changedFields[key] = formData[key];
  }
});
```

### 2. Date Formatting
```javascript
// Format dates as YYYY-MM-DD
const formatDate = (date) => {
  return date.toISOString().split('T')[0];
};
```

### 3. Boolean Fields
```javascript
// Some fields accept 0/1, others accept true/false
const payload = {
  status: isActive ? 1 : 0,           // Use 0/1
  isFeatured: isFeatured ? 1 : 0,     // Use 0/1
  is_bestseller: isBestseller,         // Use true/false
  free_shipping: hasFreeShipping       // Use true/false
};
```

### 4. Error Handling
```javascript
try {
  const response = await fetch(`/api/products/${productId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(updateData)
  });
  
  const result = await response.json();
  
  if (!response.ok) {
    if (response.status === 422) {
      // Handle validation errors
      Object.entries(result.errors).forEach(([field, messages]) => {
        setFieldError(field, messages[0]);
      });
    } else if (response.status === 404) {
      showError('Product not found');
    } else {
      showError(result.message || 'Update failed');
    }
    return;
  }
  
  showSuccess('Product updated successfully');
  // result.data.updated_fields contains list of updated fields
  
} catch (error) {
  showError('Network error. Please try again.');
}
```

---

## Notes

1. **Unique Constraints:** `sku` and `slug` must be unique (excluding current product)
2. **Auto-Updates:** `updated_by_id` is automatically set to authenticated user
3. **Cache Clearing:** Product caches are automatically cleared after update
4. **Transaction Safety:** All updates wrapped in database transactions
5. **Logging:** All errors are logged for debugging

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-17 | Initial documentation |
