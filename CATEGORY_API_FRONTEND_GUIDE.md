# Category API Frontend Integration Guide

## Overview
This document provides complete API endpoint documentation, response structures, and migration guidelines for integrating the new unified category API structure into your frontend.

---

## 📋 Table of Contents
1. [API Endpoints](#api-endpoints)
2. [Response Structures](#response-structures)
3. [Migration Guide](#migration-guide)
4. [Code Examples](#code-examples)
5. [Error Handling](#error-handling)

---

## 🔗 API Endpoints

### Base URL
```
/api/v1/categories
```

### 1. Get Complete Menu Tree
**Endpoint:** `GET /api/v1/categories/tree`

**Description:** Returns the complete hierarchical menu structure with all categories, subcategories, and child categories in a nested format. Use this for initial page load or when you need the full menu structure.

**Query Parameters:**
- `refresh` (optional, boolean): Force refresh cache. Default: `false`

**Example Request:**
```bash
GET /api/v1/categories/tree
GET /api/v1/categories/tree?refresh=true
```

**Example Response:**
```json
{
  "success": true,
  "message": "Menu tree retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "image": "https://example.com/storage/categories/2024/12/electronics-primary.jpg",
      "description": "Electronic devices and gadgets for home and office",
      "is_active": true,
      "sort_order": 1,
      "subcategories": [
        {
          "id": 10,
          "name": "Washing Machine",
          "slug": "washing-machine",
          "parent_id": 1,
          "image": "https://example.com/storage/categories/2024/12/washing-machine-primary.jpg",
          "is_active": true,
          "sort_order": 1,
          "child_categories": [
            {
              "id": 100,
              "name": "Family Washing Machine",
              "slug": "family-washing-machine",
              "parent_id": 10,
              "image": "https://example.com/storage/categories/2024/12/family-washing-primary.jpg",
              "is_active": true,
              "sort_order": 1
            },
            {
              "id": 101,
              "name": "Small Washing Machine",
              "slug": "small-washing-machine",
              "parent_id": 10,
              "image": null,
              "is_active": true,
              "sort_order": 2
            }
          ]
        },
        {
          "id": 11,
          "name": "Mobile Phone",
          "slug": "mobile-phone",
          "parent_id": 1,
          "image": null,
          "is_active": true,
          "sort_order": 2,
          "child_categories": []
        }
      ]
    },
    {
      "id": 2,
      "name": "Home Decor",
      "slug": "home-decor",
      "image": null,
      "description": "Everything to make your home beautiful",
      "is_active": true,
      "sort_order": 2,
      "subcategories": [
        {
          "id": 20,
          "name": "Bathrobes",
          "slug": "bathrobes",
          "parent_id": 2,
          "image": null,
          "is_active": true,
          "sort_order": 1,
          "child_categories": []
        }
      ]
    }
  ],
  "meta": {
    "request_id": "req_1234567890",
    "timestamp": "2024-12-01T10:30:00Z",
    "response_time_ms": 45.23
  }
}
```

---

### 2. Get Root Categories Only
**Endpoint:** `GET /api/v1/categories`

**Description:** Returns only top-level/parent categories without nested children. Use this for initial page load optimization or when you want to lazy-load children on hover/click.

**Query Parameters:**
- `refresh` (optional, boolean): Force refresh cache. Default: `false`

**Example Request:**
```bash
GET /api/v1/categories
GET /api/v1/categories?refresh=true
```

**Example Response:**
```json
{
  "success": true,
  "message": "Root categories retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "image": "https://example.com/storage/categories/2024/12/electronics-primary.jpg",
      "has_children": true,
      "is_active": true,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "Home Decor",
      "slug": "home-decor",
      "image": null,
      "has_children": true,
      "is_active": true,
      "sort_order": 2
    },
    {
      "id": 3,
      "name": "Fashion & Apparel",
      "slug": "fashion-apparel",
      "image": null,
      "has_children": true,
      "is_active": true,
      "sort_order": 3
    }
  ],
  "meta": {
    "request_id": "req_1234567891",
    "timestamp": "2024-12-01T10:31:00Z",
    "response_time_ms": 12.45
  }
}
```

**Key Fields:**
- `has_children`: Boolean indicating if category has subcategories. Use this to show/hide dropdown arrows or to trigger lazy loading.

---

### 3. Get Category Children
**Endpoint:** `GET /api/v1/categories/{id}/children`

**Description:** Fetches subcategories and child categories for a specific parent category. Perfect for lazy-loading on hover or click.

**URL Parameters:**
- `id` (required, integer): Category ID

**Example Request:**
```bash
GET /api/v1/categories/1/children
GET /api/v1/categories/10/children
```

**Example Response:**
```json
{
  "success": true,
  "message": "Category children retrieved successfully",
  "data": {
    "category": {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "level": 1
    },
    "children": [
      {
        "id": 10,
        "name": "Washing Machine",
        "slug": "washing-machine",
        "parent_id": 1,
        "has_children": true,
        "is_active": true,
        "sort_order": 1
      },
      {
        "id": 11,
        "name": "Mobile Phone",
        "slug": "mobile-phone",
        "parent_id": 1,
        "has_children": false,
        "is_active": true,
        "sort_order": 2
      }
    ]
  },
  "meta": {
    "request_id": "req_1234567892",
    "timestamp": "2024-12-01T10:32:00Z",
    "response_time_ms": 8.67
  }
}
```

**Usage:**
- Use this endpoint when user hovers over a category to load its children
- The `has_children` flag tells you if you need to load another level
- Works for any level (1, 2, or 3)

---

### 4. Get Category by Slug
**Endpoint:** `GET /api/v1/categories/slug/{slug}`

**Description:** Retrieves category information using URL-friendly slug. Essential for routing and displaying category pages.

**URL Parameters:**
- `slug` (required, string): Category slug (URL-friendly name)

**Example Request:**
```bash
GET /api/v1/categories/slug/electronics
GET /api/v1/categories/slug/washing-machine
GET /api/v1/categories/slug/family-washing-machine
```

**Example Response:**
```json
{
  "success": true,
  "message": "Category retrieved successfully",
  "data": {
    "id": 10,
    "name": "Washing Machine",
    "slug": "washing-machine",
    "description": "All types of washing machines for your laundry needs",
    "parent_id": 1,
    "level": 2,
    "breadcrumb": [
      {
        "id": 1,
        "name": "Electronics",
        "slug": "electronics",
        "level": 1
      },
      {
        "id": 10,
        "name": "Washing Machine",
        "slug": "washing-machine",
        "level": 2
      }
    ],
    "is_active": true,
    "meta_title": "Washing Machines - Buy Online",
    "meta_description": "Shop washing machines from top brands. Free shipping available.",
    "image": "https://example.com/storage/categories/2024/12/washing-machine-primary.jpg"
  },
  "meta": {
    "request_id": "req_1234567893",
    "timestamp": "2024-12-01T10:33:00Z",
    "response_time_ms": 15.89
  }
}
```

**Key Features:**
- Includes `breadcrumb` array for navigation
- Includes SEO fields (`meta_title`, `meta_description`)
- Works for any category level

---

### 5. Get Breadcrumb Path
**Endpoint:** `GET /api/v1/categories/{id}/breadcrumb`

**Description:** Returns the hierarchical path from root to the specified category. Use this for breadcrumb navigation.

**URL Parameters:**
- `id` (required, integer): Category ID

**Example Request:**
```bash
GET /api/v1/categories/100/breadcrumb
```

**Example Response:**
```json
{
  "success": true,
  "message": "Breadcrumb retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "level": 1
    },
    {
      "id": 10,
      "name": "Washing Machine",
      "slug": "washing-machine",
      "level": 2
    },
    {
      "id": 100,
      "name": "Family Washing Machine",
      "slug": "family-washing-machine",
      "level": 3
    }
  ],
  "meta": {
    "request_id": "req_1234567894",
    "timestamp": "2024-12-01T10:34:00Z",
    "response_time_ms": 5.12
  }
}
```

---

## 📊 Response Structures

### Standard Response Format
All endpoints follow this consistent structure:

```typescript
interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  meta?: {
    request_id: string;
    timestamp: string;
    response_time_ms: number;
  };
}
```

### Category Object Structure
```typescript
interface Category {
  id: number;
  name: string;
  slug: string;
  image: string | null;
  description?: string | null;
  is_active: boolean;
  sort_order: number;
  parent_id?: number | null;
  level?: number;
  meta_title?: string | null;
  meta_description?: string | null;
}
```

### Category with Children (Tree Structure)
```typescript
interface CategoryTree extends Category {
  subcategories: CategoryWithChildren[];
}

interface CategoryWithChildren extends Category {
  parent_id: number;
  child_categories: Category[];
}
```

### Root Category
```typescript
interface RootCategory extends Category {
  has_children: boolean;
}
```

### Breadcrumb Item
```typescript
interface BreadcrumbItem {
  id: number;
  name: string;
  slug: string;
  level: number;
}
```

---

## 🔄 Migration Guide

### Old Structure vs New Structure

#### Old Structure (Previous API)
```javascript
// Old response structure
{
  id: 1,
  name: "Electronics",
  image: "electronics.jpg",
  sub_categories: [
    {
      id: 10,
      name: "Washing Machine",
      category_id: 1,
      child_sub_categories: [...]
    }
  ]
}
```

#### New Structure (Current API)
```javascript
// New response structure
{
  id: 1,
  name: "Electronics",
  slug: "electronics",
  image: "https://full-url-path.jpg",
  is_active: true,
  sort_order: 1,
  subcategories: [
    {
      id: 10,
      name: "Washing Machine",
      slug: "washing-machine",
      parent_id: 1,
      child_categories: [...]
    }
  ]
}
```

### Key Changes

1. **Field Name Changes:**
   - `sub_categories` → `subcategories`
   - `child_sub_categories` → `child_categories`
   - `category_id` → `parent_id`

2. **New Fields:**
   - `slug`: URL-friendly identifier
   - `is_active`: Boolean status
   - `sort_order`: Display order
   - `has_children`: Boolean flag for lazy loading
   - `level`: Category level (1, 2, or 3)

3. **Image URL:**
   - Old: Relative path or filename
   - New: Full URL or null

### Migration Steps

#### Step 1: Update API Endpoints
```javascript
// Old endpoint
const oldEndpoint = '/api/product/menu';

// New endpoints
const treeEndpoint = '/api/v1/categories/tree';
const rootEndpoint = '/api/v1/categories';
const childrenEndpoint = (id) => `/api/v1/categories/${id}/children`;
```

#### Step 2: Update Data Fetching
```javascript
// Old way
async function fetchMenu() {
  const response = await fetch('/api/product/menu');
  const data = await response.json();
  return data.data; // Old structure
}

// New way
async function fetchMenuTree() {
  const response = await fetch('/api/v1/categories/tree');
  const result = await response.json();
  if (result.success) {
    return result.data; // New structure
  }
  throw new Error(result.message);
}
```

#### Step 3: Update Component Props
```javascript
// Old component
function CategoryMenu({ categories }) {
  return categories.map(cat => (
    <CategoryItem 
      key={cat.id}
      name={cat.name}
      image={cat.image}
      subCategories={cat.sub_categories}
    />
  ));
}

// New component
function CategoryMenu({ categories }) {
  return categories.map(cat => (
    <CategoryItem 
      key={cat.id}
      name={cat.name}
      slug={cat.slug}
      image={cat.image}
      isActive={cat.is_active}
      hasChildren={cat.has_children}
      subcategories={cat.subcategories}
    />
  ));
}
```

#### Step 4: Update Navigation/Routing
```javascript
// Old routing
<Link to={`/category/${category.id}`}>
  {category.name}
</Link>

// New routing (using slug)
<Link to={`/category/${category.slug}`}>
  {category.name}
</Link>
```

---

## 💻 Code Examples

### React/Next.js Example

#### Fetching Complete Tree
```typescript
// hooks/useCategoryTree.ts
import { useState, useEffect } from 'react';

interface CategoryTree {
  id: number;
  name: string;
  slug: string;
  image: string | null;
  subcategories: CategoryWithChildren[];
}

export function useCategoryTree() {
  const [tree, setTree] = useState<CategoryTree[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchTree() {
      try {
        setLoading(true);
        const response = await fetch('/api/v1/categories/tree');
        const result = await response.json();
        
        if (result.success) {
          setTree(result.data);
        } else {
          setError(result.message);
        }
      } catch (err) {
        setError('Failed to load categories');
      } finally {
        setLoading(false);
      }
    }

    fetchTree();
  }, []);

  return { tree, loading, error };
}
```

#### Lazy Loading Children
```typescript
// hooks/useCategoryChildren.ts
export function useCategoryChildren(categoryId: number | null) {
  const [children, setChildren] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!categoryId) return;

    async function fetchChildren() {
      setLoading(true);
      try {
        const response = await fetch(`/api/v1/categories/${categoryId}/children`);
        const result = await response.json();
        
        if (result.success) {
          setChildren(result.data.children);
        }
      } catch (err) {
        console.error('Failed to load children:', err);
      } finally {
        setLoading(false);
      }
    }

    fetchChildren();
  }, [categoryId]);

  return { children, loading };
}
```

#### Category Menu Component
```tsx
// components/CategoryMenu.tsx
import { useCategoryTree } from '@/hooks/useCategoryTree';

export function CategoryMenu() {
  const { tree, loading, error } = useCategoryTree();

  if (loading) return <div>Loading menu...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <nav className="category-menu">
      {tree.map(category => (
        <CategoryItem key={category.id} category={category} />
      ))}
    </nav>
  );
}

function CategoryItem({ category }) {
  const [showChildren, setShowChildren] = useState(false);
  const { children, loading } = useCategoryChildren(
    showChildren ? category.id : null
  );

  return (
    <div 
      className="category-item"
      onMouseEnter={() => setShowChildren(true)}
    >
      <Link href={`/category/${category.slug}`}>
        {category.image && (
          <img src={category.image} alt={category.name} />
        )}
        <span>{category.name}</span>
      </Link>
      
      {category.has_children && showChildren && (
        <div className="subcategories">
          {loading ? (
            <div>Loading...</div>
          ) : (
            children.map(child => (
              <Link 
                key={child.id} 
                href={`/category/${child.slug}`}
              >
                {child.name}
              </Link>
            ))
          )}
        </div>
      )}
    </div>
  );
}
```

#### Breadcrumb Component
```tsx
// components/Breadcrumb.tsx
import { useCategoryBySlug } from '@/hooks/useCategoryBySlug';

export function Breadcrumb({ categorySlug }) {
  const { category, loading } = useCategoryBySlug(categorySlug);

  if (loading || !category) return null;

  return (
    <nav aria-label="Breadcrumb">
      <ol className="breadcrumb">
        <li><Link href="/">Home</Link></li>
        {category.breadcrumb.map((item, index) => (
          <li key={item.id}>
            {index < category.breadcrumb.length - 1 ? (
              <Link href={`/category/${item.slug}`}>
                {item.name}
              </Link>
            ) : (
              <span>{item.name}</span>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
}
```

### Vue.js Example

```vue
<!-- components/CategoryMenu.vue -->
<template>
  <nav class="category-menu">
    <div v-if="loading">Loading...</div>
    <div v-else-if="error">Error: {{ error }}</div>
    <CategoryItem
      v-else
      v-for="category in tree"
      :key="category.id"
      :category="category"
    />
  </nav>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const tree = ref([]);
const loading = ref(true);
const error = ref(null);

async function fetchTree() {
  try {
    loading.value = true;
    const response = await fetch('/api/v1/categories/tree');
    const result = await response.json();
    
    if (result.success) {
      tree.value = result.data;
    } else {
      error.value = result.message;
    }
  } catch (err) {
    error.value = 'Failed to load categories';
  } finally {
    loading.value = false;
  }
}

onMounted(fetchTree);
</script>
```

---

## ⚠️ Error Handling

### Error Response Format
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Error detail"]
  }
}
```

### HTTP Status Codes
- `200`: Success
- `400`: Bad Request (invalid parameters)
- `404`: Not Found (category doesn't exist)
- `422`: Validation Error
- `500`: Server Error

### Error Handling Example
```typescript
async function fetchCategory(slug: string) {
  try {
    const response = await fetch(`/api/v1/categories/slug/${slug}`);
    const result = await response.json();
    
    if (!result.success) {
      if (response.status === 404) {
        // Category not found
        return null;
      }
      throw new Error(result.message);
    }
    
    return result.data;
  } catch (error) {
    console.error('Failed to fetch category:', error);
    throw error;
  }
}
```

---

## 🚀 Performance Tips

1. **Use Lazy Loading:**
   - Fetch root categories first (`/api/v1/categories`)
   - Load children on demand (`/api/v1/categories/{id}/children`)

2. **Cache Responses:**
   - Categories don't change frequently
   - Cache tree data for 24 hours (API already caches server-side)

3. **Optimize Images:**
   - Check if `image` is null before rendering
   - Use placeholder images for missing images

4. **Use Slug for Routing:**
   - More SEO-friendly than IDs
   - Better user experience

---

## 📝 Checklist for Frontend Migration

- [ ] Update API endpoint URLs
- [ ] Update response structure handling
- [ ] Change `sub_categories` to `subcategories`
- [ ] Change `child_sub_categories` to `child_categories`
- [ ] Update routing to use `slug` instead of `id`
- [ ] Add `has_children` check for lazy loading
- [ ] Update image URL handling (now full URLs)
- [ ] Implement breadcrumb using new endpoint
- [ ] Add error handling for new response format
- [ ] Test all category pages
- [ ] Test navigation and routing
- [ ] Test lazy loading functionality

---

## 🔗 Quick Reference

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/categories/tree` | GET | Complete menu tree |
| `/api/v1/categories` | GET | Root categories only |
| `/api/v1/categories/{id}/children` | GET | Category children |
| `/api/v1/categories/slug/{slug}` | GET | Category by slug |
| `/api/v1/categories/{id}/breadcrumb` | GET | Breadcrumb path |

---

## 📞 Support

If you encounter any issues during migration:
1. Check the response structure matches the examples
2. Verify API endpoints are accessible
3. Check browser console for errors
4. Verify network requests in DevTools

For questions or issues, refer to the main documentation: `CATEGORY_API_RESTRUCTURE_SUMMARY.md`

