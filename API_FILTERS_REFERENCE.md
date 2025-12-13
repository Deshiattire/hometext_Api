# API Filters Quick Reference

## 🔗 Endpoint
```
GET /api/products
```

## 📋 All Available Query Parameters

### Search
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `search` | string | `?search=shirt` | Search in name, SKU, description, categories |

### Category Filters
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `category_id` | integer | `?category_id=3` | Filter by main category |
| `sub_category_id` | integer | `?sub_category_id=5` | Filter by sub category |
| `child_sub_category_id` | integer | `?child_sub_category_id=10` | Filter by child sub category |

### Product Filters
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `brand_id` | integer | `?brand_id=2` | Filter by brand |
| `status` | integer | `?status=1` | Filter by status (1=active, 0=inactive) |

### Price Filters
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `min_price` | numeric | `?min_price=100` | Minimum price |
| `max_price` | numeric | `?max_price=500` | Maximum price |

### Attribute Filters
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `color` | string | `?color=red` | Filter by color name (searches in `product_variations.attributes` JSON field) |
| `attribute_id` | integer | `?attribute_id=5` | Filter by attribute ID |
| `attribute_value_id` | integer | `?attribute_value_id=25` | Filter by single attribute value |
| `attribute_value_ids` | array | `?attribute_value_ids=25,26,27` | Filter by multiple attribute values (comma-separated) |

### Stock Filters
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `in_stock` | boolean | `?in_stock=true` | Only show in-stock products |
| `stock_status` | string | `?stock_status=in_stock` | Filter by stock status: `in_stock`, `out_of_stock`, `on_backorder`, `preorder` |

### Sorting & Pagination
| Parameter | Type | Example | Description |
|-----------|------|---------|-------------|
| `order_by` | string | `?order_by=price` | Sort field: `id`, `name`, `price`, `created_at`, `updated_at` |
| `direction` | string | `?direction=asc` | Sort direction: `asc` or `desc` |
| `per_page` | integer | `?per_page=30` | Items per page (1-100, default: 20) |
| `page` | integer | `?page=2` | Page number (default: 1) |

---

## 🎯 Example API Calls

### Example 1: Basic Search
```
GET /api/products?search=shirt
```

### Example 2: Category Filter
```
GET /api/products?category_id=3&sub_category_id=5
```

### Example 3: Price Range
```
GET /api/products?min_price=100&max_price=500
```

### Example 4: Color Filter
```
GET /api/products?color=blue
```

### Example 5: Multiple Filters Combined
```
GET /api/products?search=cotton&category_id=3&brand_id=5&min_price=50&max_price=200&color=blue&in_stock=true&order_by=price&direction=asc&per_page=30&page=1
```

### Example 6: Attribute Value Filter
```
GET /api/products?attribute_value_id=25
```

### Example 7: Multiple Attribute Values
```
GET /api/products?attribute_value_ids=25,26,27
```

### Example 8: Stock Status
```
GET /api/products?stock_status=in_stock
```

---

## ✅ Response Format

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "price": 100,
        ...
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 20,
      "total": 200,
      "from": 1,
      "to": 20,
      "has_more": true
    }
  }
}
```

---

## 🚀 Frontend Implementation

See `FRONTEND_FILTERS_GUIDE.md` for complete Next.js implementation examples.
