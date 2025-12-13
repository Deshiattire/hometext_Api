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
            'created_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            'updated_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            'primary_photo',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications.specifications',
            'shops:id,name'
        ]);

        // Apply search filter - optimized with LEFT JOINs instead of whereHas (much faster)
        // This approach is used by major e-commerce platforms for better performance
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            
            // Use LEFT JOINs for better performance (avoids subqueries from whereHas)
            // This is 3-10x faster than whereHas on large datasets
            $query->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                  ->leftJoin('sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
                  ->leftJoin('child_sub_categories', 'products.child_sub_category_id', '=', 'child_sub_categories.id')
                  ->where(function ($q) use ($searchTerm) {
                      // Search in product fields (most common searches)
                      $q->where('products.name', 'like', $searchTerm)
                        ->orWhere('products.sku', 'like', $searchTerm)
                        ->orWhere('products.description', 'like', $searchTerm)
                        // Search in category name (using joined table)
                        ->orWhere('categories.name', 'like', $searchTerm)
                        // Search in subcategory name (using joined table)
                        ->orWhere('sub_categories.name', 'like', $searchTerm)
                        // Search in child subcategory name (using joined table)
                        ->orWhere('child_sub_categories.name', 'like', $searchTerm);
                  })
                  // Use groupBy instead of distinct for better pagination performance
                  ->groupBy('products.id')
                  ->select('products.*');
        }

        // Apply category filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply sub category filter
        if (!empty($filters['sub_category_id'])) {
            $query->where('sub_category_id', $filters['sub_category_id']);
        }

        // Apply child sub category filter
        if (!empty($filters['child_sub_category_id'])) {
            $query->where('child_sub_category_id', $filters['child_sub_category_id']);
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply price range filters
        if (isset($filters['min_price']) && $filters['min_price'] > 0) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] > 0) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Apply stock status filter
        if (isset($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // Apply in_stock filter (products with stock > 0)
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where(function ($q) {
                $q->where('stock', '>', 0)
                  ->orWhere('stock_status', 'in_stock');
            });
        }

        // Apply color filter (search only in product variations attributes JSON field)
        if (!empty($filters['color'])) {
            $colorTerm = '%' . strtolower($filters['color']) . '%';
            
            // Only search in variations if variations table exists
            if (DB::getSchemaBuilder()->hasTable('product_variations')) {
                $query->whereHas('variations', function ($variationQuery) use ($colorTerm) {
                    $variationQuery->where(function ($vq) use ($colorTerm) {
                        // Search in JSON attributes field - check various case combinations for Color key
                        $vq->whereRaw("LOWER(JSON_EXTRACT(attributes, '$.Color')) LIKE ?", [$colorTerm])
                            ->orWhereRaw("LOWER(JSON_EXTRACT(attributes, '$.Colour')) LIKE ?", [$colorTerm])
                            ->orWhereRaw("LOWER(JSON_EXTRACT(attributes, '$.color')) LIKE ?", [$colorTerm])
                            ->orWhereRaw("LOWER(JSON_EXTRACT(attributes, '$.colour')) LIKE ?", [$colorTerm])
                            // Also search in any attribute value (case-insensitive) - catches color stored in any key
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.*'))) LIKE ?", [$colorTerm]);
                    });
                });
            }
        }

        // Apply attribute filter by attribute_id
        if (!empty($filters['attribute_id'])) {
            $query->whereHas('product_attributes', function ($q) use ($filters) {
                $q->where('attribute_id', $filters['attribute_id']);
            });
        }

        // Apply attribute value filter (single value)
        if (!empty($filters['attribute_value_id'])) {
            $query->whereHas('product_attributes', function ($q) use ($filters) {
                $q->where('attribute_value_id', $filters['attribute_value_id']);
            });
        }

        // Apply multiple attribute values filter (OR condition - product has any of these values)
        if (!empty($filters['attribute_value_ids']) && is_array($filters['attribute_value_ids'])) {
            $query->whereHas('product_attributes', function ($q) use ($filters) {
                $q->whereIn('attribute_value_id', $filters['attribute_value_ids']);
            });
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
        $with = [
            // Basic relationships
            'category:id,name,slug',
            'sub_category:id,name,slug',
            'child_sub_category:id,name,slug',
            'brand:id,name,slug,logo',
            'country:id,name',
            'supplier:id,name,phone,email',
            'created_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            'updated_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            
            // Media
            'primary_photo:id,photo,product_id,is_primary',
            'photos:id,photo,product_id,is_primary',
            
            // Attributes and specifications
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications',
            
            // Shops
            'shops:id,name'
        ];
        
        // Add optional relationships only if tables exist
        if (DB::getSchemaBuilder()->hasTable('product_videos')) {
            $with['videos'] = function($q) {
                $q->select('id', 'type', 'url', 'thumbnail', 'title', 'position', 'product_id');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_tags')) {
            $with['tags'] = function($q) {
                $q->select('id', 'name', 'slug');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_variations')) {
            $with['variations'] = function($q) {
                $q->select('id', 'product_id', 'sku', 'name', 'slug', 'regular_price', 'sale_price', 'stock_quantity', 'stock_status', 'attributes', 'is_active');
            };
            $with['variations.primary_photo'] = function($q) {
                $q->select('id', 'photo', 'product_id', 'is_primary');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_reviews')) {
            $with['approvedReviews'] = function($q) {
                $q->select('id', 'product_id', 'user_id', 'reviewer_name', 'rating', 'title', 'review', 'is_verified_purchase', 'is_recommended', 'created_at')
                  ->where('is_approved', true);
            };
            $with['approvedReviews.user'] = function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('bulk_pricing')) {
            $with['bulkPricing'] = function($q) {
                $q->select('id', 'product_id', 'min_quantity', 'max_quantity', 'price', 'discount_percentage');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_analytics')) {
            $with['analytics'] = function($q) {
                $q->select('id', 'product_id', 'views_count', 'clicks_count', 'add_to_cart_count', 'purchase_count', 'wishlist_count', 'conversion_rate');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('related_products')) {
            $with['relatedProducts'] = function($q) {
                $q->select('id', 'product_id', 'related_product_id', 'relation_type', 'sort_order');
            };
            $with['relatedProducts.relatedProduct'] = function($q) {
                $q->select('id', 'name', 'slug', 'price');
            };
            $with['relatedProducts.relatedProduct.primary_photo'] = function($q) {
                $q->select('id', 'photo', 'product_id', 'is_primary');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_seo_meta_data')) {
            $with['seo_meta'] = function($q) {
                $q->select('id', 'product_id', 'name', 'content');
            };
        }
        
        try {
            return Product::query()->with($with)->findOrFail($id);
        } catch (\Illuminate\Database\QueryException $e) {
            // If error is due to missing table, try again without optional relationships
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found')) {
                // Remove all optional relationships and try again
                $with = array_filter($with, function($key) {
                    return !in_array($key, ['videos', 'tags', 'variations', 'approvedReviews', 'bulkPricing', 'analytics', 'relatedProducts', 'seo_meta']);
                }, ARRAY_FILTER_USE_KEY);
                return Product::query()->with($with)->findOrFail($id);
            }
            throw $e;
        }
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
        // Use the same relationship loading logic as getProductById
        $with = [
            // Basic relationships
            'category:id,name,slug',
            'sub_category:id,name,slug',
            'child_sub_category:id,name,slug',
            'brand:id,name,slug,logo',
            'country:id,name',
            'supplier:id,name,phone,email',
            'created_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            'updated_by' => function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            },
            
            // Media
            'primary_photo:id,photo,product_id,is_primary',
            'photos:id,photo,product_id,is_primary',
            
            // Attributes and specifications
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications',
            
            // Shops
            'shops:id,name'
        ];
        
        // Add optional relationships only if tables exist
        if (DB::getSchemaBuilder()->hasTable('product_videos')) {
            $with['videos'] = function($q) {
                $q->select('id', 'type', 'url', 'thumbnail', 'title', 'position', 'product_id');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_tags')) {
            $with['tags'] = function($q) {
                $q->select('id', 'name', 'slug');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_variations')) {
            $with['variations'] = function($q) {
                $q->select('id', 'product_id', 'sku', 'name', 'slug', 'regular_price', 'sale_price', 'stock_quantity', 'stock_status', 'attributes', 'is_active');
            };
            $with['variations.primary_photo'] = function($q) {
                $q->select('id', 'photo', 'product_id', 'is_primary');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_reviews')) {
            $with['approvedReviews'] = function($q) {
                $q->select('id', 'product_id', 'user_id', 'reviewer_name', 'rating', 'title', 'review', 'is_verified_purchase', 'is_recommended', 'created_at')
                  ->where('is_approved', true);
            };
            $with['approvedReviews.user'] = function($q) {
                $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('bulk_pricing')) {
            $with['bulkPricing'] = function($q) {
                $q->select('id', 'product_id', 'min_quantity', 'max_quantity', 'price', 'discount_percentage');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_analytics')) {
            $with['analytics'] = function($q) {
                $q->select('id', 'product_id', 'views_count', 'clicks_count', 'add_to_cart_count', 'purchase_count', 'wishlist_count', 'conversion_rate');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('related_products')) {
            $with['relatedProducts'] = function($q) {
                $q->select('id', 'product_id', 'related_product_id', 'relation_type', 'sort_order');
            };
            $with['relatedProducts.relatedProduct'] = function($q) {
                $q->select('id', 'name', 'slug', 'price');
            };
            $with['relatedProducts.relatedProduct.primary_photo'] = function($q) {
                $q->select('id', 'photo', 'product_id', 'is_primary');
            };
        }
        
        if (DB::getSchemaBuilder()->hasTable('product_seo_meta_data')) {
            $with['seo_meta'] = function($q) {
                $q->select('id', 'product_id', 'name', 'content');
            };
        }
        
        try {
            return Product::query()->with($with)->where('slug', $slug)->firstOrFail();
        } catch (\Illuminate\Database\QueryException $e) {
            // If error is due to missing table, try again without optional relationships
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found')) {
                // Remove all optional relationships and try again
                $with = array_filter($with, function($key) {
                    return !in_array($key, ['videos', 'tags', 'variations', 'approvedReviews', 'bulkPricing', 'analytics', 'relatedProducts', 'seo_meta']);
                }, ARRAY_FILTER_USE_KEY);
                return Product::query()->with($with)->where('slug', $slug)->firstOrFail();
            }
            throw $e;
        }
    }

    /**
     * Base query builder with common eager loading for list views
     */
    private function getBaseQuery()
    {
        $query = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->with([
                'category:id,name,slug',
                'sub_category:id,name,slug',
                'brand:id,name,slug,logo',
                'primary_photo:id,photo,product_id,is_primary',
            ]);
        
        // Only filter by visibility if column exists
        if (DB::getSchemaBuilder()->hasColumn('products', 'visibility')) {
            $query->where('visibility', Product::VISIBILITY_VISIBLE);
        }
        
        return $query;
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
            $query = $this->getBaseQuery();
            
            // Check if product_analytics table exists
            if (DB::getSchemaBuilder()->hasTable('product_analytics')) {
                // Use analytics table if available
                return $query
                    ->leftJoin('product_analytics', 'products.id', '=', 'product_analytics.product_id')
                    ->select('products.*')
                    ->orderByRaw('COALESCE(product_analytics.purchase_count, 0) DESC')
                    ->orderBy('products.created_at', 'desc')
                    ->paginate($perPage);
            } else {
                // Fallback: Use is_bestseller flag or sold_count
                return $query
                    ->where(function($q) {
                        $q->where('is_bestseller', 1)
                          ->orWhere('sold_count', '>', 0);
                    })
                    ->orderBy('sold_count', 'desc')
                    ->orderBy('is_bestseller', 'desc')
                    ->orderBy('products.created_at', 'desc')
                    ->paginate($perPage);
            }
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

