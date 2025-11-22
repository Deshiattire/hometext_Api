# Multiple Filters with Main Index Endpoint - Detailed Explanation

## 🎯 Why Use Query Parameters Instead of Dedicated Endpoints?

The main index endpoint (`GET /api/products`) accepts **multiple query parameters** that can be **combined together**. This is different from dedicated endpoints which only handle **one filter at a time**.

---

## 1️⃣ Multiple Filters - Combining Filters Together

### ❌ Problem with Dedicated Endpoints
You **CANNOT** combine filters with dedicated endpoints:

```bash
# ❌ This doesn't exist - you can't combine category + brand
GET /api/products/category/5/brand/10  # This route doesn't exist!

# ❌ You'd need to make TWO separate API calls
GET /api/products/category/5      # Get category products
GET /api/products/brand/10         # Get brand products
# Then filter in frontend (inefficient!)
```

### ✅ Solution: Main Index Endpoint with Query Parameters

You **CAN** combine multiple filters in one request:

```bash
# ✅ Combine category + brand + search in ONE request
GET /api/products?category_id=5&brand_id=10&search=bed&per_page=20
```

### Real-World Example: E-commerce Search Page

**Scenario:** User wants to find "bed sheets" from "Hometex" brand in "Bedding" category

```javascript
// Frontend Code
async function searchProducts(filters) {
  // Build query string dynamically
  const params = new URLSearchParams();
  
  if (filters.categoryId) {
    params.append('category_id', filters.categoryId);
  }
  
  if (filters.brandId) {
    params.append('brand_id', filters.brandId);
  }
  
  if (filters.searchTerm) {
    params.append('search', filters.searchTerm);
  }
  
  params.append('per_page', 20);
  params.append('order_by', 'price');
  params.append('direction', 'asc');
  
  // Single API call with all filters
  const response = await fetch(`/api/products?${params.toString()}`);
  const data = await response.json();
  
  return data;
}

// Usage
const results = await searchProducts({
  categoryId: 5,        // Bedding category
  brandId: 10,          // Hometex brand
  searchTerm: 'bed'     // Search term
});

// This makes ONE request:
// GET /api/products?category_id=5&brand_id=10&search=bed&per_page=20&order_by=price&direction=asc
```

### Available Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Filter by category | `?category_id=5` |
| `brand_id` | integer | Filter by brand | `?brand_id=10` |
| `search` | string | Search in name/SKU | `?search=bed` |
| `status` | integer | Filter by status (1=active, 0=inactive) | `?status=1` |
| `order_by` | string | Sort field (id, name, price, created_at, updated_at) | `?order_by=price` |
| `direction` | string | Sort direction (asc, desc) | `?direction=asc` |
| `per_page` | integer | Items per page (1-100) | `?per_page=20` |
| `page` | integer | Page number | `?page=2` |

### Example API Calls

```bash
# Example 1: Category + Brand
GET /api/products?category_id=5&brand_id=10
# Returns: Products in category 5 AND brand 10

# Example 2: Category + Brand + Search
GET /api/products?category_id=5&brand_id=10&search=sheet
# Returns: Products in category 5 AND brand 10 AND name contains "sheet"

# Example 3: Category + Search + Sort by Price
GET /api/products?category_id=5&search=bed&order_by=price&direction=asc
# Returns: Products in category 5, name contains "bed", sorted by price (low to high)

# Example 4: Brand + Status + Sort by Name
GET /api/products?brand_id=10&status=1&order_by=name&direction=asc
# Returns: Active products from brand 10, sorted alphabetically

# Example 5: All filters combined
GET /api/products?category_id=5&brand_id=10&search=bed&status=1&order_by=price&direction=asc&per_page=50
# Returns: Active products in category 5, brand 10, name contains "bed", sorted by price, 50 per page
```

---

## 2️⃣ Admin Panel with Dynamic Filters

### Why Admin Panels Need Query Parameters

Admin panels typically have **multiple filter dropdowns** that users can enable/disable dynamically. Query parameters are perfect for this because:

1. **Flexible** - Users can select any combination of filters
2. **URL-friendly** - Filters are in URL, so users can bookmark/share filtered views
3. **State management** - Easy to sync URL with filter state

### Example: Admin Product Management Page

**UI Components:**
- Category dropdown (optional)
- Brand dropdown (optional)
- Search box (optional)
- Status toggle (optional)
- Sort dropdown (optional)
- Items per page selector

**Frontend Implementation:**

```javascript
// React/Vue Component Example
class ProductAdminPanel {
  constructor() {
    this.filters = {
      category_id: null,
      brand_id: null,
      search: '',
      status: null,
      order_by: 'created_at',
      direction: 'desc',
      per_page: 20
    };
  }
  
  // When user changes any filter
  updateFilter(key, value) {
    this.filters[key] = value;
    this.loadProducts();
  }
  
  // Build and make API call
  async loadProducts() {
    // Remove null/empty filters
    const activeFilters = Object.entries(this.filters)
      .filter(([key, value]) => value !== null && value !== '')
      .reduce((obj, [key, value]) => {
        obj[key] = value;
        return obj;
      }, {});
    
    // Build query string
    const params = new URLSearchParams(activeFilters);
    
    // Make API call
    const response = await fetch(`/api/products?${params.toString()}`);
    const data = await response.json();
    
    // Update UI
    this.renderProducts(data.data.products);
  }
}

// Usage
const adminPanel = new ProductAdminPanel();

// User selects category
adminPanel.updateFilter('category_id', 5);
// API Call: GET /api/products?category_id=5&order_by=created_at&direction=desc&per_page=20

// User also selects brand
adminPanel.updateFilter('brand_id', 10);
// API Call: GET /api/products?category_id=5&brand_id=10&order_by=created_at&direction=desc&per_page=20

// User types search
adminPanel.updateFilter('search', 'bed');
// API Call: GET /api/products?category_id=5&brand_id=10&search=bed&order_by=created_at&direction=desc&per_page=20

// User changes sort
adminPanel.updateFilter('order_by', 'price');
adminPanel.updateFilter('direction', 'asc');
// API Call: GET /api/products?category_id=5&brand_id=10&search=bed&order_by=price&direction=asc&per_page=20
```

### HTML Form Example

```html
<!-- Admin Filter Form -->
<form id="productFilters" onsubmit="return false;">
  <select name="category_id" onchange="applyFilters()">
    <option value="">All Categories</option>
    <option value="5">Bedding</option>
    <option value="6">Bath</option>
  </select>
  
  <select name="brand_id" onchange="applyFilters()">
    <option value="">All Brands</option>
    <option value="10">Hometex</option>
    <option value="11">Other Brand</option>
  </select>
  
  <input type="text" name="search" placeholder="Search..." onkeyup="applyFilters()">
  
  <select name="status" onchange="applyFilters()">
    <option value="">All Status</option>
    <option value="1">Active</option>
    <option value="0">Inactive</option>
  </select>
  
  <select name="order_by" onchange="applyFilters()">
    <option value="created_at">Date Added</option>
    <option value="name">Name</option>
    <option value="price">Price</option>
  </select>
  
  <select name="direction" onchange="applyFilters()">
    <option value="desc">Descending</option>
    <option value="asc">Ascending</option>
  </select>
</form>

<script>
function applyFilters() {
  const form = document.getElementById('productFilters');
  const formData = new FormData(form);
  
  // Remove empty values
  const params = new URLSearchParams();
  for (const [key, value] of formData.entries()) {
    if (value) {
      params.append(key, value);
    }
  }
  
  // Make API call
  fetch(`/api/products?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
      // Update product list
      renderProducts(data.data.products);
    });
}
</script>
```

---

## 3️⃣ Advanced Search Functionality

### Complex Search Scenarios

Advanced search allows users to combine multiple criteria for precise results.

### Example: E-commerce Product Search Page

**User Story:** "I want to find active bed sheets from Hometex brand, sorted by price, under $100"

```javascript
// Advanced Search Function
async function advancedSearch(criteria) {
  const params = new URLSearchParams();
  
  // Category filter
  if (criteria.category) {
    params.append('category_id', criteria.category);
  }
  
  // Brand filter
  if (criteria.brand) {
    params.append('brand_id', criteria.brand);
  }
  
  // Search term
  if (criteria.query) {
    params.append('search', criteria.query);
  }
  
  // Status (only active products)
  params.append('status', 1);
  
  // Sorting
  params.append('order_by', criteria.sortBy || 'price');
  params.append('direction', criteria.sortDirection || 'asc');
  
  // Pagination
  params.append('per_page', criteria.perPage || 20);
  params.append('page', criteria.page || 1);
  
  // Make API call
  const response = await fetch(`/api/products?${params.toString()}`);
  const data = await response.json();
  
  // Additional filtering in frontend (e.g., price < $100)
  if (criteria.maxPrice) {
    data.data.products = data.data.products.filter(
      product => product.price <= criteria.maxPrice
    );
  }
  
  return data;
}

// Usage
const results = await advancedSearch({
  category: 5,           // Bedding
  brand: 10,             // Hometex
  query: 'bed sheet',    // Search term
  sortBy: 'price',       // Sort by price
  sortDirection: 'asc',  // Low to high
  maxPrice: 100,         // Under $100 (frontend filter)
  perPage: 20
});

// API Call Made:
// GET /api/products?category_id=5&brand_id=10&search=bed%20sheet&status=1&order_by=price&direction=asc&per_page=20&page=1
```

### Example: Filter Builder Pattern

```javascript
class ProductFilterBuilder {
  constructor() {
    this.filters = {};
  }
  
  category(id) {
    if (id) this.filters.category_id = id;
    return this;
  }
  
  brand(id) {
    if (id) this.filters.brand_id = id;
    return this;
  }
  
  search(term) {
    if (term) this.filters.search = term;
    return this;
  }
  
  status(status) {
    if (status !== null) this.filters.status = status;
    return this;
  }
  
  sortBy(field, direction = 'asc') {
    this.filters.order_by = field;
    this.filters.direction = direction;
    return this;
  }
  
  paginate(perPage = 20, page = 1) {
    this.filters.per_page = perPage;
    this.filters.page = page;
    return this;
  }
  
  async execute() {
    const params = new URLSearchParams(this.filters);
    const response = await fetch(`/api/products?${params.toString()}`);
    return await response.json();
  }
}

// Usage - Fluent API
const results = await new ProductFilterBuilder()
  .category(5)
  .brand(10)
  .search('bed')
  .status(1)
  .sortBy('price', 'asc')
  .paginate(20, 1)
  .execute();
```

---

## 📊 Comparison: Query Parameters vs Dedicated Endpoints

| Feature | Query Parameters | Dedicated Endpoints |
|---------|-----------------|---------------------|
| **Multiple Filters** | ✅ Yes (unlimited) | ❌ No (one at a time) |
| **Combine Filters** | ✅ Yes | ❌ No |
| **Dynamic Filtering** | ✅ Perfect | ❌ Limited |
| **Admin Panels** | ✅ Ideal | ❌ Not suitable |
| **Advanced Search** | ✅ Perfect | ❌ Not suitable |
| **SEO Friendly** | ⚠️ Less | ✅ Better |
| **Simple Use Cases** | ⚠️ Overkill | ✅ Better |
| **URL Length** | ⚠️ Can be long | ✅ Shorter |

---

## 🎯 When to Use Each

### Use Query Parameters (`/api/products?category_id=X&brand_id=Y`) when:
- ✅ You need to combine multiple filters
- ✅ Building admin panels
- ✅ Building advanced search
- ✅ Users can dynamically add/remove filters
- ✅ You need flexible sorting options

### Use Dedicated Endpoints (`/api/products/category/X`) when:
- ✅ Simple, single-filter scenarios
- ✅ Category/brand pages (SEO important)
- ✅ Public-facing pages
- ✅ You want cleaner URLs

---

## 💡 Best Practice: Use Both!

**Recommended Approach:**
- Use **dedicated endpoints** for public category/brand pages (SEO)
- Use **query parameters** for admin panels and advanced search (flexibility)

Both can coexist and serve different purposes!

