# Product Update API - Frontend Implementation Guide

## 1. Update Endpoint Details

### Base Endpoint
```
PUT/PATCH /api/products/{id}
```

### Authentication
**Required:** Bearer Token (Sanctum)
- User must have `update-products` permission
- Typically: Admin or Sales Manager roles

### Request Headers
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Example cURL
```bash
curl -X PUT http://localhost:8000/api/products/123 \
  -H "Authorization: Bearer {your_token}" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Product Name", "price": 299.99}'
```

---

## 2. Request Payload Structure

### Complete Field List
All fields are **optional** - send only the fields you want to update.

```typescript
interface ProductUpdatePayload {
  // Basic Information
  name?: string;                    // 3-255 chars
  slug?: string;                    // unique, auto-generated if not provided
  description?: string;             // optional
  short_description?: string;       // max 500 chars
  
  // Pricing
  price?: number;                   // min: 0
  sale_price?: number;              // min: 0, must be less than price
  cost_price?: number;              // min: 0
  
  // Categorization
  category_id?: number;             // exists in categories table
  sub_category_id?: number;         // exists in sub_categories, nullable
  child_sub_category_id?: number;   // exists in child_sub_categories, nullable
  brand_id?: number;                // exists in brands table, nullable
  
  // Inventory
  stock_quantity?: number;          // min: 0
  sku?: string;                     // unique, max 100 chars
  stock_status?: 'in_stock' | 'out_of_stock' | 'pre_order';
  
  // Product Details
  unit?: string;                    // e.g., "piece", "kg", "meter"
  weight?: number;                  // in kg, nullable
  dimensions?: string;              // e.g., "10x20x30 cm"
  material?: string;                // product material
  color?: string;                   // primary color
  size?: string;                    // size information
  
  // Status & Visibility
  status?: 'active' | 'inactive' | 'draft';
  is_featured?: boolean;            // 0 or 1
  is_new_arrival?: boolean;         // 0 or 1
  is_best_seller?: boolean;         // 0 or 1
  is_on_sale?: boolean;             // 0 or 1
  
  // SEO
  meta_title?: string;              // max 255 chars
  meta_description?: string;        // max 500 chars
  meta_keywords?: string;           // max 500 chars
  
  // Media
  thumbnail?: string;               // image URL or path
  images?: string[];                // array of image URLs
  video_url?: string;               // YouTube/Vimeo URL
  
  // Shipping
  shipping_class?: string;
  shipping_cost?: number;           // min: 0
  
  // Advanced
  warranty_info?: string;
  return_policy?: string;
  manufacturer?: string;
  country_of_origin?: string;
  
  // Attributes (for variations)
  attributes?: Array<{
    attribute_id: number;
    value: string;
  }>;
  
  // Specifications (detailed product specs)
  specifications?: Array<{
    key: string;
    value: string;
  }>;
  
  // Related Products
  related_product_ids?: number[];   // array of product IDs
  
  // Tags
  tag_ids?: number[];               // array of tag IDs
}
```

---

## 3. Separate Endpoints?

**No separate endpoints needed.** The API uses a **single flexible endpoint** that handles:
- Partial updates (update only specific fields)
- Full updates (update all fields)
- Nested object updates (attributes, specifications)

**All update scenarios use:** `PUT/PATCH /api/products/{id}`

---

## 4. Field Constraints

### Required vs Optional
- **All fields are optional** during updates
- Send only the fields you want to change
- Existing values remain unchanged for omitted fields

### Read-Only Fields
These are **automatically managed** by the system:
- `id` - Primary key
- `created_at` - Creation timestamp
- `updated_at` - Auto-updated on save
- `created_by` - Set during creation only
- `updated_by` - Auto-updated on save

### Validation Rules

#### String Fields
```typescript
name: {
  required: false,
  minLength: 3,
  maxLength: 255
}

slug: {
  required: false,
  unique: true, // except current product
  pattern: /^[a-z0-9-]+$/
}

sku: {
  required: false,
  unique: true, // except current product
  maxLength: 100
}
```

#### Numeric Fields
```typescript
price: {
  required: false,
  min: 0,
  type: 'decimal'
}

sale_price: {
  required: false,
  min: 0,
  max: price, // must be less than regular price
  type: 'decimal'
}

stock_quantity: {
  required: false,
  min: 0,
  type: 'integer'
}
```

#### Enum Fields
```typescript
status: ['active', 'inactive', 'draft']
stock_status: ['in_stock', 'out_of_stock', 'pre_order']
```

#### Boolean Fields
```typescript
is_featured: 0 | 1 | true | false
is_new_arrival: 0 | 1 | true | false
is_best_seller: 0 | 1 | true | false
is_on_sale: 0 | 1 | true | false
```

#### Foreign Keys
```typescript
category_id: {
  exists: 'categories.id'
}

brand_id: {
  exists: 'brands.id',
  nullable: true
}
```

---

## 5. Nested Object Handling

### Attributes (for product variations)

**Structure:**
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "value": "Red"
    },
    {
      "attribute_id": 2,
      "value": "Large"
    }
  ]
}
```

**How it works:**
- Completely **replaces** existing attributes
- To add: Include new + existing attributes
- To remove: Omit from array
- To update: Send all with new values

**Example - Update Color Only:**
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "value": "Blue"  // Changed from Red
    },
    {
      "attribute_id": 2,
      "value": "Large"  // Keep existing
    }
  ]
}
```

### Specifications (detailed product specs)

**Structure:**
```json
{
  "specifications": [
    {
      "key": "Material",
      "value": "Cotton 100%"
    },
    {
      "key": "Care Instructions",
      "value": "Machine washable"
    }
  ]
}
```

**How it works:**
- Completely **replaces** existing specifications
- Each spec has a key-value pair
- To remove: Omit from array
- To add: Include in array

### Images (multiple product images)

**Structure:**
```json
{
  "images": [
    "/images/products/product-1.jpg",
    "/images/products/product-2.jpg",
    "/images/products/product-3.jpg"
  ]
}
```

**How it works:**
- Array of image URLs/paths
- Order matters (first = primary in gallery)
- Completely replaces existing images

### Related Products

**Structure:**
```json
{
  "related_product_ids": [45, 67, 89, 123]
}
```

**How it works:**
- Array of product IDs
- Replaces existing related products
- Products must exist in database

### Tags

**Structure:**
```json
{
  "tag_ids": [1, 5, 12, 18]
}
```

**How it works:**
- Array of tag IDs
- Replaces existing tags
- Tags must exist in database

---

## 6. Response Format

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "product": {
      "id": 123,
      "name": "Updated Product Name",
      "slug": "updated-product-name",
      "description": "Product description...",
      "price": 299.99,
      "sale_price": 249.99,
      "stock_quantity": 50,
      "status": "active",
      "is_featured": true,
      "thumbnail": "/images/products/thumbnail.jpg",
      "images": [...],
      "category": {
        "id": 5,
        "name": "Electronics",
        "slug": "electronics"
      },
      "brand": {
        "id": 10,
        "name": "Samsung",
        "logo": "/images/brands/samsung.png"
      },
      "attributes": [
        {
          "id": 1,
          "attribute_id": 1,
          "value": "Red",
          "attribute": {
            "id": 1,
            "name": "Color"
          }
        }
      ],
      "specifications": [
        {
          "id": 1,
          "key": "Material",
          "value": "Cotton 100%"
        }
      ],
      "tags": [
        {
          "id": 1,
          "name": "New Arrival",
          "slug": "new-arrival"
        }
      ],
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-20T14:45:00.000000Z"
    },
    "updated_fields": ["name", "price", "sale_price"],
    "updates_count": 3
  }
}
```

### Error Response (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": [
      "The name must be at least 3 characters."
    ],
    "sale_price": [
      "The sale price must be less than the regular price."
    ],
    "category_id": [
      "The selected category does not exist."
    ]
  }
}
```

### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Product not found",
  "error": "No product exists with ID: 999"
}
```

### Error Response (401 Unauthorized)

```json
{
  "message": "Unauthenticated."
}
```

### Error Response (403 Forbidden)

```json
{
  "success": false,
  "message": "You do not have permission to update products"
}
```

---

## 7. Frontend Implementation Examples

### React/Next.js Example

```typescript
// types/product.ts
export interface ProductUpdateData {
  name?: string;
  price?: number;
  sale_price?: number;
  stock_quantity?: number;
  status?: 'active' | 'inactive' | 'draft';
  // ... other fields
}

// api/products.ts
export async function updateProduct(
  productId: number,
  data: ProductUpdateData,
  token: string
) {
  const response = await fetch(`http://localhost:8000/api/products/${productId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data)
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to update product');
  }

  return response.json();
}

// components/ProductEditForm.tsx
import { useState } from 'react';
import { updateProduct } from '@/api/products';

export default function ProductEditForm({ product, token }) {
  const [formData, setFormData] = useState({
    name: product.name,
    price: product.price,
    sale_price: product.sale_price,
    stock_quantity: product.stock_quantity,
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      // Only send changed fields
      const changedData = {};
      Object.keys(formData).forEach(key => {
        if (formData[key] !== product[key]) {
          changedData[key] = formData[key];
        }
      });

      const result = await updateProduct(product.id, changedData, token);
      console.log('Updated:', result.data.product);
      alert('Product updated successfully!');
    } catch (error) {
      setErrors(error.errors || {});
      alert('Failed to update product');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Basic Information */}
      <div>
        <label>Product Name</label>
        <input
          type="text"
          value={formData.name}
          onChange={(e) => setFormData({...formData, name: e.target.value})}
        />
        {errors.name && <span className="error">{errors.name[0]}</span>}
      </div>

      <div>
        <label>Price</label>
        <input
          type="number"
          step="0.01"
          value={formData.price}
          onChange={(e) => setFormData({...formData, price: parseFloat(e.target.value)})}
        />
        {errors.price && <span className="error">{errors.price[0]}</span>}
      </div>

      <div>
        <label>Sale Price</label>
        <input
          type="number"
          step="0.01"
          value={formData.sale_price}
          onChange={(e) => setFormData({...formData, sale_price: parseFloat(e.target.value)})}
        />
        {errors.sale_price && <span className="error">{errors.sale_price[0]}</span>}
      </div>

      <button type="submit" disabled={loading}>
        {loading ? 'Updating...' : 'Update Product'}
      </button>
    </form>
  );
}
```

### Axios Example

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Set auth token
api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// Update product
async function updateProduct(id, data) {
  try {
    const response = await api.put(`/products/${id}`, data);
    return response.data;
  } catch (error) {
    if (error.response) {
      // Validation errors
      throw error.response.data;
    }
    throw error;
  }
}

// Example usage
const result = await updateProduct(123, {
  name: 'New Product Name',
  price: 299.99,
  is_featured: true
});

console.log('Updated product:', result.data.product);
console.log('Fields updated:', result.data.updated_fields);
```

---

## 8. Common Update Scenarios

### Scenario 1: Update Basic Information Only
```json
PUT /api/products/123

{
  "name": "Premium Cotton T-Shirt",
  "short_description": "Comfortable and stylish",
  "description": "High-quality cotton t-shirt perfect for everyday wear..."
}
```

### Scenario 2: Update Pricing
```json
PUT /api/products/123

{
  "price": 499.99,
  "sale_price": 399.99,
  "is_on_sale": true
}
```

### Scenario 3: Update Inventory
```json
PUT /api/products/123

{
  "stock_quantity": 150,
  "stock_status": "in_stock",
  "sku": "PRD-2024-001"
}
```

### Scenario 4: Update SEO
```json
PUT /api/products/123

{
  "meta_title": "Buy Premium Cotton T-Shirt Online | Best Price",
  "meta_description": "Shop premium cotton t-shirts at amazing prices...",
  "meta_keywords": "cotton t-shirt, premium tshirt, comfortable clothing"
}
```

### Scenario 5: Update Attributes
```json
PUT /api/products/123

{
  "attributes": [
    {
      "attribute_id": 1,
      "value": "Blue"
    },
    {
      "attribute_id": 2,
      "value": "XL"
    }
  ]
}
```

### Scenario 6: Toggle Feature Flags
```json
PUT /api/products/123

{
  "is_featured": false,
  "is_new_arrival": true,
  "is_best_seller": true
}
```

### Scenario 7: Full Update (All Fields)
```json
PUT /api/products/123

{
  "name": "Premium Cotton T-Shirt",
  "slug": "premium-cotton-tshirt",
  "description": "High-quality cotton...",
  "price": 499.99,
  "sale_price": 399.99,
  "category_id": 5,
  "brand_id": 10,
  "stock_quantity": 100,
  "sku": "PRD-2024-001",
  "status": "active",
  "is_featured": true,
  "thumbnail": "/images/products/tshirt.jpg",
  "images": [
    "/images/products/tshirt-1.jpg",
    "/images/products/tshirt-2.jpg"
  ],
  "attributes": [...],
  "specifications": [...],
  "tag_ids": [1, 5, 12]
}
```

---

## 9. Error Handling Guide

### Common Validation Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `The name must be at least 3 characters` | Name too short | Use minimum 3 characters |
| `The slug has already been taken` | Duplicate slug | Use unique slug or let system auto-generate |
| `The sale price must be less than the regular price` | Sale price >= price | Ensure sale_price < price |
| `The selected category does not exist` | Invalid category_id | Use valid category ID |
| `The stock quantity must be at least 0` | Negative stock | Use 0 or positive number |
| `The sku has already been taken` | Duplicate SKU | Use unique SKU code |

### HTTP Status Codes

- **200 OK**: Product updated successfully
- **401 Unauthorized**: Missing or invalid token
- **403 Forbidden**: No permission to update products
- **404 Not Found**: Product doesn't exist
- **422 Unprocessable Entity**: Validation failed
- **500 Internal Server Error**: Server error

---

## 10. Best Practices

### 1. Only Send Changed Fields
```typescript
// ✅ Good - Only send what changed
const changedFields = {
  price: 299.99
};
await updateProduct(123, changedFields);

// ❌ Bad - Sending all fields unnecessarily
const allFields = {
  name: product.name,
  description: product.description,
  price: 299.99,
  // ... 40 more fields
};
```

### 2. Handle Validation Errors Gracefully
```typescript
try {
  await updateProduct(id, data);
} catch (error) {
  if (error.errors) {
    // Display field-specific errors
    Object.keys(error.errors).forEach(field => {
      showFieldError(field, error.errors[field][0]);
    });
  } else {
    // Display general error
    showError(error.message);
  }
}
```

### 3. Show Loading States
```typescript
setLoading(true);
try {
  const result = await updateProduct(id, data);
  showSuccess('Product updated!');
} finally {
  setLoading(false);
}
```

### 4. Optimistic Updates
```typescript
// Update UI immediately
setProduct(newData);

try {
  await updateProduct(id, newData);
} catch (error) {
  // Revert on error
  setProduct(oldData);
  showError('Update failed');
}
```

### 5. Validate Before Sending
```typescript
// Client-side validation
if (data.name && data.name.length < 3) {
  showError('Name must be at least 3 characters');
  return;
}

if (data.sale_price && data.sale_price >= data.price) {
  showError('Sale price must be less than regular price');
  return;
}

await updateProduct(id, data);
```

---

## 11. Testing the API

### Using Postman

1. **Set Authorization:**
   - Type: Bearer Token
   - Token: `{your_auth_token}`

2. **Set Headers:**
   ```
   Content-Type: application/json
   Accept: application/json
   ```

3. **Send Request:**
   ```
   PUT http://localhost:8000/api/products/123
   
   Body (raw JSON):
   {
     "name": "Test Product Update",
     "price": 299.99
   }
   ```

### Using cURL

```bash
curl -X PUT http://localhost:8000/api/products/123 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Product Name",
    "price": 299.99,
    "stock_quantity": 50
  }'
```

---

## 12. Current Known Issues

### ✅ FIXED: Ambiguous Column Error
**Issue:** GET `/api/products/{id}` was returning 500 error with "ambiguous column 'id' in product_tags query"

**Status:** Fixed in ProductService.php
- Changed: `select('id', 'name', 'slug')`
- To: `select('product_tags.id', 'product_tags.name', 'product_tags.slug')`

**Location:** Lines 211-213 and 324-326 in `app/Services/ProductService.php`

---

## Support

For issues or questions:
1. Check validation error messages
2. Verify authentication token
3. Ensure user has correct permissions
4. Review request payload structure
5. Check database constraints

---

**Last Updated:** January 2024  
**API Version:** 1.0  
**Laravel Version:** 10.x
