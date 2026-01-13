# Product Navigation API - Frontend Integration Guide

## Overview

This guide explains how to implement **contextual product navigation** (Prev/Next buttons) on product detail pages, following the same pattern used by major e-commerce platforms like Amazon, Shopify, and Zalando.

## Key Concept

**Navigation is based on browsing context, not product IDs.**

When a user clicks a product from a listing (category page, search results, etc.), the Prev/Next buttons should navigate to the previous and next products **in that specific listing**, not to adjacent product IDs.

## Architecture

```
┌─────────────────────┐      ┌─────────────────────┐      ┌─────────────────────┐
│   Product Listing   │ ──►  │   Product Detail    │ ──►  │   Next Product      │
│   (with filters)    │      │   (with context)    │      │   (same context)    │
└─────────────────────┘      └─────────────────────┘      └─────────────────────┘
         │                            │                            │
         ▼                            ▼                            ▼
   list_id: abc123              ?list=abc123&pos=2          ?list=abc123&pos=3
```

---

## Quick Start Checklist

For frontend developers, here's what you need to do:

- [ ] **Step 1**: When fetching product listings, save `navigation.list_id` from response
- [ ] **Step 2**: When rendering product cards, add `?list={list_id}&pos={index}` to URLs
- [ ] **Step 3**: On product detail page, read `list` and `pos` from URL query params
- [ ] **Step 4**: Pass these params when calling product detail API
- [ ] **Step 5**: Render prev/next buttons using `navigation.prev` and `navigation.next`
- [ ] **Step 6**: Build prev/next URLs with same `list_id` and the `position` from response

---

## API Endpoints Summary

| Endpoint | Returns Navigation? | Use Case |
|----------|---------------------|----------|
| `GET /api/products` | ✅ `list_id` | Category/search listings |
| `GET /api/products/featured` | ✅ `list_id` | Homepage featured section |
| `GET /api/products/bestsellers` | ✅ `list_id` | Homepage bestsellers |
| `GET /api/products/trending` | ✅ `list_id` | Homepage trending |
| `GET /api/products/new-arrivals` | ✅ `list_id` | Homepage new arrivals |
| `GET /api/products/on-sale` | ✅ `list_id` | Homepage deals/sale |
| `GET /api/products/{id}?list=X&pos=Y` | ✅ `prev/next` | Product detail with context |
| `GET /api/products/slug/{slug}?list=X&pos=Y` | ✅ `prev/next` | Product detail (SEO URL) |
| `GET /api/products/{id}/navigation?list=X` | ✅ `prev/next` | Standalone navigation fetch |

---

## API Endpoints

### 1. Product Listing (with Navigation List)

```
GET /api/products
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| category_id | int | Filter by category |
| sub_category_id | int | Filter by subcategory |
| brand_id | int | Filter by brand |
| min_price | float | Minimum price |
| max_price | float | Maximum price |
| search | string | Search term |
| order_by | string | Sort field (created_at, price, name) |
| direction | string | Sort direction (asc, desc) |
| per_page | int | Items per page (default: 20) |

**Response:**
```json
{
  "status": "success",
  "message": "Products retrieved successfully",
  "data": {
    "products": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 20,
      "total": 200
    },
    "navigation": {
      "list_id": "abc123def456",
      "total": 200,
      "expires_at": "2026-01-09T15:30:00+00:00"
    }
  }
}
```

### 2. Product Detail (with Navigation Context)

```
GET /api/products/{id}?list={list_id}&pos={position}
GET /api/products/slug/{slug}?list={list_id}&pos={position}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| list | string | Navigation list ID from product listing |
| pos | int | Position hint (0-indexed) for faster lookup |

**Response:**
```json
{
  "status": "success",
  "message": "Product retrieved successfully",
  "data": {
    "product": {
      "id": 369,
      "name": "Premium Bath Towel",
      "slug": "premium-bath-towel",
      ...
    },
    "navigation": {
      "prev": {
        "id": 491,
        "name": "Luxury Bathrobe",
        "slug": "luxury-bathrobe",
        "price": 2500,
        "thumbnail": "https://example.com/images/product/thumb/bathrobe.jpg",
        "position": 1
      },
      "next": {
        "id": 720,
        "name": "Cotton Bath Mat",
        "slug": "cotton-bath-mat",
        "price": 850,
        "thumbnail": "https://example.com/images/product/thumb/bathmat.jpg",
        "position": 3
      },
      "position": 2,
      "total": 42,
      "list_id": "abc123def456"
    }
  }
}
```

### 3. Standalone Navigation Endpoint

```
GET /api/products/{id}/navigation?list={list_id}&pos={position}
```

Use this when you need to refresh navigation without fetching full product details.

**Additional Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| fallback | bool | Use category-based fallback if no list_id |
| order_by | string | Sort field for fallback navigation |
| direction | string | Sort direction for fallback navigation |

---

## Frontend Implementation

### React/Next.js Example

```typescript
// types/navigation.ts
interface ProductNavigation {
  prev: ProductNavItem | null;
  next: ProductNavItem | null;
  position: number | null;
  total: number | null;
  list_id: string | null;
}

interface ProductNavItem {
  id: number;
  name: string;
  slug: string;
  price: number;
  thumbnail: string | null;
  position: number;
}

interface NavigationListInfo {
  list_id: string;
  total: number;
  expires_at: string;
}
```

```typescript
// hooks/useProductNavigation.ts
import { useRouter, useSearchParams } from 'next/navigation';

export function useProductNavigation() {
  const router = useRouter();
  const searchParams = useSearchParams();
  
  const listId = searchParams.get('list');
  const position = searchParams.get('pos');
  
  // Build product URL with navigation context
  const buildProductUrl = (
    slug: string, 
    listId: string | null, 
    position: number | null
  ) => {
    const params = new URLSearchParams();
    if (listId) params.set('list', listId);
    if (position !== null) params.set('pos', position.toString());
    
    const queryString = params.toString();
    return `/products/${slug}${queryString ? `?${queryString}` : ''}`;
  };
  
  return {
    listId,
    position: position ? parseInt(position) : null,
    buildProductUrl,
  };
}
```

```tsx
// components/ProductNavigation.tsx
import Link from 'next/link';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface ProductNavigationProps {
  navigation: ProductNavigation;
  listId: string | null;
}

export function ProductNavigation({ navigation, listId }: ProductNavigationProps) {
  if (!navigation.prev && !navigation.next) {
    return null; // No navigation context
  }
  
  const buildUrl = (product: ProductNavItem) => {
    const params = new URLSearchParams();
    if (listId) params.set('list', listId);
    params.set('pos', product.position.toString());
    return `/products/${product.slug}?${params.toString()}`;
  };
  
  return (
    <div className="flex items-center justify-between py-4 border-t border-b">
      {/* Previous Product */}
      <div className="flex-1">
        {navigation.prev ? (
          <Link 
            href={buildUrl(navigation.prev)}
            className="flex items-center gap-3 group"
          >
            <ChevronLeft className="w-5 h-5 text-gray-400 group-hover:text-primary" />
            <div className="flex items-center gap-3">
              {navigation.prev.thumbnail && (
                <img 
                  src={navigation.prev.thumbnail} 
                  alt={navigation.prev.name}
                  className="w-12 h-12 object-cover rounded"
                />
              )}
              <div className="text-left">
                <div className="text-xs text-gray-500">Previous</div>
                <div className="text-sm font-medium truncate max-w-[150px]">
                  {navigation.prev.name}
                </div>
              </div>
            </div>
          </Link>
        ) : (
          <div className="w-5" /> // Spacer
        )}
      </div>
      
      {/* Position Indicator */}
      {navigation.position && navigation.total && (
        <div className="text-sm text-gray-500 px-4">
          {navigation.position} of {navigation.total}
        </div>
      )}
      
      {/* Next Product */}
      <div className="flex-1 flex justify-end">
        {navigation.next ? (
          <Link 
            href={buildUrl(navigation.next)}
            className="flex items-center gap-3 group"
          >
            <div className="flex items-center gap-3">
              <div className="text-right">
                <div className="text-xs text-gray-500">Next</div>
                <div className="text-sm font-medium truncate max-w-[150px]">
                  {navigation.next.name}
                </div>
              </div>
              {navigation.next.thumbnail && (
                <img 
                  src={navigation.next.thumbnail} 
                  alt={navigation.next.name}
                  className="w-12 h-12 object-cover rounded"
                />
              )}
            </div>
            <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-primary" />
          </Link>
        ) : (
          <div className="w-5" /> // Spacer
        )}
      </div>
    </div>
  );
}
```

```tsx
// app/products/[slug]/page.tsx
import { ProductNavigation } from '@/components/ProductNavigation';

async function getProduct(slug: string, listId?: string, pos?: string) {
  const params = new URLSearchParams();
  if (listId) params.set('list', listId);
  if (pos) params.set('pos', pos);
  
  const url = `${API_BASE}/products/slug/${slug}${params.toString() ? `?${params}` : ''}`;
  const res = await fetch(url);
  return res.json();
}

export default async function ProductPage({ 
  params, 
  searchParams 
}: { 
  params: { slug: string },
  searchParams: { list?: string, pos?: string }
}) {
  const { data } = await getProduct(
    params.slug, 
    searchParams.list, 
    searchParams.pos
  );
  
  return (
    <div>
      {/* Product Navigation */}
      {data.navigation && (
        <ProductNavigation 
          navigation={data.navigation}
          listId={searchParams.list || null}
        />
      )}
      
      {/* Product Details */}
      <ProductDetails product={data.product} />
      
      {/* Bottom Navigation (optional) */}
      {data.navigation && (
        <ProductNavigation 
          navigation={data.navigation}
          listId={searchParams.list || null}
        />
      )}
    </div>
  );
}
```

### Product Listing Integration

```tsx
// app/category/[slug]/page.tsx
'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';

interface ListingState {
  products: Product[];
  navigation: NavigationListInfo;
  pagination: PaginationInfo;
}

export default function CategoryPage({ params }: { params: { slug: string } }) {
  const [data, setData] = useState<ListingState | null>(null);
  const searchParams = useSearchParams();
  
  useEffect(() => {
    fetchProducts();
  }, [searchParams]);
  
  const fetchProducts = async () => {
    const queryString = searchParams.toString();
    const res = await fetch(`/api/products?category_slug=${params.slug}&${queryString}`);
    const result = await res.json();
    setData(result.data);
  };
  
  if (!data) return <Loading />;
  
  return (
    <div>
      <ProductGrid 
        products={data.products}
        navigationListId={data.navigation.list_id}
      />
      <Pagination pagination={data.pagination} />
    </div>
  );
}

// Product card with navigation context
function ProductCard({ 
  product, 
  index, 
  listId 
}: { 
  product: Product, 
  index: number, 
  listId: string 
}) {
  // Build URL with navigation context
  const href = `/products/${product.slug}?list=${listId}&pos=${index}`;
  
  return (
    <Link href={href}>
      <div className="product-card">
        <img src={product.thumbnail} alt={product.name} />
        <h3>{product.name}</h3>
        <p>{product.price}</p>
      </div>
    </Link>
  );
}
```

---

## Handling Edge Cases

### 1. Direct Product Page Access (No Context)

When users land directly on a product page (via search engine, bookmark, or shared link):

```tsx
// No navigation will be returned - this is expected behavior
{data.navigation ? (
  <ProductNavigation navigation={data.navigation} listId={null} />
) : (
  // Optionally show related products or category products instead
  <RelatedProducts productId={data.product.id} />
)}
```

### 2. Expired Navigation List

The `list_id` expires after 10 minutes. If navigation returns `null`:

```tsx
const fetchNavigation = async (productId: number, listId: string) => {
  const res = await fetch(`/api/products/${productId}/navigation?list=${listId}`);
  const data = await res.json();
  
  if (!data.data.list_id) {
    // List expired - could refresh from category
    return fetchFallbackNavigation(productId);
  }
  
  return data.data;
};

const fetchFallbackNavigation = async (productId: number) => {
  const res = await fetch(`/api/products/${productId}/navigation?fallback=true`);
  return res.json();
};
```

### 3. Infinite Scroll / Load More

For infinite scroll implementations, pass the current scroll position:

```tsx
// Track cumulative position across pages
const [cumulativeOffset, setCumulativeOffset] = useState(0);

const loadMore = async () => {
  const newProducts = await fetchNextPage();
  setCumulativeOffset(prev => prev + products.length);
  setProducts([...products, ...newProducts]);
};

// Position = cumulativeOffset + index within current products array
const getPosition = (index: number) => cumulativeOffset + index;
```

---

## Configuration Options

### Backend (.env)

```env
# Navigation cache TTL in seconds (default: 600 = 10 minutes)
PRODUCT_NAV_CACHE_TTL=600

# Maximum products in navigation list (default: 500)
PRODUCT_NAV_MAX_LIST_SIZE=500

# Enable category fallback when no list context (default: false)
PRODUCT_NAV_CATEGORY_FALLBACK=false

# Auto-include navigation in product responses (default: true)
PRODUCT_NAV_AUTO_INCLUDE=true

# Prefer Redis for caching (default: true)
PRODUCT_NAV_PREFER_REDIS=true
```

---

## Best Practices

1. **Always pass position hint** - Improves lookup performance from O(n) to O(1)

2. **Don't persist list_id** - It's ephemeral and expires after 10 minutes

3. **Handle missing navigation gracefully** - Show related products instead

4. **Use slug-based URLs** - Better for SEO: `/products/premium-bath-towel?list=abc123&pos=5`

5. **Consider preloading** - Prefetch prev/next product data for instant navigation

6. **Cache navigation locally** - Store in React state to avoid API calls on back navigation

---

## Migration from ID-based Navigation

If you previously had ID-based navigation:

```tsx
// ❌ Old (ID-based - incorrect)
const prevId = currentProductId - 1;
const nextId = currentProductId + 1;

// ✅ New (Context-based - correct)
const { prev, next } = navigation;
const prevSlug = prev?.slug;
const nextSlug = next?.slug;
```

---

## Support

For issues or questions about this implementation, check:
- [config/product_navigation.php](config/product_navigation.php) - Configuration options
- [app/Services/ProductNavigationService.php](app/Services/ProductNavigationService.php) - Service implementation

---

## Complete Working Example: Homepage to Product Detail Flow

This section shows the **exact flow** from start to finish.

### Scenario: User browses "Bestsellers" on homepage, clicks a product, uses Prev/Next

#### Step 1: Homepage fetches Bestsellers

```javascript
// API Call
const response = await fetch('https://api.example.com/api/products/bestsellers?per_page=8');
const { data } = await response.json();

// Response structure:
{
  "status": "success",
  "data": {
    "products": [
      { "id": 101, "name": "Luxury Bedsheet", "slug": "luxury-bedsheet", "price": 2500 },
      { "id": 205, "name": "Cotton Towel", "slug": "cotton-towel", "price": 800 },
      { "id": 89, "name": "Silk Pillow", "slug": "silk-pillow", "price": 1200 },
      { "id": 342, "name": "Bath Mat", "slug": "bath-mat", "price": 650 }
    ],
    "pagination": { "current_page": 1, "total": 4 },
    "navigation": {
      "list_id": "d4f8a2b1c3e5",   // ← SAVE THIS!
      "total": 4,
      "expires_at": "2026-01-10T16:00:00Z",
      "section": "bestsellers"
    }
  }
}
```

#### Step 2: Render product cards with navigation context

```jsx
function BestsellersSection({ products, navigation }) {
  return (
    <section>
      <h2>Bestsellers</h2>
      <div className="grid grid-cols-4 gap-4">
        {products.map((product, index) => (
          <a 
            key={product.id}
            href={`/products/${product.slug}?list=${navigation.list_id}&pos=${index}`}
          >
            <ProductCard product={product} />
          </a>
        ))}
      </div>
    </section>
  );
}

// Generated URLs:
// /products/luxury-bedsheet?list=d4f8a2b1c3e5&pos=0
// /products/cotton-towel?list=d4f8a2b1c3e5&pos=1
// /products/silk-pillow?list=d4f8a2b1c3e5&pos=2
// /products/bath-mat?list=d4f8a2b1c3e5&pos=3
```

#### Step 3: User clicks "Cotton Towel" (position 1)

Browser navigates to: `/products/cotton-towel?list=d4f8a2b1c3e5&pos=1`

#### Step 4: Product detail page fetches with context

```javascript
// Read query params from URL
const listId = searchParams.get('list');  // "d4f8a2b1c3e5"
const pos = searchParams.get('pos');      // "1"

// API Call - INCLUDE the query params!
const response = await fetch(
  `https://api.example.com/api/products/slug/cotton-towel?list=${listId}&pos=${pos}`
);
const { data } = await response.json();

// Response structure:
{
  "status": "success",
  "data": {
    "product": {
      "id": 205,
      "name": "Cotton Towel",
      "slug": "cotton-towel",
      "price": 800,
      "description": "...",
      // ... full product details
    },
    "navigation": {
      "prev": {
        "id": 101,
        "name": "Luxury Bedsheet",
        "slug": "luxury-bedsheet",
        "price": 2500,
        "thumbnail": "https://example.com/images/product/thumb/bedsheet.jpg",
        "position": 0
      },
      "next": {
        "id": 89,
        "name": "Silk Pillow",
        "slug": "silk-pillow",
        "price": 1200,
        "thumbnail": "https://example.com/images/product/thumb/pillow.jpg",
        "position": 2
      },
      "position": 2,      // 1-indexed for display (user sees "2 of 4")
      "total": 4,
      "list_id": "d4f8a2b1c3e5"
    }
  }
}
```

#### Step 5: Render prev/next navigation

```jsx
function ProductPage({ product, navigation, listId }) {
  return (
    <div>
      {/* Navigation Bar */}
      {navigation && (
        <div className="flex justify-between items-center py-4 border-b">
          {/* Previous */}
          {navigation.prev ? (
            <a href={`/products/${navigation.prev.slug}?list=${listId}&pos=${navigation.prev.position}`}>
              ← {navigation.prev.name}
            </a>
          ) : (
            <span className="text-gray-300">← Previous</span>
          )}
          
          {/* Position */}
          <span>{navigation.position} of {navigation.total}</span>
          
          {/* Next */}
          {navigation.next ? (
            <a href={`/products/${navigation.next.slug}?list=${listId}&pos=${navigation.next.position}`}>
              {navigation.next.name} →
            </a>
          ) : (
            <span className="text-gray-300">Next →</span>
          )}
        </div>
      )}
      
      {/* Product Details */}
      <h1>{product.name}</h1>
      <p>{product.price}</p>
      {/* ... rest of product details */}
    </div>
  );
}
```

#### Step 6: User clicks "Next" → Goes to Silk Pillow

Browser navigates to: `/products/silk-pillow?list=d4f8a2b1c3e5&pos=2`

The cycle continues! API returns:
```json
{
  "navigation": {
    "prev": { "slug": "cotton-towel", "position": 1 },
    "next": { "slug": "bath-mat", "position": 3 },
    "position": 3,
    "total": 4
  }
}
```

#### Step 7: User reaches last product (Bath Mat)

```json
{
  "navigation": {
    "prev": { "slug": "silk-pillow", "position": 2 },
    "next": null,  // ← No next product!
    "position": 4,
    "total": 4
  }
}
```

"Next" button should be disabled/hidden.

---

## Common Mistakes to Avoid

### ❌ Mistake 1: Not passing query params to API

```javascript
// WRONG - no navigation will be returned
const response = await fetch(`/api/products/slug/${slug}`);

// CORRECT - pass list and pos
const response = await fetch(`/api/products/slug/${slug}?list=${listId}&pos=${pos}`);
```

### ❌ Mistake 2: Using wrong position in prev/next URLs

```javascript
// WRONG - using current position
href={`/products/${navigation.next.slug}?list=${listId}&pos=${currentPosition + 1}`}

// CORRECT - use position from the navigation response
href={`/products/${navigation.next.slug}?list=${listId}&pos=${navigation.next.position}`}
```

### ❌ Mistake 3: Forgetting to handle missing navigation

```jsx
// WRONG - will crash if navigation is null
<div>{navigation.position} of {navigation.total}</div>

// CORRECT - handle gracefully
{navigation && (
  <div>{navigation.position} of {navigation.total}</div>
)}
```

### ❌ Mistake 4: Storing list_id in localStorage/database

```javascript
// WRONG - list_id expires in 10 minutes
localStorage.setItem('lastListId', listId);

// CORRECT - only pass via URL query params
// If user bookmarks/shares URL, navigation will gracefully degrade
```

---

## When Navigation Won't Be Available

Navigation will be `null` or empty in these cases:

1. **Direct URL access** - User types URL directly or uses bookmark
2. **Shared links** - Another user shares the product link
3. **Search engine traffic** - User comes from Google
4. **Expired list** - More than 10 minutes since listing was loaded
5. **Product removed from list** - Product no longer matches original filters

**This is expected behavior!** Simply hide the prev/next buttons:

```jsx
{navigation?.prev || navigation?.next ? (
  <ProductNavigation navigation={navigation} listId={listId} />
) : (
  // Optionally show "You may also like" or similar
  <RelatedProducts productId={product.id} />
)}
```
