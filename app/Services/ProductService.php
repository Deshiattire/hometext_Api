<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get paginated list of products
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()->with([
            'category:id,name',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'brand:id,name',
            'country:id,name',
            'supplier:id,name,phone',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
            'primary_photo',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications.specifications',
            'shops:id,name'
        ]);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Order by (default: created_at desc for newest first)
        $orderBy = $filters['order_by'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $query->orderBy($orderBy, $direction);

        return $query->paginate($perPage);
    }

    /**
     * Get a single product by ID with all relationships
     *
     * @param int $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProductById(int $id): Product
    {
        return Product::query()->with([
            // Basic relationships
            'category:id,name,slug',
            'sub_category:id,name,slug',
            'child_sub_category:id,name,slug',
            'brand:id,name,slug,logo',
            'country:id,name,code',
            'supplier:id,name,phone,email',
            'supplier.address',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
            
            // Media
            'primary_photo:id,photo,alt_text,width,height,product_id,is_primary',
            'photos:id,photo,alt_text,width,height,position,product_id,is_primary',
            'videos:id,type,url,thumbnail,title,position,product_id',
            
            // Attributes and specifications
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications:id,product_id,group,name,value',
            
            // Tags
            'tags:id,name,slug',
            
            // Variations
            'variations:id,product_id,sku,name,slug,regular_price,sale_price,stock_quantity,stock_status,attributes,is_active',
            'variations.primary_photo:id,photo,product_id,is_primary',
            
            // Reviews (only approved)
            'approvedReviews:id,product_id,user_id,reviewer_name,rating,title,review,is_verified_purchase,is_recommended,created_at',
            'approvedReviews.user:id,first_name,last_name',
            
            // Pricing
            'bulkPricing:id,product_id,min_quantity,max_quantity,price,discount_percentage',
            
            // Analytics
            'analytics:id,product_id,views_count,clicks_count,add_to_cart_count,purchase_count,wishlist_count,conversion_rate',
            
            // Related products
            'relatedProducts:id,product_id,related_product_id,relation_type,sort_order',
            'relatedProducts.relatedProduct:id,name,slug,price',
            'relatedProducts.relatedProduct.primary_photo:id,photo,product_id,is_primary',
            
            // SEO
            'seo_meta:id,product_id,name,content',
            
            // Shops
            'shops:id,name,slug'
        ])->findOrFail($id);
    }

    /**
     * Get a single product by slug with all relationships
     *
     * @param string $slug
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProductBySlug(string $slug): Product
    {
        return Product::query()->with([
            // Basic relationships
            'category:id,name,slug',
            'sub_category:id,name,slug',
            'child_sub_category:id,name,slug',
            'brand:id,name,slug,logo',
            'country:id,name,code',
            'supplier:id,name,phone,email',
            'supplier.address',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
            
            // Media
            'primary_photo:id,photo,alt_text,width,height,product_id,is_primary',
            'photos:id,photo,alt_text,width,height,position,product_id,is_primary',
            'videos:id,type,url,thumbnail,title,position,product_id',
            
            // Attributes and specifications
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications:id,product_id,group,name,value',
            
            // Tags
            'tags:id,name,slug',
            
            // Variations
            'variations:id,product_id,sku,name,slug,regular_price,sale_price,stock_quantity,stock_status,attributes,is_active',
            'variations.primary_photo:id,photo,product_id,is_primary',
            
            // Reviews (only approved)
            'approvedReviews:id,product_id,user_id,reviewer_name,rating,title,review,is_verified_purchase,is_recommended,created_at',
            'approvedReviews.user:id,first_name,last_name',
            
            // Pricing
            'bulkPricing:id,product_id,min_quantity,max_quantity,price,discount_percentage',
            
            // Analytics
            'analytics:id,product_id,views_count,clicks_count,add_to_cart_count,purchase_count,wishlist_count,conversion_rate',
            
            // Related products
            'relatedProducts:id,product_id,related_product_id,relation_type,sort_order',
            'relatedProducts.relatedProduct:id,name,slug,price',
            'relatedProducts.relatedProduct.primary_photo:id,photo,product_id,is_primary',
            
            // SEO
            'seo_meta:id,product_id,name,content',
            
            // Shops
            'shops:id,name,slug'
        ])->where('slug', $slug)->firstOrFail();
    }

    /**
     * Base query builder with common eager loading for list views
     */
    private function getBaseQuery()
    {
        return Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('visibility', Product::VISIBILITY_VISIBLE)
            ->with([
                'category:id,name,slug',
                'sub_category:id,name,slug',
                'brand:id,name,slug,logo',
                'primary_photo:id,photo,product_id,is_primary',
            ]);
    }

    /**
     * Get featured products
     * Cached for 1 hour
     */
    public function getFeaturedProducts(int $perPage = 20, bool $forceRefresh = false): LengthAwarePaginator
    {
        $cacheKey = 'products_featured_' . $perPage;
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($perPage) {
            return $this->getBaseQuery()
                ->where('isFeatured', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get new arrivals (products added in last 30 days)
     * Cached for 1 hour
     */
    public function getNewArrivals(int $perPage = 20, bool $forceRefresh = false): LengthAwarePaginator
    {
        $cacheKey = 'products_new_arrivals_' . $perPage;
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($perPage) {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            
            return $this->getBaseQuery()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get trending products
     * Cached for 1 hour
     */
    public function getTrendingProducts(int $perPage = 20, bool $forceRefresh = false): LengthAwarePaginator
    {
        $cacheKey = 'products_trending_' . $perPage;
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($perPage) {
            return $this->getBaseQuery()
                ->where('isTrending', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get bestsellers (sorted by purchase_count from analytics)
     * Cached for 1 hour
     */
    public function getBestsellers(int $perPage = 20, bool $forceRefresh = false): LengthAwarePaginator
    {
        $cacheKey = 'products_bestsellers_' . $perPage;
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () use ($perPage) {
            return $this->getBaseQuery()
                ->leftJoin('product_analytics', 'products.id', '=', 'product_analytics.product_id')
                ->select('products.*')
                ->orderBy('product_analytics.purchase_count', 'desc')
                ->orderBy('products.created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get products on sale (with active discounts)
     * Cached for 15 minutes (shorter cache due to time-sensitive discounts)
     */
    public function getOnSaleProducts(int $perPage = 20, bool $forceRefresh = false): LengthAwarePaginator
    {
        $cacheKey = 'products_on_sale_' . $perPage;
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 900, function () use ($perPage) {
            $now = Carbon::now();
            
            return $this->getBaseQuery()
                ->where(function ($query) use ($now) {
                    $query->where(function ($q) use ($now) {
                        $q->whereNotNull('discount_start')
                          ->whereNotNull('discount_end')
                          ->where('discount_start', '<=', $now)
                          ->where('discount_end', '>=', $now);
                    })
                    ->orWhere(function ($q) {
                        $q->where('discount_percent', '>', 0)
                          ->orWhere('discount_fixed', '>', 0);
                    });
                })
                ->orderBy('discount_percent', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Get similar products based on category, brand, or tags
     * 
     * @param int $productId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSimilarProducts(int $productId, int $perPage = 20): LengthAwarePaginator
    {
        $product = Product::findOrFail($productId);
        
        $query = $this->getBaseQuery()
            ->where('products.id', '!=', $productId);

        // Match by category
        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        }

        // If no category match, try brand
        if (!$product->category_id && $product->brand_id) {
            $query->where('brand_id', $product->brand_id);
        }

        // If still no match, try tags
        if (!$product->category_id && !$product->brand_id && $product->tags->isNotEmpty()) {
            $tagIds = $product->tags->pluck('id')->toArray();
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('product_tags.id', $tagIds);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get recommendations (frequently bought together, customers also viewed)
     * 
     * @param int $productId
     * @param string $relationType
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRecommendations(int $productId, string $relationType = 'frequently_bought_together', int $perPage = 20): LengthAwarePaginator
    {
        $product = Product::findOrFail($productId);
        
        $relatedProductIds = $product->relatedProducts()
            ->where('relation_type', $relationType)
            ->where('is_active', true)
            ->pluck('related_product_id')
            ->toArray();

        if (empty($relatedProductIds)) {
            // Fallback to similar products if no recommendations exist
            return $this->getSimilarProducts($productId, $perPage);
        }

        return $this->getBaseQuery()
            ->whereIn('products.id', $relatedProductIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get products by category
     * 
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductsByCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->where('category_id', $categoryId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get products by brand
     * 
     * @param int $brandId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductsByBrand(int $brandId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->where('brand_id', $brandId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

