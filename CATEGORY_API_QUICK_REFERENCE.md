# Category API - Quick Reference Card

## 🚀 Quick Start

### Base URL
```
/api/v1/categories
```

---

## 📍 Endpoints at a Glance

| Endpoint | Method | Use Case |
|----------|--------|----------|
| `/api/v1/categories/tree` | GET | Get full menu tree (all levels) |
| `/api/v1/categories` | GET | Get root categories only |
| `/api/v1/categories/{id}/children` | GET | Get children of a category (lazy load) |
| `/api/v1/categories/slug/{slug}` | GET | Get category details by slug |
| `/api/v1/categories/{id}/breadcrumb` | GET | Get breadcrumb path |

---

## 📦 Response Structure

### Standard Response
```json
{
  "success": true,
  "message": "Success message",
  "data": { /* response data */ },
  "meta": { /* metadata */ }
}
```

### Category Object
```json
{
  "id": 1,
  "name": "Electronics",
  "slug": "electronics",
  "image": "https://...",
  "is_active": true,
  "sort_order": 1,
  "parent_id": null,
  "level": 1,
  "has_children": true
}
```

---

## 🔄 Migration Quick Guide

### Old → New Field Mapping

| Old Field | New Field | Notes |
|-----------|-----------|-------|
| `sub_categories` | `subcategories` | Array of subcategories |
| `child_sub_categories` | `child_categories` | Array of child categories |
| `category_id` | `parent_id` | Parent category ID |
| `id` (for routing) | `slug` | Use slug for URLs |

### Old Endpoint → New Endpoint

| Old | New |
|-----|-----|
| `/api/product/menu` | `/api/v1/categories/tree` |

---

## 💡 Common Patterns

### 1. Fetch Full Menu
```javascript
fetch('/api/v1/categories/tree')
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const categories = data.data;
      // Render menu
    }
  });
```

### 2. Lazy Load Children
```javascript
// On hover/click
fetch(`/api/v1/categories/${categoryId}/children`)
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const children = data.data.children;
      // Render children
    }
  });
```

### 3. Get Category by Slug
```javascript
fetch(`/api/v1/categories/slug/${slug}`)
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const category = data.data;
      // Use category.breadcrumb for navigation
    }
  });
```

### 4. Build Breadcrumb
```javascript
fetch(`/api/v1/categories/${categoryId}/breadcrumb`)
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const breadcrumb = data.data;
      // Render breadcrumb: Home > Category > Subcategory
    }
  });
```

---

## ⚠️ Important Notes

1. **Always check `success` field** before using `data`
2. **Image can be `null`** - always check before rendering
3. **Use `slug` for routing** instead of `id` (SEO-friendly)
4. **Use `has_children`** to determine if lazy loading is needed
5. **All endpoints return consistent structure**

---

## 🎯 Recommended Approach

### Initial Load
```javascript
// Fetch root categories only (faster)
GET /api/v1/categories
```

### On Hover/Click
```javascript
// Lazy load children
GET /api/v1/categories/{id}/children
```

### For Full Menu (if needed)
```javascript
// Fetch complete tree
GET /api/v1/categories/tree
```

---

## 📋 Field Reference

### Required Fields (Always Present)
- `id` - Category ID
- `name` - Category name
- `slug` - URL-friendly identifier
- `is_active` - Active status
- `sort_order` - Display order

### Optional Fields (May be null)
- `image` - Image URL
- `description` - Category description
- `parent_id` - Parent category ID
- `level` - Category level (1, 2, or 3)
- `meta_title` - SEO title
- `meta_description` - SEO description

### Computed Fields
- `has_children` - Boolean, indicates if category has subcategories

---

## 🔍 Response Examples

### Tree Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "subcategories": [
        {
          "id": 10,
          "name": "Washing Machine",
          "child_categories": [...]
        }
      ]
    }
  ]
}
```

### Root Categories Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "has_children": true
    }
  ]
}
```

### Children Response
```json
{
  "success": true,
  "data": {
    "category": { "id": 1, "name": "Electronics" },
    "children": [
      { "id": 10, "name": "Washing Machine", "has_children": true }
    ]
  }
}
```

---

## 🛠️ Error Handling

```javascript
try {
  const response = await fetch('/api/v1/categories/tree');
  const result = await response.json();
  
  if (!result.success) {
    // Handle error
    console.error(result.message);
    return;
  }
  
  // Use result.data
  const categories = result.data;
} catch (error) {
  // Handle network error
  console.error('Network error:', error);
}
```

---

## 📚 Full Documentation

For complete documentation, examples, and migration guide, see:
- **CATEGORY_API_FRONTEND_GUIDE.md** - Complete integration guide



