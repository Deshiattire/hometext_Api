# Complete Filters Guide - Next.js Frontend Implementation

## 📋 All Available Filters

Your API now supports comprehensive filtering. Here's how to use each filter from your Next.js frontend.

---

## 🎯 Complete Filter List

### Category Filters
- `category_id` - Filter by main category
- `sub_category_id` - Filter by sub category
- `child_sub_category_id` - Filter by child sub category

### Product Filters
- `search` - Search term (searches name, SKU, description, categories)
- `brand_id` - Filter by brand
- `status` - Filter by status (1 = active, 0 = inactive)

### Price Filters
- `min_price` - Minimum price (numeric)
- `max_price` - Maximum price (numeric)

### Attribute Filters
- `color` - Filter by color name (string, e.g., "red", "blue")
- `attribute_id` - Filter by attribute ID
- `attribute_value_id` - Filter by single attribute value ID
- `attribute_value_ids` - Filter by multiple attribute value IDs (array)

### Stock Filters
- `in_stock` - Only show products in stock (boolean: true/false)
- `stock_status` - Filter by stock status: `in_stock`, `out_of_stock`, `on_backorder`, `preorder`

### Sorting & Pagination
- `order_by` - Sort field: `id`, `name`, `price`, `created_at`, `updated_at`
- `direction` - Sort direction: `asc` or `desc`
- `per_page` - Items per page (1-100, default: 20)
- `page` - Page number (default: 1)

---

## 🔧 Complete Implementation Example

### 1. Updated Search Hook with All Filters

```typescript
'use client';

// hooks/useProductSearch.ts
import { useState, useEffect, useCallback } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useDebounce } from './useDebounce';

interface SearchFilters {
  // Search
  search?: string;
  
  // Categories
  category_id?: number;
  sub_category_id?: number;
  child_sub_category_id?: number;
  
  // Brand
  brand_id?: number;
  
  // Status
  status?: number;
  
  // Price Range
  min_price?: number;
  max_price?: number;
  
  // Attributes
  color?: string;
  attribute_id?: number;
  attribute_value_id?: number;
  attribute_value_ids?: number[];
  
  // Stock
  in_stock?: boolean;
  stock_status?: 'in_stock' | 'out_of_stock' | 'on_backorder' | 'preorder';
  
  // Sorting & Pagination
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

  // Initialize filters from URL
  const [filters, setFilters] = useState<SearchFilters>(() => {
    return {
      search: searchParams.get('search') || undefined,
      category_id: searchParams.get('category_id') 
        ? parseInt(searchParams.get('category_id')!) 
        : undefined,
      sub_category_id: searchParams.get('sub_category_id') 
        ? parseInt(searchParams.get('sub_category_id')!) 
        : undefined,
      child_sub_category_id: searchParams.get('child_sub_category_id') 
        ? parseInt(searchParams.get('child_sub_category_id')!) 
        : undefined,
      brand_id: searchParams.get('brand_id') 
        ? parseInt(searchParams.get('brand_id')!) 
        : undefined,
      min_price: searchParams.get('min_price') 
        ? parseFloat(searchParams.get('min_price')!) 
        : undefined,
      max_price: searchParams.get('max_price') 
        ? parseFloat(searchParams.get('max_price')!) 
        : undefined,
      color: searchParams.get('color') || undefined,
      attribute_value_id: searchParams.get('attribute_value_id') 
        ? parseInt(searchParams.get('attribute_value_id')!) 
        : undefined,
      attribute_value_ids: searchParams.get('attribute_value_ids') 
        ? searchParams.get('attribute_value_ids')!.split(',').map(Number)
        : undefined,
      in_stock: searchParams.get('in_stock') === 'true' ? true : undefined,
      stock_status: searchParams.get('stock_status') as any || undefined,
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

  const debouncedSearch = useDebounce(filters.search, 300);

  // Update search when debounced value changes
  useEffect(() => {
    if (debouncedSearch !== filters.search) {
      setFilters(prev => ({ ...prev, search: debouncedSearch, page: 1 }));
    }
  }, [debouncedSearch]);

  // Build query string
  const buildQueryString = useCallback((filters: SearchFilters): string => {
    const params = new URLSearchParams();
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        if (Array.isArray(value)) {
          // Handle arrays (attribute_value_ids)
          params.append(key, value.join(','));
        } else if (typeof value === 'boolean') {
          // Handle booleans
          params.append(key, value.toString());
        } else {
          params.append(key, value.toString());
        }
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

  // Clear all filters
  const clearFilters = useCallback(() => {
    setFilters({
      order_by: 'created_at',
      direction: 'desc',
      per_page: 20,
      page: 1,
    });
    router.replace('/search');
  }, [router]);

  return {
    products,
    pagination,
    loading,
    error,
    filters,
    updateFilters,
    clearFilters,
    refetch: fetchProducts,
  };
}
```

---

## 🎨 Complete Filter Component

```typescript
'use client';

// components/ProductFilters.tsx
import { useProductSearch } from '@/hooks/useProductSearch';
import { useState } from 'react';

export function ProductFilters() {
  const { filters, updateFilters, clearFilters } = useProductSearch();
  const [priceRange, setPriceRange] = useState({
    min: filters.min_price || 0,
    max: filters.max_price || 10000,
  });

  const hasActiveFilters = 
    filters.category_id ||
    filters.sub_category_id ||
    filters.child_sub_category_id ||
    filters.brand_id ||
    filters.min_price ||
    filters.max_price ||
    filters.color ||
    filters.in_stock;

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-900">Filters</h2>
        {hasActiveFilters && (
          <button
            onClick={clearFilters}
            className="text-sm text-blue-600 hover:text-blue-700"
          >
            Clear All
          </button>
        )}
      </div>

      {/* Category Filters */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Category
        </label>
        <select
          value={filters.category_id || ''}
          onChange={(e) => updateFilters({ 
            category_id: e.target.value ? parseInt(e.target.value) : undefined 
          })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
        >
          <option value="">All Categories</option>
          {/* Fetch from API */}
        </select>
      </div>

      {/* Sub Category Filter */}
      {filters.category_id && (
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Sub Category
          </label>
          <select
            value={filters.sub_category_id || ''}
            onChange={(e) => updateFilters({ 
              sub_category_id: e.target.value ? parseInt(e.target.value) : undefined 
            })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          >
            <option value="">All Sub Categories</option>
            {/* Fetch from API based on category_id */}
          </select>
        </div>
      )}

      {/* Child Sub Category Filter */}
      {filters.sub_category_id && (
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Child Sub Category
          </label>
          <select
            value={filters.child_sub_category_id || ''}
            onChange={(e) => updateFilters({ 
              child_sub_category_id: e.target.value ? parseInt(e.target.value) : undefined 
            })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          >
            <option value="">All Child Sub Categories</option>
            {/* Fetch from API based on sub_category_id */}
          </select>
        </div>
      )}

      {/* Brand Filter */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Brand
        </label>
        <select
          value={filters.brand_id || ''}
          onChange={(e) => updateFilters({ 
            brand_id: e.target.value ? parseInt(e.target.value) : undefined 
          })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
        >
          <option value="">All Brands</option>
          {/* Fetch from API */}
        </select>
      </div>

      {/* Price Range Filter */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Price Range
        </label>
        <div className="flex gap-2">
          <input
            type="number"
            placeholder="Min"
            value={priceRange.min || ''}
            onChange={(e) => setPriceRange({ ...priceRange, min: parseFloat(e.target.value) || 0 })}
            onBlur={() => updateFilters({ min_price: priceRange.min > 0 ? priceRange.min : undefined })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
          <input
            type="number"
            placeholder="Max"
            value={priceRange.max || ''}
            onChange={(e) => setPriceRange({ ...priceRange, max: parseFloat(e.target.value) || 10000 })}
            onBlur={() => updateFilters({ max_price: priceRange.max > 0 ? priceRange.max : undefined })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
      </div>

      {/* Color Filter */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Color
        </label>
        <div className="flex flex-wrap gap-2">
          {['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow'].map((color) => (
            <button
              key={color}
              onClick={() => updateFilters({ 
                color: filters.color === color.toLowerCase() ? undefined : color.toLowerCase() 
              })}
              className={`px-3 py-1 rounded-full text-sm border ${
                filters.color === color.toLowerCase()
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'bg-white text-gray-700 border-gray-300 hover:border-blue-500'
              }`}
            >
              {color}
            </button>
          ))}
        </div>
      </div>

      {/* Stock Status Filter */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Stock Status
        </label>
        <select
          value={filters.stock_status || ''}
          onChange={(e) => updateFilters({ 
            stock_status: e.target.value ? e.target.value as any : undefined 
          })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
        >
          <option value="">All</option>
          <option value="in_stock">In Stock</option>
          <option value="out_of_stock">Out of Stock</option>
          <option value="on_backorder">On Backorder</option>
          <option value="preorder">Preorder</option>
        </select>
      </div>

      {/* In Stock Only Toggle */}
      <div className="flex items-center">
        <input
          type="checkbox"
          id="in_stock"
          checked={filters.in_stock || false}
          onChange={(e) => updateFilters({ in_stock: e.target.checked || undefined })}
          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        />
        <label htmlFor="in_stock" className="ml-2 text-sm text-gray-700">
          Show only in-stock items
        </label>
      </div>

      {/* Sort By */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Sort By
        </label>
        <select
          value={filters.order_by || 'created_at'}
          onChange={(e) => updateFilters({ order_by: e.target.value as any })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
        >
          <option value="created_at">Newest</option>
          <option value="name">Name (A-Z)</option>
          <option value="price">Price (Low to High)</option>
          <option value="price">Price (High to Low)</option>
        </select>
      </div>
    </div>
  );
}
```

---

## 📝 Query Parameter Examples

### Example 1: Basic Search with Category
```
GET /api/products?search=shirt&category_id=3
```

### Example 2: Multiple Category Filters
```
GET /api/products?category_id=1&sub_category_id=5&child_sub_category_id=10
```

### Example 3: Price Range Filter
```
GET /api/products?min_price=100&max_price=500
```

### Example 4: Color Filter
```
GET /api/products?color=red
```

### Example 5: Multiple Filters Combined
```
GET /api/products?search=cotton&category_id=3&brand_id=5&min_price=50&max_price=200&color=blue&in_stock=true&order_by=price&direction=asc
```

### Example 6: Attribute Value Filter
```
GET /api/products?attribute_value_id=25
```

### Example 7: Multiple Attribute Values (OR condition)
```
GET /api/products?attribute_value_ids=25,26,27
```

### Example 8: Stock Status Filter
```
GET /api/products?stock_status=in_stock
```

---

## 🎯 Frontend Usage Examples

### Example 1: Filter by Category Hierarchy

```typescript
// When user selects a category, show sub-categories
const handleCategoryChange = (categoryId: number) => {
  updateFilters({ 
    category_id: categoryId,
    sub_category_id: undefined, // Reset sub category
    child_sub_category_id: undefined, // Reset child sub category
  });
};

// When user selects a sub-category, show child sub-categories
const handleSubCategoryChange = (subCategoryId: number) => {
  updateFilters({ 
    sub_category_id: subCategoryId,
    child_sub_category_id: undefined, // Reset child sub category
  });
};
```

### Example 2: Price Range Slider

```typescript
import { Range } from 'react-range';

export function PriceRangeFilter() {
  const { filters, updateFilters } = useProductSearch();
  const [values, setValues] = useState([
    filters.min_price || 0,
    filters.max_price || 10000,
  ]);

  return (
    <div>
      <label className="block text-sm font-medium mb-2">
        Price: ${values[0]} - ${values[1]}
      </label>
      <Range
        values={values}
        step={10}
        min={0}
        max={10000}
        onChange={(values) => {
          setValues(values);
          updateFilters({
            min_price: values[0] > 0 ? values[0] : undefined,
            max_price: values[1] < 10000 ? values[1] : undefined,
          });
        }}
        renderTrack={({ props, children }) => (
          <div {...props} className="h-2 bg-gray-200 rounded">
            {children}
          </div>
        )}
        renderThumb={({ props }) => (
          <div {...props} className="h-5 w-5 bg-blue-500 rounded-full" />
        )}
      />
    </div>
  );
}
```

### Example 3: Color Swatches

```typescript
export function ColorFilter() {
  const { filters, updateFilters } = useProductSearch();
  const colors = ['red', 'blue', 'green', 'black', 'white', 'yellow'];

  return (
    <div>
      <label className="block text-sm font-medium mb-2">Color</label>
      <div className="flex gap-2">
        {colors.map((color) => (
          <button
            key={color}
            onClick={() => updateFilters({ 
              color: filters.color === color ? undefined : color 
            })}
            className={`w-8 h-8 rounded-full border-2 ${
              filters.color === color 
                ? 'border-blue-500 ring-2 ring-blue-300' 
                : 'border-gray-300'
            }`}
            style={{ backgroundColor: color }}
            title={color}
          />
        ))}
      </div>
    </div>
  );
}
```

### Example 4: Multiple Attribute Values (Checkboxes)

```typescript
export function AttributeFilter({ attributeId }: { attributeId: number }) {
  const { filters, updateFilters } = useProductSearch();
  const [attributeValues, setAttributeValues] = useState<number[]>([]);

  // Fetch attribute values from API
  // useEffect(() => { ... }, [attributeId]);

  const handleToggle = (valueId: number) => {
    const newValues = attributeValues.includes(valueId)
      ? attributeValues.filter(id => id !== valueId)
      : [...attributeValues, valueId];
    
    setAttributeValues(newValues);
    updateFilters({ 
      attribute_value_ids: newValues.length > 0 ? newValues : undefined 
    });
  };

  return (
    <div>
      <label className="block text-sm font-medium mb-2">Sizes</label>
      {attributeValues.map((value) => (
        <label key={value.id} className="flex items-center">
          <input
            type="checkbox"
            checked={attributeValues.includes(value.id)}
            onChange={() => handleToggle(value.id)}
            className="mr-2"
          />
          {value.name}
        </label>
      ))}
    </div>
  );
}
```

---

## ✅ Best Practices

1. **Reset pagination** when filters change
2. **Debounce search input** (300-500ms)
3. **Show active filter count** to users
4. **Clear filters** button when filters are active
5. **URL sync** - Keep filters in URL for shareability
6. **Loading states** - Show skeletons during filtering
7. **Empty states** - Show message when no results
8. **Filter persistence** - Remember user preferences (localStorage)

---

## 🚀 Complete Search Page Example

```typescript
'use client';

import { useProductSearch } from '@/hooks/useProductSearch';
import { ProductFilters } from '@/components/ProductFilters';
import { SearchBar } from '@/components/SearchBar';

export default function SearchPage() {
  const { products, loading, error, filters, updateFilters } = useProductSearch();

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex gap-8">
        {/* Filters Sidebar */}
        <aside className="hidden lg:block w-64">
          <ProductFilters />
        </aside>

        {/* Main Content */}
        <main className="flex-1">
          <SearchBar
            initialValue={filters.search}
            onSearch={(query) => updateFilters({ search: query })}
          />

          {/* Results */}
          {loading && <div>Loading...</div>}
          {error && <div className="text-red-500">{error}</div>}
          
          <div className="grid grid-cols-3 gap-4 mt-6">
            {products.map((product) => (
              <div key={product.id}>{product.name}</div>
            ))}
          </div>
        </main>
      </div>
    </div>
  );
}
```

---

This guide covers all available filters and how to implement them in your Next.js frontend. All filters work through the same `/api/products` endpoint with query parameters!


