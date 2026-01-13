<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Product Navigation Service
 * 
 * Implements contextual product navigation (Prev/Next) following industry standards.
 * This is how major e-commerce platforms (Amazon, Shopify, Zalando) handle product navigation.
 * 
 * Key Concepts:
 * - Navigation is based on "Search Result Context" not product IDs
 * - The context (filters, sorting, category) determines the product sequence
 * - Navigation state is passed via query params, not stored in session (API-first)
 * - Result lists are cached with short TTL for performance
 * 
 * @see https://www.nngroup.com/articles/pagination-vs-infinite-scroll/
 */
class ProductNavigationService
{
    /**
     * Cache TTL for navigation lists (in seconds)
     * Short TTL because product listings can change frequently
     */
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Maximum products to store in a navigation list
     * Prevents memory issues with very large result sets
     */
    private const MAX_LIST_SIZE = 500;

    /**
     * Cache key prefix for navigation lists
     */
    private const CACHE_PREFIX = 'product_nav_list_';

    /**
     * Create a navigation list from filters and return the list ID
     * 
     * This is called when loading a product listing page.
     * The returned list_id should be passed to product detail pages.
     * 
     * @param array $filters The same filters used for product listing
     * @param string $orderBy Sort field
     * @param string $direction Sort direction (asc/desc)
     * @return array{list_id: string, total: int, expires_at: string}
     */
    public function createNavigationList(array $filters = [], string $orderBy = 'created_at', string $direction = 'desc'): array
    {
        // Generate a deterministic list ID based on filters and sorting
        $listId = $this->generateListId($filters, $orderBy, $direction);
        $cacheKey = self::CACHE_PREFIX . $listId;

        // Check if list already exists in cache
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return [
                'list_id' => $listId,
                'total' => count($cached['product_ids']),
                'expires_at' => $cached['expires_at'],
            ];
        }

        // Build and execute the query to get product IDs
        $productIds = $this->buildNavigationQuery($filters, $orderBy, $direction)
            ->limit(self::MAX_LIST_SIZE)
            ->pluck('id')
            ->toArray();

        // Store in cache with metadata
        $expiresAt = now()->addSeconds(self::CACHE_TTL)->toIso8601String();
        Cache::put($cacheKey, [
            'product_ids' => $productIds,
            'filters' => $filters,
            'order_by' => $orderBy,
            'direction' => $direction,
            'expires_at' => $expiresAt,
            'created_at' => now()->toIso8601String(),
        ], self::CACHE_TTL);

        return [
            'list_id' => $listId,
            'total' => count($productIds),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Create a navigation list from a pre-fetched collection of products
     * 
     * Use this for homepage sections like Featured, Bestsellers, Trending, etc.
     * where the products are already fetched with specific business logic.
     * 
     * @param \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $products
     * @param string $sectionType Identifier for the section (e.g., 'featured', 'bestsellers')
     * @param array $extraContext Additional context for cache key generation
     * @return array{list_id: string, total: int, expires_at: string}
     */
    public function createNavigationListFromProducts($products, string $sectionType, array $extraContext = []): array
    {
        // Generate a deterministic list ID based on section type and context
        $listId = $this->generateSectionListId($sectionType, $extraContext);
        $cacheKey = self::CACHE_PREFIX . $listId;

        // Check if list already exists in cache
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return [
                'list_id' => $listId,
                'total' => count($cached['product_ids']),
                'expires_at' => $cached['expires_at'],
                'section' => $sectionType,
            ];
        }

        // Extract product IDs from the collection/paginator
        $productIds = $products->pluck('id')->toArray();

        // Store in cache with metadata
        $expiresAt = now()->addSeconds(self::CACHE_TTL)->toIso8601String();
        Cache::put($cacheKey, [
            'product_ids' => $productIds,
            'section_type' => $sectionType,
            'extra_context' => $extraContext,
            'expires_at' => $expiresAt,
            'created_at' => now()->toIso8601String(),
        ], self::CACHE_TTL);

        return [
            'list_id' => $listId,
            'total' => count($productIds),
            'expires_at' => $expiresAt,
            'section' => $sectionType,
        ];
    }

    /**
     * Generate a deterministic list ID for homepage sections
     * 
     * @param string $sectionType
     * @param array $extraContext
     * @return string
     */
    private function generateSectionListId(string $sectionType, array $extraContext = []): string
    {
        ksort($extraContext);
        
        $hashInput = json_encode([
            'section' => $sectionType,
            'ctx' => $extraContext,
        ]);

        return substr(md5($hashInput), 0, 12);
    }

    /**
     * Get navigation context for a product (prev/next neighbors)
     * 
     * @param int|string $productIdentifier Product ID or slug
     * @param string|null $listId The navigation list ID from product listing
     * @param int|null $position The position hint (0-indexed) to speed up lookup
     * @return array{prev: array|null, next: array|null, position: int|null, total: int|null, list_id: string|null}
     */
    public function getProductNavigation($productIdentifier, ?string $listId = null, ?int $position = null): array
    {
        // Resolve product ID if slug was provided
        $productId = $this->resolveProductId($productIdentifier);
        
        if (!$productId) {
            return $this->emptyNavigation();
        }

        // If we have a valid list ID, use cached navigation
        if ($listId) {
            $navigation = $this->getNavigationFromList($productId, $listId, $position);
            if ($navigation['list_id'] !== null) {
                return $navigation;
            }
        }

        // Fallback: No list context, return empty navigation
        // This is the expected behavior when user lands directly on product page
        // Frontend can choose to show related products instead
        return $this->emptyNavigation();
    }

    /**
     * Get navigation using a specific list context
     * 
     * @param int $productId
     * @param string $listId
     * @param int|null $positionHint
     * @return array
     */
    private function getNavigationFromList(int $productId, string $listId, ?int $positionHint = null): array
    {
        $cacheKey = self::CACHE_PREFIX . $listId;
        $cached = Cache::get($cacheKey);

        if (!$cached || empty($cached['product_ids'])) {
            return $this->emptyNavigation();
        }

        $productIds = $cached['product_ids'];
        $total = count($productIds);

        // Find current product position
        // If position hint is provided and valid, use it for O(1) lookup
        if ($positionHint !== null && isset($productIds[$positionHint]) && $productIds[$positionHint] === $productId) {
            $currentIndex = $positionHint;
        } else {
            // Otherwise search the array - O(n) but cached list is limited to MAX_LIST_SIZE
            $currentIndex = array_search($productId, $productIds, true);
        }

        if ($currentIndex === false) {
            // Product not in this list (maybe filters changed or product was removed)
            return $this->emptyNavigation();
        }

        // Get prev/next product IDs
        $prevId = $currentIndex > 0 ? $productIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < $total - 1 ? $productIds[$currentIndex + 1] : null;

        // Fetch minimal product data for prev/next
        $prev = $prevId ? $this->getMinimalProductData($prevId, $currentIndex - 1) : null;
        $next = $nextId ? $this->getMinimalProductData($nextId, $currentIndex + 1) : null;

        return [
            'prev' => $prev,
            'next' => $next,
            'position' => $currentIndex + 1, // 1-indexed for display
            'total' => $total,
            'list_id' => $listId,
        ];
    }

    /**
     * Get category-based fallback navigation
     * 
     * Use this when no list context is available but you want to show
     * prev/next from the same category (like "Browse more in this category")
     * 
     * @param int $productId
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function getCategoryFallbackNavigation(int $productId, string $orderBy = 'created_at', string $direction = 'desc'): array
    {
        $product = Product::select('id', 'category_id', 'sub_category_id', 'child_sub_category_id', $orderBy)
            ->find($productId);

        if (!$product) {
            return $this->emptyNavigation();
        }

        // Build query for same category
        $query = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('category_id', $product->category_id);

        // Optionally narrow to sub_category if exists
        if ($product->sub_category_id) {
            $query->where('sub_category_id', $product->sub_category_id);
        }

        // Get prev product (where sort value is less/greater than current)
        $prevQuery = clone $query;
        $nextQuery = clone $query;

        if ($direction === 'desc') {
            $prev = $prevQuery
                ->where(function ($q) use ($product, $orderBy) {
                    $q->where($orderBy, '>', $product->$orderBy)
                      ->orWhere(function ($q2) use ($product, $orderBy) {
                          $q2->where($orderBy, '=', $product->$orderBy)
                             ->where('id', '>', $product->id);
                      });
                })
                ->orderBy($orderBy, 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $next = $nextQuery
                ->where(function ($q) use ($product, $orderBy) {
                    $q->where($orderBy, '<', $product->$orderBy)
                      ->orWhere(function ($q2) use ($product, $orderBy) {
                          $q2->where($orderBy, '=', $product->$orderBy)
                             ->where('id', '<', $product->id);
                      });
                })
                ->orderBy($orderBy, 'desc')
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $prev = $prevQuery
                ->where(function ($q) use ($product, $orderBy) {
                    $q->where($orderBy, '<', $product->$orderBy)
                      ->orWhere(function ($q2) use ($product, $orderBy) {
                          $q2->where($orderBy, '=', $product->$orderBy)
                             ->where('id', '<', $product->id);
                      });
                })
                ->orderBy($orderBy, 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $next = $nextQuery
                ->where(function ($q) use ($product, $orderBy) {
                    $q->where($orderBy, '>', $product->$orderBy)
                      ->orWhere(function ($q2) use ($product, $orderBy) {
                          $q2->where($orderBy, '=', $product->$orderBy)
                             ->where('id', '>', $product->id);
                      });
                })
                ->orderBy($orderBy, 'asc')
                ->orderBy('id', 'asc')
                ->first();
        }

        return [
            'prev' => $prev ? $this->formatProductForNavigation($prev) : null,
            'next' => $next ? $this->formatProductForNavigation($next) : null,
            'position' => null, // Unknown position without full list
            'total' => null,
            'list_id' => null,
            'context' => 'category_fallback', // Indicates this is fallback navigation
        ];
    }

    /**
     * Build the navigation query with filters
     * Mirrors the logic in ProductService::getPaginatedProducts for consistency
     * 
     * @param array $filters
     * @param string $orderBy
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildNavigationQuery(array $filters, string $orderBy, string $direction)
    {
        $query = Product::query()
            ->select('id') // Only need IDs for navigation list
            ->where('status', Product::STATUS_ACTIVE);

        // Apply category filters (most common filter)
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['sub_category_id'])) {
            $query->where('sub_category_id', $filters['sub_category_id']);
        }

        if (!empty($filters['child_sub_category_id'])) {
            $query->where('child_sub_category_id', $filters['child_sub_category_id']);
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Apply price range filters
        if (isset($filters['min_price']) && $filters['min_price'] > 0) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] > 0) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Apply stock status filter
        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // Apply in_stock filter
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where(function ($q) {
                $q->where('stock', '>', 0)
                  ->orWhere('stock_status', 'in_stock');
            });
        }

        // Apply search filter (simplified for navigation - just product name)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $searchTerm);
        }

        // Apply attribute value filter
        if (!empty($filters['attribute_value_ids']) && is_array($filters['attribute_value_ids'])) {
            $query->whereHas('product_attributes', function ($q) use ($filters) {
                $q->whereIn('attribute_value_id', $filters['attribute_value_ids']);
            });
        }

        // Ensure consistent ordering with secondary sort by ID
        $query->orderBy($orderBy, $direction)
              ->orderBy('id', $direction);

        return $query;
    }

    /**
     * Generate a deterministic list ID from filters and sorting
     * Same filters + sorting = same list ID = cache hit
     * 
     * @param array $filters
     * @param string $orderBy
     * @param string $direction
     * @return string
     */
    private function generateListId(array $filters, string $orderBy, string $direction): string
    {
        // Sort filters to ensure consistent hashing regardless of parameter order
        ksort($filters);
        
        $hashInput = json_encode([
            'f' => $filters,
            'o' => $orderBy,
            'd' => $direction,
        ]);

        // Use short hash for URL-friendliness
        return substr(md5($hashInput), 0, 12);
    }

    /**
     * Resolve product ID from identifier (ID or slug)
     * 
     * @param int|string $identifier
     * @return int|null
     */
    private function resolveProductId($identifier): ?int
    {
        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        // Assume it's a slug
        $product = Product::where('slug', $identifier)->select('id')->first();
        return $product?->id;
    }

    /**
     * Get minimal product data for navigation display
     * 
     * @param int $productId
     * @param int $position 0-indexed position for building URLs
     * @return array
     */
    private function getMinimalProductData(int $productId, int $position): array
    {
        $product = Product::query()
            ->select('id', 'name', 'slug', 'price', 'discount_percent', 'discount_fixed', 'discount_start', 'discount_end')
            ->with(['primary_photo:id,photo,product_id'])
            ->find($productId);

        if (!$product) {
            return [];
        }

        return $this->formatProductForNavigation($product, $position);
    }

    /**
     * Format product data for navigation response
     * 
     * @param Product $product
     * @param int|null $position
     * @return array
     */
    private function formatProductForNavigation(Product $product, ?int $position = null): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'thumbnail' => $product->primary_photo?->photo 
                ? asset('images/product/thumb/' . $product->primary_photo->photo)
                : null,
        ];

        if ($position !== null) {
            $data['position'] = $position;
        }

        return $data;
    }

    /**
     * Return empty navigation structure
     * 
     * @return array
     */
    private function emptyNavigation(): array
    {
        return [
            'prev' => null,
            'next' => null,
            'position' => null,
            'total' => null,
            'list_id' => null,
        ];
    }

    /**
     * Invalidate navigation caches for a specific list
     * 
     * @param string $listId
     * @return bool
     */
    public function invalidateList(string $listId): bool
    {
        return Cache::forget(self::CACHE_PREFIX . $listId);
    }

    /**
     * Clear all navigation caches
     * Called when products are bulk updated or cache needs refresh
     * 
     * Note: With file/database cache, this requires pattern matching
     * which isn't always efficient. Consider using Redis with SCAN
     * for production workloads.
     * 
     * @return void
     */
    public function clearAllNavigationCaches(): void
    {
        // For file/database cache, we can't efficiently clear by prefix
        // This is a limitation - for production, use Redis with Cache::tags()
        // or implement a cache key registry
        
        // Log a warning in development
        if (config('app.debug')) {
            Log::info('ProductNavigationService: clearAllNavigationCaches called. For efficient cache clearing, use Redis with tags.');
        }
    }
}
