<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Manager\PriceManager;
use App\Models\Product;
use App\Models\ProductPhoto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $priceData = PriceManager::calculate_sell_price(
            $this->price ?? 0,
            $this->discount_percent ?? 0,
            $this->discount_fixed ?? 0,
            $this->discount_start,
            $this->discount_end
        );
        
        $regularPrice = $this->price ?? 0;
        $salePrice = $priceData['price'] ?? $regularPrice;
        $finalPrice = $salePrice;
        $discountAmount = $priceData['discount'] ?? 0;
        $discountPercent = $this->discount_percent ?? 0;
        
        // Calculate if discount is active
        $isDiscountActive = false;
        $remainingDays = null;
        if ($this->discount_start && $this->discount_end) {
            $now = Carbon::now();
            $start = Carbon::parse($this->discount_start);
            $end = Carbon::parse($this->discount_end);
            $isDiscountActive = $now->isBetween($start, $end);
            if ($isDiscountActive && $end->isFuture()) {
                $remainingDays = $now->diffInDays($end);
            }
        }
        
        // Calculate profit margin
        $profitAmount = $finalPrice - ($this->cost ?? 0);
        $profitPercentage = $finalPrice > 0 ? (($profitAmount / $finalPrice) * 100) : 0;
        
        // Calculate tax
        $taxRate = $this->tax_rate ?? 0;
        $taxAmount = $this->tax_included ? 0 : ($finalPrice * $taxRate / 100);
        
        // Stock status
        $stockQuantity = $this->stock ?? 0;
        $stockStatus = $this->stock_status ?? Product::STOCK_STATUS_IN_STOCK;
        $isLowStock = $stockQuantity <= ($this->low_stock_threshold ?? 10);
        
        // Reviews aggregation
        $reviews = $this->approvedReviews ?? collect();
        $averageRating = $reviews->avg('rating') ?? 0;
        $ratingCount = $reviews->count();
        $reviewCount = $reviews->count();
        $ratingDistribution = $this->getRatingDistribution($reviews);
        $verifiedPurchaseCount = $reviews->where('is_verified_purchase', true)->count();
        $verifiedPurchasePercentage = $reviewCount > 0 ? ($verifiedPurchaseCount / $reviewCount * 100) : 0;
        $recommendedCount = $reviews->where('is_recommended', true)->count();
        $recommendationPercentage = $reviewCount > 0 ? ($recommendedCount / $reviewCount * 100) : 0;
        
        // Analytics
        $analytics = $this->analytics;
        $viewsCount = $analytics->views_count ?? 0;
        $clicksCount = $analytics->clicks_count ?? 0;
        $addToCartCount = $analytics->add_to_cart_count ?? 0;
        $purchaseCount = $analytics->purchase_count ?? 0;
        $wishlistCount = $analytics->wishlist_count ?? 0;
        $conversionRate = $analytics->conversion_rate ?? 0;
        
        // Badges
        $isNew = $this->isNew ?? false;
        if ($this->created_at) {
            $daysSinceCreation = Carbon::now()->diffInDays($this->created_at);
            $isNew = $daysSinceCreation <= 30;
        }
        
        return [
            // ===== BASIC PRODUCT INFORMATION =====
            'id' => $this->id,
            'sku' => $this->sku ?? '',
            'name' => $this->name ?? '',
            'slug' => $this->slug ?? '',
            'description' => $this->description ?? '',
            'short_description' => $this->short_description ?? '',
            'status' => $this->status == Product::STATUS_ACTIVE ? 'active' : ($this->status == Product::STATUS_INACTIVE ? 'inactive' : 'draft'),
            'visibility' => $this->visibility ?? Product::VISIBILITY_VISIBLE,
            'type' => $this->type ?? Product::TYPE_SIMPLE,
            
            // ===== CATEGORIZATION =====
            'category' => $this->when($this->category, [
                'id' => $this->category->id ?? null,
                'name' => $this->category->name ?? '',
                'slug' => $this->category->slug ?? '',
                'level' => 1,
            ]),
            'sub_category' => $this->when($this->sub_category, [
                'id' => $this->sub_category->id ?? null,
                'name' => $this->sub_category->name ?? '',
                'slug' => $this->sub_category->slug ?? '',
                'level' => 2,
            ]),
            'child_sub_category' => $this->when($this->child_sub_category, [
                'id' => $this->child_sub_category->id ?? null,
                'name' => $this->child_sub_category->name ?? '',
                'slug' => $this->child_sub_category->slug ?? '',
                'level' => 3,
            ]),
            'breadcrumb' => $this->breadcrumb ?? [],
            'tags' => $this->tags->pluck('name')->toArray() ?? [],
            
            // ===== BRAND & MANUFACTURER =====
            'brand' => $this->when($this->brand, [
                'id' => $this->brand->id ?? null,
                'name' => $this->brand->name ?? '',
                'slug' => $this->brand->slug ?? '',
                'logo' => $this->brand->logo ? ImageUploadManager::prepareImageUrl(\App\Models\Brand::THUMB_IMAGE_UPLOAD_PATH, $this->brand->logo) : null,
            ]),
            'manufacturer' => $this->when($this->supplier, [
                'id' => $this->supplier->id ?? null,
                'name' => $this->supplier->name ?? '',
                'country' => $this->country->name ?? '',
            ]),
            'country_of_origin' => $this->when($this->country, [
                'id' => $this->country->id ?? null,
                'name' => $this->country->name ?? '',
                'code' => $this->country->code ?? null,
            ]),
            
            // ===== PRICING & OFFERS =====
            'pricing' => [
                'currency' => $this->currency ?? PriceManager::CURRENCY_NAME,
                'currency_symbol' => $this->currency_symbol ?? PriceManager::CURRENCY_SYMBOL,
                'cost_price' => (float) ($this->cost ?? 0),
                'regular_price' => (float) $regularPrice,
                'old_price' => $this->old_price ? (float) $this->old_price : null,
                'sale_price' => $isDiscountActive ? (float) $salePrice : null,
                'final_price' => (float) $finalPrice,
                'discount' => [
                    'type' => $this->discount_fixed > 0 ? 'fixed' : ($this->discount_percent > 0 ? 'percentage' : 'none'),
                    'value' => $this->discount_percent ?? 0,
                    'fixed_amount' => $this->discount_fixed ? (float) $this->discount_fixed : null,
                    'percent' => $this->discount_percent ? (float) $this->discount_percent : null,
                    'amount' => (float) $discountAmount,
                    'start_date' => $this->discount_start ? Carbon::parse($this->discount_start)->toIso8601String() : null,
                    'end_date' => $this->discount_end ? Carbon::parse($this->discount_end)->toIso8601String() : null,
                    'is_active' => $isDiscountActive,
                    'remaining_days' => $remainingDays,
                ],
                'tax' => [
                    'rate' => (float) $taxRate,
                    'amount' => (float) $taxAmount,
                    'included' => $this->tax_included ?? false,
                    'class' => $this->tax_class ?? 'standard',
                ],
                'profit_margin' => [
                    'amount' => (float) $profitAmount,
                    'percentage' => round($profitPercentage, 2),
                ],
                'price_range' => $this->getPriceRange(), // For variable products
            ],
            
            // ===== INVENTORY & STOCK =====
            'inventory' => [
                'stock_status' => $stockStatus,
                'stock_quantity' => $stockQuantity,
                'low_stock_threshold' => $this->low_stock_threshold ?? 10,
                'is_low_stock' => $isLowStock,
                'allow_backorders' => $this->allow_backorders ?? false,
                'manage_stock' => $this->manage_stock ?? true,
                'stock_by_location' => $this->shops->map(function ($shop) {
                    return [
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name ?? '',
                        'shop_slug' => $shop->slug ?? null,
                        'quantity' => $shop->pivot->quantity ?? 0,
                        'reserved' => 0, // Can be calculated from orders
                    ];
                })->toArray(),
                'sold_count' => $this->sold_count ?? 0,
                'restock_date' => $this->restock_date ? Carbon::parse($this->restock_date)->toIso8601String() : null,
            ],
            
            // ===== PRODUCT VARIATIONS =====
            'has_variations' => $this->hasVariations(),
            'parent_id' => $this->parent_id,
            'variations' => ProductVariationResource::collection($this->variations ?? collect()),
            'attributes' => ProductAttributeListResource::collection($this->product_attributes ?? collect()),
            
            // ===== SPECIFICATIONS =====
            'specifications' => $this->getSpecificationsGrouped(),
            
            // ===== MEDIA =====
            'media' => [
                'primary_image' => $this->when($this->primary_photo, [
                    'id' => $this->primary_photo->id ?? null,
                    'url' => ImageUploadManager::prepareImageUrl(ProductPhoto::PHOTO_UPLOAD_PATH, $this->primary_photo->photo ?? null),
                    'thumbnail' => ImageUploadManager::prepareImageUrl(ProductPhoto::THUMB_PHOTO_UPLOAD_PATH, $this->primary_photo->photo ?? null),
                    'alt_text' => $this->primary_photo->alt_text ?? '',
                    'width' => $this->primary_photo->width ?? null,
                    'height' => $this->primary_photo->height ?? null,
                ]),
                'gallery' => ProductPhotoListResource::collection($this->photos ?? collect()),
                'videos' => ProductVideoResource::collection($this->videos ?? collect()),
            ],
            
            // ===== REVIEWS & RATINGS =====
            'reviews' => [
                'average_rating' => round($averageRating, 1),
                'rating_count' => $ratingCount,
                'review_count' => $reviewCount,
                'rating_distribution' => $ratingDistribution,
                'verified_purchase_percentage' => round($verifiedPurchasePercentage, 1),
                'recommendation_percentage' => round($recommendationPercentage, 1),
            ],
            
            // ===== SHIPPING & DELIVERY =====
            'shipping' => [
                'weight' => (float) ($this->weight ?? 0),
                'weight_unit' => $this->weight_unit ?? 'kg',
                'dimensions' => [
                    'length' => (float) ($this->length ?? 0),
                    'width' => (float) ($this->width ?? 0),
                    'height' => (float) ($this->height ?? 0),
                    'unit' => $this->dimension_unit ?? 'cm',
                ],
                'shipping_class' => $this->shipping_class ?? 'standard',
                'free_shipping' => $this->free_shipping ?? false,
                'ships_from' => [
                    'country' => $this->ships_from_country ?? '',
                    'city' => $this->ships_from_city ?? '',
                ],
                'estimated_delivery' => [
                    'min_days' => $this->min_delivery_days ?? 3,
                    'max_days' => $this->max_delivery_days ?? 7,
                    'express_available' => $this->express_available ?? false,
                ],
            ],
            
            // ===== PRODUCT FLAGS & BADGES =====
            'badges' => [
                'is_featured' => (bool) ($this->isFeatured ?? false),
                'is_new' => $isNew,
                'is_trending' => (bool) ($this->isTrending ?? false),
                'is_bestseller' => (bool) ($this->is_bestseller ?? false),
                'is_on_sale' => $isDiscountActive,
                'is_limited_edition' => (bool) ($this->is_limited_edition ?? false),
                'is_exclusive' => (bool) ($this->is_exclusive ?? false),
                'is_eco_friendly' => (bool) ($this->is_eco_friendly ?? false),
            ],
            
            // ===== SEO & META DATA =====
            'seo' => $this->getSeoData(),
            
            // ===== RELATED PRODUCTS & RECOMMENDATIONS =====
            'related_products' => [
                'similar_products' => $this->getRelatedProductIds('similar'),
                'frequently_bought_together' => $this->getRelatedProductIds('frequently_bought_together'),
                'customers_also_viewed' => $this->getRelatedProductIds('customers_also_viewed'),
                'recently_viewed' => $this->getRelatedProductIds('recently_viewed'),
            ],
            
            // ===== ADDITIONAL INFORMATION =====
            'warranty' => [
                'has_warranty' => $this->has_warranty ?? false,
                'duration' => $this->warranty_duration ?? null,
                'duration_unit' => $this->warranty_duration_unit ?? 'months',
                'type' => $this->warranty_type ?? null,
                'details' => $this->warranty_details ?? null,
            ],
            'return_policy' => [
                'returnable' => $this->returnable ?? true,
                'return_window_days' => $this->return_window_days ?? 7,
                'conditions' => $this->return_conditions ?? null,
            ],
            'minimum_order_quantity' => $this->minimum_order_quantity ?? 1,
            'maximum_order_quantity' => $this->maximum_order_quantity ?? null,
            'bulk_pricing' => BulkPricingResource::collection($this->bulkPricing ?? collect()),
            
            // ===== SUPPLIER & VENDOR =====
            'supplier' => $this->when($this->supplier, [
                'id' => $this->supplier->id ?? null,
                'name' => $this->supplier->name ?? '',
                'phone' => $this->supplier->phone ?? '',
                'email' => $this->supplier->email ?? '',
                'address' => $this->supplier->address ? $this->supplier->address->address ?? '' : '',
            ]),
            'vendor' => null, // Can be added if vendor table exists
            
            // ===== FAQS =====
            'faqs' => $this->getFaqsData(),
            
            // ===== TIMESTAMPS & AUDIT =====
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'published_at' => $this->published_at ? $this->published_at->toIso8601String() : null,
            'created_by' => $this->when($this->created_by, [
                'id' => $this->created_by->id ?? null,
                'name' => ($this->created_by->first_name ?? '') . ' ' . ($this->created_by->last_name ?? ''),
                'role' => 'admin', // Can be fetched from user roles
            ]),
            'updated_by' => $this->when($this->updated_by, [
                'id' => $this->updated_by->id ?? null,
                'name' => ($this->updated_by->first_name ?? '') . ' ' . ($this->updated_by->last_name ?? ''),
                'role' => 'admin',
            ]),
            
            // ===== ANALYTICS & TRACKING =====
            'analytics' => [
                'views_count' => $viewsCount,
                'clicks_count' => $clicksCount,
                'add_to_cart_count' => $addToCartCount,
                'purchase_count' => $purchaseCount,
                'conversion_rate' => round($conversionRate, 2),
                'wishlist_count' => $wishlistCount,
            ],
        ];
    }
    
    /**
     * Get rating distribution
     */
    private function getRatingDistribution($reviews): array
    {
        return [
            '5_star' => $reviews->where('rating', 5)->count(),
            '4_star' => $reviews->where('rating', 4)->count(),
            '3_star' => $reviews->where('rating', 3)->count(),
            '2_star' => $reviews->where('rating', 2)->count(),
            '1_star' => $reviews->where('rating', 1)->count(),
        ];
    }
    
    /**
     * Get FAQs data
     */
    private function getFaqsData(): array
    {
        $faqs = $this->faqs ?? collect();
        
        return $faqs->map(function ($faq) {
            return [
                'id' => $faq->id,
                'question' => $faq->question ?? '',
                'answer' => $faq->answer ?? '',
            ];
        })->toArray();
    }
    
    /**
     * Get specifications grouped by group
     */
    private function getSpecificationsGrouped(): array
    {
        $specs = $this->product_specifications ?? collect();
        $grouped = $specs->groupBy('group');
        
        return $grouped->map(function ($items, $group) {
            return [
                'group' => $group ?? 'General',
                'attributes' => $items->map(function ($item) {
                    return [
                        'name' => $item->name ?? '',
                        'value' => $item->value ?? '',
                    ];
                })->toArray(),
            ];
        })->values()->toArray();
    }
    
    /**
     * Get SEO data
     */
    private function getSeoData(): array
    {
        $seoMeta = $this->seo_meta ?? collect();
        $metaData = [];
        
        foreach ($seoMeta as $meta) {
            $metaData[$meta->name] = $meta->content;
        }
        
        $primaryImageUrl = $this->primary_photo 
            ? ImageUploadManager::prepareImageUrl(ProductPhoto::PHOTO_UPLOAD_PATH, $this->primary_photo->photo)
            : null;
        
        return [
            'meta_title' => $metaData['meta_title'] ?? $this->name ?? '',
            'meta_description' => $metaData['meta_description'] ?? $this->short_description ?? $this->description ?? '',
            'meta_keywords' => isset($metaData['meta_keywords']) ? explode(',', $metaData['meta_keywords']) : [],
            'canonical_url' => $metaData['canonical_url'] ?? null,
            'og_title' => $metaData['og_title'] ?? $this->name ?? '',
            'og_description' => $metaData['og_description'] ?? $this->short_description ?? '',
            'og_image' => $metaData['og_image'] ?? $primaryImageUrl,
            'twitter_card' => $metaData['twitter_card'] ?? 'summary_large_image',
        ];
    }
    
    /**
     * Get related product IDs by relation type
     */
    private function getRelatedProductIds(string $relationType): array
    {
        $related = $this->relatedProducts ?? collect();
        return $related->where('relation_type', $relationType)
            ->pluck('related_product_id')
            ->toArray();
    }
    
    /**
     * Calculate price range for variable products
     */
    private function getPriceRange(): ?array
    {
        if (!$this->hasVariations() || $this->variations->isEmpty()) {
            return null;
        }

        $prices = $this->variations->map(function ($variation) {
            $finalPrice = $variation->sale_price ?? $variation->regular_price ?? 0;
            return (float) $finalPrice;
        })->filter()->values();

        if ($prices->isEmpty()) {
            return null;
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
        ];
    }
}
