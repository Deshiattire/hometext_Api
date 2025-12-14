# Next.js 16.7 - Query Parameters & API Integration Guide

## 📋 Complete Guide: How to Pass Query Parameters from Frontend to Backend

This guide shows you exactly how to construct and pass query parameters from your Next.js 16.7 frontend to your Laravel backend API.

---

## 🎯 API Endpoint Reference

Your backend endpoint:
```
GET /api/products?search={query}&category_id={id}&brand_id={id}&per_page={number}&page={number}&order_by={field}&direction={asc|desc}
```

**Available Query Parameters:**
- `search` - Search term (string)
- `category_id` - Filter by category (integer)
- `brand_id` - Filter by brand (integer)
- `status` - Filter by status (integer: 1=active, 0=inactive)
- `order_by` - Sort field: `id`, `name`, `price`, `created_at`, `updated_at`
- `direction` - Sort direction: `asc` or `desc`
- `per_page` - Items per page (integer, max 100, default 20)
- `page` - Page number (integer, default 1)

---

## 🔧 Method 1: Using Fetch API (Vanilla JavaScript)

### Basic Search Example

```typescript
'use client';

import { useState, useEffect } from 'react';

export function ProductSearch() {
  const [searchQuery, setSearchQuery] = useState('');
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchProducts = async () => {
    setLoading(true);
    
    // Build query parameters
    const params = new URLSearchParams();
    
    if (searchQuery) {
      params.append('search', searchQuery);
    }
    params.append('per_page', '20');
    params.append('page', '1');
    params.append('order_by', 'created_at');
    params.append('direction', 'desc');

    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/api/products?${params.toString()}`
      );
      
      const data = await response.json();
      setProducts(data.data.products);
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <input
        type="text"
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
        placeholder="Search products..."
      />
      <button onClick={fetchProducts}>Search</button>
      
      {loading && <p>Loading...</p>}
      {products.map(product => (
        <div key={product.id}>{product.name}</div>
      ))}
    </div>
  );
}
```

### Advanced Example with All Filters

```typescript
'use client';

import { useState, useEffect } from 'react';

interface SearchFilters {
  search?: string;
  category_id?: number;
  brand_id?: number;
  status?: number;
  order_by?: 'id' | 'name' | 'price' | 'created_at' | 'updated_at';
  direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export function AdvancedProductSearch() {
  const [filters, setFilters] = useState<SearchFilters>({
    per_page: 20,
    page: 1,
    order_by: 'created_at',
    direction: 'desc',
  });
  const [products, setProducts] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);

  // Build query string from filters
  const buildQueryString = (filters: SearchFilters): string => {
    const params = new URLSearchParams();
    
    // Only add parameters that have values
    if (filters.search) {
      params.append('search', filters.search);
    }
    if (filters.category_id) {
      params.append('category_id', filters.category_id.toString());
    }
    if (filters.brand_id) {
      params.append('brand_id', filters.brand_id.toString());
    }
    if (filters.status !== undefined) {
      params.append('status', filters.status.toString());
    }
    if (filters.order_by) {
      params.append('order_by', filters.order_by);
    }
    if (filters.direction) {
      params.append('direction', filters.direction);
    }
    if (filters.per_page) {
      params.append('per_page', filters.per_page.toString());
    }
    if (filters.page) {
      params.append('page', filters.page.toString());
    }

    return params.toString();
  };

  const fetchProducts = async () => {
    setLoading(true);
    
    try {
      const queryString = buildQueryString(filters);
      const url = `${process.env.NEXT_PUBLIC_API_URL}/api/products?${queryString}`;
      
      console.log('Fetching:', url); // Debug: See the actual URL
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      
      // Your API returns: { data: { products: [...], pagination: {...} } }
      setProducts(result.data.products);
      setPagination(result.data.pagination);
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch when filters change
  useEffect(() => {
    fetchProducts();
  }, [filters]);

  return (
    <div className="p-4">
      {/* Search Input */}
      <input
        type="text"
        value={filters.search || ''}
        onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
        placeholder="Search products..."
        className="border p-2 rounded w-full mb-4"
      />

      {/* Filters */}
      <div className="grid grid-cols-2 gap-4 mb-4">
        <select
          value={filters.category_id || ''}
          onChange={(e) => setFilters({ 
            ...filters, 
            category_id: e.target.value ? parseInt(e.target.value) : undefined,
            page: 1 
          })}
          className="border p-2 rounded"
        >
          <option value="">All Categories</option>
          <option value="1">Category 1</option>
          <option value="2">Category 2</option>
        </select>

        <select
          value={filters.brand_id || ''}
          onChange={(e) => setFilters({ 
            ...filters, 
            brand_id: e.target.value ? parseInt(e.target.value) : undefined,
            page: 1 
          })}
          className="border p-2 rounded"
        >
          <option value="">All Brands</option>
          <option value="1">Brand 1</option>
          <option value="2">Brand 2</option>
        </select>

        <select
          value={filters.order_by || 'created_at'}
          onChange={(e) => setFilters({ ...filters, order_by: e.target.value as any })}
          className="border p-2 rounded"
        >
          <option value="created_at">Newest</option>
          <option value="name">Name</option>
          <option value="price">Price</option>
        </select>

        <select
          value={filters.direction || 'desc'}
          onChange={(e) => setFilters({ ...filters, direction: e.target.value as 'asc' | 'desc' })}
          className="border p-2 rounded"
        >
          <option value="desc">Descending</option>
          <option value="asc">Ascending</option>
        </select>
      </div>

      {/* Results */}
      {loading && <p>Loading...</p>}
      
      <div className="grid grid-cols-3 gap-4">
        {products.map((product: any) => (
          <div key={product.id} className="border p-4 rounded">
            <h3>{product.name}</h3>
            <p>${product.price}</p>
          </div>
        ))}
      </div>

      {/* Pagination */}
      {pagination && (
        <div className="mt-4 flex gap-2">
          <button
            onClick={() => setFilters({ ...filters, page: (filters.page || 1) - 1 })}
            disabled={!pagination.has_more || filters.page === 1}
            className="px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-300"
          >
            Previous
          </button>
          <span className="px-4 py-2">
            Page {pagination.current_page} of {pagination.last_page}
          </span>
          <button
            onClick={() => setFilters({ ...filters, page: (filters.page || 1) + 1 })}
            disabled={!pagination.has_more}
            className="px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-300"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
```

---

## 🔗 Method 2: Using Next.js URL SearchParams (Server Components)

### Reading Query Params from URL (Server Component)

```typescript
// app/search/page.tsx
import { Suspense } from 'react';

export default function SearchPage({
  searchParams,
}: {
  searchParams: { [key: string]: string | string[] | undefined };
}) {
  return (
    <Suspense fallback={<div>Loading...</div>}>
      <SearchContent searchParams={searchParams} />
    </Suspense>
  );
}

async function SearchContent({
  searchParams,
}: {
  searchParams: { [key: string]: string | string[] | undefined };
}) {
  // Extract query parameters
  const search = typeof searchParams.search === 'string' ? searchParams.search : undefined;
  const categoryId = typeof searchParams.category_id === 'string' 
    ? parseInt(searchParams.category_id) 
    : undefined;
  const brandId = typeof searchParams.brand_id === 'string' 
    ? parseInt(searchParams.brand_id) 
    : undefined;
  const page = typeof searchParams.page === 'string' 
    ? parseInt(searchParams.page) 
    : 1;
  const perPage = typeof searchParams.per_page === 'string' 
    ? parseInt(searchParams.per_page) 
    : 20;
  const orderBy = typeof searchParams.order_by === 'string' 
    ? searchParams.order_by 
    : 'created_at';
  const direction = typeof searchParams.direction === 'string' 
    ? searchParams.direction 
    : 'desc';

  // Build query string
  const params = new URLSearchParams();
  if (search) params.append('search', search);
  if (categoryId) params.append('category_id', categoryId.toString());
  if (brandId) params.append('brand_id', brandId.toString());
  params.append('page', page.toString());
  params.append('per_page', perPage.toString());
  params.append('order_by', orderBy);
  params.append('direction', direction);

  // Fetch from API
  const response = await fetch(
    `${process.env.API_URL}/api/products?${params.toString()}`,
    {
      cache: 'no-store', // or 'force-cache' for static
    }
  );

  const data = await response.json();
  const products = data.data.products;
  const pagination = data.data.pagination;

  return (
    <div>
      <h1>Search Results</h1>
      {products.map((product: any) => (
        <div key={product.id}>{product.name}</div>
      ))}
    </div>
  );
}
```

---

## 🎣 Method 3: Using Custom Hook with URL Sync

### Complete Hook Implementation

```typescript
'use client';

// hooks/useProductSearch.ts
import { useState, useEffect, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

interface SearchFilters {
  search?: string;
  category_id?: number;
  brand_id?: number;
  status?: number;
  order_by?: 'id' | 'name' | 'price' | 'created_at' | 'updated_at';
  direction?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

interface Product {
  id: number;
  name: string;
  price: number;
  // ... other fields
}

interface ApiResponse {
  data: {
    products: Product[];
    pagination: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
      has_more: boolean;
    };
  };
}

export function useProductSearch() {
  const router = useRouter();
  const searchParams = useSearchParams();

  // Initialize filters from URL or defaults
  const [filters, setFilters] = useState<SearchFilters>(() => {
    return {
      search: searchParams.get('search') || undefined,
      category_id: searchParams.get('category_id') 
        ? parseInt(searchParams.get('category_id')!) 
        : undefined,
      brand_id: searchParams.get('brand_id') 
        ? parseInt(searchParams.get('brand_id')!) 
        : undefined,
      status: searchParams.get('status') 
        ? parseInt(searchParams.get('status')!) 
        : undefined,
      order_by: (searchParams.get('order_by') as any) || 'created_at',
      direction: (searchParams.get('direction') as 'asc' | 'desc') || 'desc',
      per_page: searchParams.get('per_page') 
        ? parseInt(searchParams.get('per_page')!) 
        : 20,
      page: searchParams.get('page') 
        ? parseInt(searchParams.get('page')!) 
        : 1,
    };
  });

  const [products, setProducts] = useState<Product[]>([]);
  const [pagination, setPagination] = useState<ApiResponse['data']['pagination'] | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Build query string from filters
  const buildQueryString = useCallback((filters: SearchFilters): string => {
    const params = new URLSearchParams();
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, value.toString());
      }
    });

    return params.toString();
  }, []);

  // Update URL when filters change
  useEffect(() => {
    const queryString = buildQueryString(filters);
    const newUrl = queryString ? `/search?${queryString}` : '/search';
    router.replace(newUrl, { scroll: false });
  }, [filters, buildQueryString, router]);

  // Fetch products
  const fetchProducts = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const queryString = buildQueryString(filters);
      const url = `${process.env.NEXT_PUBLIC_API_URL}/api/products?${queryString}`;
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data: ApiResponse = await response.json();
      setProducts(data.data.products);
      setPagination(data.data.pagination);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch products');
      setProducts([]);
      setPagination(null);
    } finally {
      setLoading(false);
    }
  }, [filters, buildQueryString]);

  // Fetch when filters change
  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  // Update filters
  const updateFilters = useCallback((newFilters: Partial<SearchFilters>) => {
    setFilters(prev => ({
      ...prev,
      ...newFilters,
      page: newFilters.page !== undefined ? newFilters.page : 1, // Reset page on filter change
    }));
  }, []);

  return {
    products,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
    refetch: fetchProducts,
  };
}
```

### Using the Hook in a Component

```typescript
'use client';

// components/ProductSearchPage.tsx
import { useProductSearch } from '@/hooks/useProductSearch';

export function ProductSearchPage() {
  const {
    products,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
  } = useProductSearch();

  return (
    <div className="p-4">
      {/* Search Input */}
      <input
        type="text"
        value={filters.search || ''}
        onChange={(e) => updateFilters({ search: e.target.value })}
        placeholder="Search products..."
        className="w-full p-2 border rounded mb-4"
      />

      {/* Category Filter */}
      <select
        value={filters.category_id || ''}
        onChange={(e) => updateFilters({ 
          category_id: e.target.value ? parseInt(e.target.value) : undefined 
        })}
        className="p-2 border rounded mb-4"
      >
        <option value="">All Categories</option>
        <option value="1">Category 1</option>
        <option value="2">Category 2</option>
      </select>

      {/* Results */}
      {loading && <p>Loading...</p>}
      {error && <p className="text-red-500">{error}</p>}
      
      <div className="grid grid-cols-3 gap-4">
        {products.map((product) => (
          <div key={product.id} className="border p-4 rounded">
            <h3>{product.name}</h3>
            <p>${product.price}</p>
          </div>
        ))}
      </div>

      {/* Pagination */}
      {pagination && (
        <div className="mt-4">
          <button
            onClick={() => updateFilters({ page: (filters.page || 1) - 1 })}
            disabled={pagination.current_page === 1}
          >
            Previous
          </button>
          <span>Page {pagination.current_page} of {pagination.last_page}</span>
          <button
            onClick={() => updateFilters({ page: (filters.page || 1) + 1 })}
            disabled={!pagination.has_more}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
```

---

## 📝 Method 4: Using Next.js Link for Navigation

### Creating Shareable Search Links

```typescript
'use client';

import Link from 'next/link';

export function SearchLink() {
  // Build query parameters
  const searchParams = new URLSearchParams({
    search: 'shirt',
    category_id: '3',
    brand_id: '5',
    per_page: '20',
    page: '1',
    order_by: 'price',
    direction: 'asc',
  });

  return (
    <Link href={`/search?${searchParams.toString()}`}>
      View Shirts from Brand 5
    </Link>
  );
}

// Or dynamically
export function DynamicSearchLink({ 
  search, 
  categoryId, 
  brandId 
}: { 
  search?: string; 
  categoryId?: number; 
  brandId?: number;
}) {
  const params = new URLSearchParams();
  
  if (search) params.append('search', search);
  if (categoryId) params.append('category_id', categoryId.toString());
  if (brandId) params.append('brand_id', brandId.toString());
  params.append('per_page', '20');
  params.append('order_by', 'created_at');
  params.append('direction', 'desc');

  return (
    <Link href={`/search?${params.toString()}`}>
      View Results
    </Link>
  );
}
```

---

## 🔄 Method 5: Using Router.push() for Programmatic Navigation

```typescript
'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';

export function SearchForm() {
  const router = useRouter();
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    
    const params = new URLSearchParams();
    if (searchQuery) {
      params.append('search', searchQuery);
    }
    params.append('per_page', '20');
    params.append('page', '1');

    // Navigate to search page with query params
    router.push(`/search?${params.toString()}`);
  };

  return (
    <form onSubmit={handleSearch}>
      <input
        type="text"
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
        placeholder="Search..."
      />
      <button type="submit">Search</button>
    </form>
  );
}
```

---

## 🎯 Real-World Example: Complete Search Page

```typescript
'use client';

// app/search/page.tsx
import { Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useProductSearch } from '@/hooks/useProductSearch';

export default function SearchPage() {
  return (
    <Suspense fallback={<SearchSkeleton />}>
      <SearchContent />
    </Suspense>
  );
}

function SearchContent() {
  const {
    products,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
  } = useProductSearch();

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Search Bar */}
      <div className="mb-6">
        <input
          type="text"
          value={filters.search || ''}
          onChange={(e) => updateFilters({ search: e.target.value })}
          placeholder="Search products, categories, brands..."
          className="w-full max-w-2xl px-4 py-3 border border-gray-300 rounded-lg 
                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      {/* Filters Row */}
      <div className="flex gap-4 mb-6 flex-wrap">
        <select
          value={filters.category_id || ''}
          onChange={(e) => updateFilters({ 
            category_id: e.target.value ? parseInt(e.target.value) : undefined 
          })}
          className="px-4 py-2 border rounded-lg"
        >
          <option value="">All Categories</option>
          {/* Fetch from API */}
        </select>

        <select
          value={filters.brand_id || ''}
          onChange={(e) => updateFilters({ 
            brand_id: e.target.value ? parseInt(e.target.value) : undefined 
          })}
          className="px-4 py-2 border rounded-lg"
        >
          <option value="">All Brands</option>
          {/* Fetch from API */}
        </select>

        <select
          value={filters.order_by || 'created_at'}
          onChange={(e) => updateFilters({ order_by: e.target.value as any })}
          className="px-4 py-2 border rounded-lg"
        >
          <option value="created_at">Newest First</option>
          <option value="name">Name (A-Z)</option>
          <option value="price">Price (Low to High)</option>
        </select>

        <select
          value={filters.direction || 'desc'}
          onChange={(e) => updateFilters({ direction: e.target.value as 'asc' | 'desc' })}
          className="px-4 py-2 border rounded-lg"
        >
          <option value="desc">Descending</option>
          <option value="asc">Ascending</option>
        </select>
      </div>

      {/* Results Count */}
      {pagination && (
        <p className="text-gray-600 mb-4">
          Showing {pagination.total} results
        </p>
      )}

      {/* Loading State */}
      {loading && (
        <div className="text-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        </div>
      )}

      {/* Error State */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <p className="text-red-800">{error}</p>
        </div>
      )}

      {/* Products Grid */}
      {!loading && !error && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {products.map((product) => (
            <div key={product.id} className="border rounded-lg p-4 hover:shadow-lg transition">
              <h3 className="font-semibold text-lg mb-2">{product.name}</h3>
              <p className="text-blue-600 font-bold">${product.price}</p>
            </div>
          ))}
        </div>
      )}

      {/* Empty State */}
      {!loading && !error && products.length === 0 && (
        <div className="text-center py-12">
          <p className="text-gray-500 text-lg">No products found</p>
          <p className="text-gray-400 text-sm mt-2">
            Try adjusting your search or filters
          </p>
        </div>
      )}

      {/* Pagination */}
      {pagination && pagination.last_page > 1 && (
        <div className="flex justify-center items-center gap-4 mt-8">
          <button
            onClick={() => updateFilters({ page: (filters.page || 1) - 1 })}
            disabled={pagination.current_page === 1}
            className="px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          
          <span className="text-gray-600">
            Page {pagination.current_page} of {pagination.last_page}
          </span>
          
          <button
            onClick={() => updateFilters({ page: (filters.page || 1) + 1 })}
            disabled={!pagination.has_more}
            className="px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}

function SearchSkeleton() {
  return (
    <div className="container mx-auto px-4 py-8">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 max-w-2xl"></div>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
          <div key={i} className="h-64 bg-gray-200 rounded-lg animate-pulse"></div>
        ))}
      </div>
    </div>
  );
}
```

---

## 🔍 Debugging: See What's Being Sent

### Add Console Logging

```typescript
const fetchProducts = async () => {
  const queryString = buildQueryString(filters);
  const url = `${process.env.NEXT_PUBLIC_API_URL}/api/products?${queryString}`;
  
  // Debug: Log the URL being called
  console.log('🔍 API Call:', url);
  console.log('📋 Filters:', filters);
  console.log('🔗 Query String:', queryString);
  
  const response = await fetch(url);
  const data = await response.json();
  
  // Debug: Log the response
  console.log('✅ Response:', data);
  
  return data;
};
```

---

## ✅ Quick Reference: Query Parameter Examples

### Example 1: Simple Search
```
GET /api/products?search=shirt
```

### Example 2: Search with Category
```
GET /api/products?search=cotton&category_id=3
```

### Example 3: Search with Multiple Filters
```
GET /api/products?search=electronics&category_id=5&brand_id=2&per_page=30&order_by=price&direction=asc
```

### Example 4: Pagination
```
GET /api/products?search=shirt&page=2&per_page=20
```

### Example 5: Sort Only (No Search)
```
GET /api/products?order_by=price&direction=asc&per_page=40
```

---

## 🎯 Key Points to Remember

1. **Always encode query parameters** - Use `URLSearchParams` or `encodeURIComponent()`
2. **Remove empty/null values** - Don't send empty parameters
3. **Reset page to 1** - When filters change, reset pagination
4. **Debounce search input** - Wait 300-500ms before searching
5. **Sync with URL** - Keep search state in URL for shareability
6. **Handle loading/error states** - Always show feedback to users
7. **Use TypeScript** - Type your filters and responses

---

## 🚀 Environment Variables

Make sure you have this in your `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
# or for production:
# NEXT_PUBLIC_API_URL=https://your-api-domain.com/api
```

---

This guide covers all the ways to pass query parameters from your Next.js frontend to your Laravel backend API. Choose the method that best fits your use case!
