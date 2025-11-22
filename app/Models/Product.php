<?php

namespace App\Models;

use App\Manager\PriceManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'country_id',
        'sub_category_id',
        'child_sub_category_id',
        'supplier_id',
        'created_by_id',
        'updated_by_id',
        'cost',
        'description',
        'short_description',
        'discount_end',
        'discount_fixed',
        'discount_percent',
        'discount_start',
        'name',
        'price',
        'price_formula',
        'field_limit',
        'sku',
        'slug',
        'status',
        'stock',
        'isFeatured',
        'isNew',
        'isTrending',
        'visibility',
        'type',
        'parent_id',
        'published_at',
        'tax_rate',
        'tax_included',
        'tax_class',
        'currency',
        'currency_symbol',
        'stock_status',
        'low_stock_threshold',
        'allow_backorders',
        'manage_stock',
        'sold_count',
        'restock_date',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'shipping_class',
        'free_shipping',
        'ships_from_country',
        'ships_from_city',
        'min_delivery_days',
        'max_delivery_days',
        'express_available',
        'is_bestseller',
        'is_limited_edition',
        'is_exclusive',
        'is_eco_friendly',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'has_warranty',
        'warranty_duration',
        'warranty_duration_unit',
        'warranty_type',
        'warranty_details',
        'returnable',
        'return_window_days',
        'return_conditions',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'discount_start' => 'datetime',
        'discount_end' => 'datetime',
        'restock_date' => 'datetime',
        'tax_rate' => 'decimal:2',
        'tax_included' => 'boolean',
        'allow_backorders' => 'boolean',
        'manage_stock' => 'boolean',
        'free_shipping' => 'boolean',
        'express_available' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_limited_edition' => 'boolean',
        'is_exclusive' => 'boolean',
        'is_eco_friendly' => 'boolean',
        'has_warranty' => 'boolean',
        'returnable' => 'boolean',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;
    
    // Visibility constants
    public const VISIBILITY_VISIBLE = 'visible';
    public const VISIBILITY_CATALOG = 'catalog';
    public const VISIBILITY_SEARCH = 'search';
    public const VISIBILITY_HIDDEN = 'hidden';
    
    // Type constants
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIABLE = 'variable';
    public const TYPE_GROUPED = 'grouped';
    public const TYPE_BUNDLE = 'bundle';
    
    // Stock status constants
    public const STOCK_STATUS_IN_STOCK = 'in_stock';
    public const STOCK_STATUS_OUT_OF_STOCK = 'out_of_stock';
    public const STOCK_STATUS_ON_BACKORDER = 'on_backorder';
    public const STOCK_STATUS_PREORDER = 'preorder';

    /**
     * The "booted" method of the model.
     * Clears product filter caches when product is created, updated, or deleted
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearProductFilterCaches();
        });
        
        static::deleted(function () {
            static::clearProductFilterCaches();
        });
    }

    /**
     * Clear all product filter caches
     */
    public static function clearProductFilterCaches(): void
    {
        $perPageOptions = [10, 20, 30, 50, 100];
        
        foreach ($perPageOptions as $perPage) {
            Cache::forget("products_featured_{$perPage}");
            Cache::forget("products_new_arrivals_{$perPage}");
            Cache::forget("products_trending_{$perPage}");
            Cache::forget("products_bestsellers_{$perPage}");
            Cache::forget("products_on_sale_{$perPage}");
        }
    }

    public function storeProduct(array $input, int $authId): mixed
    {
        return self::create($this->prepareData($input, $authId));
    }

    private function prepareData(array $input, int $authId): array
    {
        return [
            'brand_id' => $input['brand_id'] ?? 0,
            'category_id' => $input['category_id'] ?? 0,
            'country_id' => $input['country_id'] ?? 0,
            'sub_category_id' => $input['sub_category_id'] ?? 0,
            'child_sub_category_id' => $input['child_sub_category_id'] ?? 0,
            'supplier_id' => $input['supplier_id'] ?? 0,
            'created_by_id' => $authId,
            'updated_by_id' => $authId,
            'cost' => $input['cost'] ?? 0,
            'description' => $input['description'] ?? '',
            'discount_end' => $input['discount_end'] ?? null,
            'discount_fixed' => $input['discount_fixed'] ?? 0,
            'discount_percent' => $input['discount_percent'] ?? 0,
            'discount_start' => $input['discount_start'] ?? null,
            'name' => $input['name'] ?? '',
            'price_formula' => $input['price_formula'] ?? '',
            'field_limit' => $input['field_limit'] ?? '',
            'price' => $input['price'] ?? 0,
            'sku' => $input['sku'] ?? '',
            'slug' => $input['slug'] ? Str::slug($input['slug']) : '',
            'status' => $input['status'] ?? 0,
            'stock' => $input['stock'] ?? 0,
            'isFeatured' => $input['isFeatured'] ?? 0,
            'isNew' => $input['isNew'] ?? 0,
            'isTrending' => $input['isTrending'] ?? 0,
        ];
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_product')
            ->withPivot('quantity')
            ->using(ShopProduct::class);
    }
    public function getProductById(int $id): Builder|Collection|Model|null
    {
        return self::query()->with('primary_photo')->findOrFail($id);
    }

    public function getProductList(array $input, string|bool $isAll = false): Collection|Paginator
    {
        $perPage = $input['per_page'] ?? 10;

        $query = self::query()->with([
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

        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%')
                ->orWhere('sku', 'like', '%' . $input['search'] . '%');
        }

        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }

        if ($isAll == 'yes' || $isAll === true) {
            return $query->get();
        } else {
            return $query->paginate($perPage);
        }
    }

    /**
     * @return BelongsTo
     */
    public function category():BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categoryRelation()
    {
        return $this->belongsTo(Category::class);

    }

    /**
     * @return BelongsTo
     */
    public function sub_category():BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * @return BelongsTo
     */
    public function child_sub_category():BelongsTo
    {
        return $this->belongsTo(ChildSubCategory::class, 'child_sub_category_id');
    }

    /**
     * @return BelongsTo
     */
    public function brand():BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
    /**
     * @return BelongsTo
     */
    public function country():BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    /**
     * @return BelongsTo
     */
    public function supplier():BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    /**
     * @return BelongsTo
     */
    public function created_by():BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    /**
     * @return BelongsTo
     */
    public function updated_by():BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
    /**
     * @return hasOne
     */
    public function primary_photo():hasOne
    {
        return $this->hasOne(ProductPhoto::class)->where('is_primary', 1);
    }
    /**
     * @return HasMany
     */
    public function product_attributes():HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function product_specifications():HasMany
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function seo_meta():HasMany
    {
        return $this->hasMany(ProductSeoMetaData::class, 'product_id');
    }

    /**
     * Get the parent product (for variations).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    /**
     * Get the variations of this product.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get the tags for this product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_pivot', 'product_id', 'product_tag_id');
    }

    /**
     * Get the reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get approved reviews only.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    /**
     * Get the videos for this product.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->where('is_active', true)->orderBy('position');
    }

    /**
     * Get the bulk pricing for this product.
     */
    public function bulkPricing(): HasMany
    {
        return $this->hasMany(BulkPricing::class)->where('is_active', true)->orderBy('min_quantity');
    }

    /**
     * Get the analytics for this product.
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(ProductAnalytics::class);
    }

    /**
     * Get related products.
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(RelatedProduct::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get similar products.
     */
    public function similarProducts(): HasMany
    {
        return $this->hasMany(RelatedProduct::class)
            ->where('relation_type', 'similar')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get frequently bought together products.
     */
    public function frequentlyBoughtTogether(): HasMany
    {
        return $this->hasMany(RelatedProduct::class)
            ->where('relation_type', 'frequently_bought_together')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Check if product has variations.
     */
    public function hasVariations(): bool
    {
        return $this->type === self::TYPE_VARIABLE && $this->variations()->exists();
    }

    /**
     * Get breadcrumb path.
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        
        if ($this->category) {
            $breadcrumb[] = [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug ?? '',
            ];
        }
        
        if ($this->sub_category) {
            $breadcrumb[] = [
                'id' => $this->sub_category->id,
                'name' => $this->sub_category->name,
                'slug' => $this->sub_category->slug ?? '',
            ];
        }
        
        if ($this->child_sub_category) {
            $breadcrumb[] = [
                'id' => $this->child_sub_category->id,
                'name' => $this->child_sub_category->name,
                'slug' => $this->child_sub_category->slug ?? '',
            ];
        }
        
        return $breadcrumb;
    }

    /**
     * Get products for bar codes with attributes.
     *
     * @param array $input
     * @return array
     */
    public function getProductForBarCode($input)
    {
        $query = self::query()->select(
            'id',
            'name',
            'brand_id',
            'sku',
            'price',
            'discount_end',
            'discount_percent',
            'discount_start'
        )->with([
            'brand:id,name', // Include the brand relationship with id and name
            'product_attributes' => function ($query) {
                $query->select('id', 'product_id', 'attribute_id', 'attribute_value_id', 'attribute_math_sign', 'attribute_number','shop_quantities', 'attribute_weight', 'attribute_mesarment', 'attribute_cost');
                $query->with([
                    'attributes' => function ($query) {
                        // Include all necessary fields
                        $query->select('id', 'name');
                    },
                    'attribute_value'
                ]);
            },
        ]);

        if (!empty($input['name'])) {
            $query->where(function ($query) use ($input) {
                $query->where('name', 'like', '%' . $input['name'] . '%')
                    ->orWhere('sku', 'like', '%' . $input['name'] . '%');
            });
        }

        if (!empty($input['category_id'])) {
            $query->where('category_id', $input['category_id']);
        }

        if (!empty($input['sub_category_id'])) {
            $query->where('sub_category_id', $input['sub_category_id']);
        }

        $products = $query->get();

        // Calculate and append sell_price to each product
        $products->transform(function ($product) {
            $product->sell_price = PriceManager::calculate_sell_price(
                $product->price,
                $product->discount_percent,
                $product->discount_fixed,
                $product->discount_start,
                $product->discount_end
            );

            return $product;
        });

        return $products;
    }






    public function getAllProduct($columns =  ['*'])
    {
        $products = DB::table('products')->select($columns)->get();
        return collect($products);
    }

    public function photos()
    {
        return $this->hasMany(ProductPhoto::class)->where('is_primary', 0);
    }

    public function duplicateProduct($id): Product
    {
        $product = Product::findOrFail($id);
        $newProduct = new Product();
        $fieldsToCopy = [
            'name',
            'sku',
            'brand_id',
            'category_id',
            'country_id',
            'sub_category_id',
            'child_sub_category_id',
            'supplier_id',
            'created_by_id',
            'updated_by_id',
            'cost',
            'description',
            'discount_end',
            'discount_fixed',
            'discount_percent',
            'discount_start',
            'price_formula',
            'field_limit',
            'price',
            'stock',
            'isFeatured',
            'isNew',
            'isTrending',
        ];

        // Copy the non-null data from the original product to the new product
        foreach ($fieldsToCopy as $field) {
            if ($product->$field !== null) {
                $newProduct->$field = $product->$field;
            }
        }

        // Generate a unique name
        $newProduct->name = $this->generateUniqueName($product->name);

        // Generate a unique SKU
        $newProduct->sku = $this->generateUniqueSku($product->sku);

        // Set the default status
        $newProduct->status = Product::STATUS_ACTIVE;

        // Generate a lowercase slug based on the name
        $newProduct->slug = Str::slug($newProduct->name, '-');

        // Save the new product
        $newProduct->save();

        // Duplicate the shops associated with the original product
        foreach ($product->shops as $shop) {
            // Attach the shop to the new product
            $newProduct->shops()->attach($shop->id, ['quantity' => $shop->pivot->quantity]);
        }

        return $newProduct;
    }
    private function generateUniqueName(string $originalName): string
    {
        // You can add logic here to generate a unique name
        // For example, you can append a unique identifier
        return "Duplicate " . Str::random(10) . ' ' . $originalName;
    }

    private function generateUniqueSku(string $originalSku): string
    {
        // You can add logic here to generate a unique SKU
        // For example, you can append a unique identifier
        return "Duplicate " . Str::random(10) . ' ' . $originalSku;
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'];
    }
    public function shopName(int $shopId): string
    {
        // Replace 'shop_relationship' with the actual relationship name.
        $shop = $this->shops->where('id', $shopId)->first();

        return $shop ? $shop->name : '';
    }

}

