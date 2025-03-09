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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

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
        'meta_title',
        'meta_description',
        'meta_keywords',
        'discount_end',
        'discount_fixed',
        'discount_percent',
        'discount_start',
        'name',
        'price',
        'old_price',
        'price_formula',
        'field_limit',
        'sku',
        'slug',
        'status',
        'frequently_bought_id',
        'realted_product',
        'stock',
        'isFeatured',
        'isNew',
        'isTrending',
    ];

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

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
            'meta_title' => $input['meta_title'] ?? '',
            'meta_keywords' => $input['meta_keywords'] ?? '',
            'meta_description' => $input['meta_description'] ?? '',
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

    public function getProductList(array $input): Collection|Paginator
    {
        $perPage = $input['per_page'] ?? 10;
        $paginate = $input['paginate'];

        $query = self::query()->with([
            'category:id,name',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'brand:id,name',
            'country:id,name',
            'supplier:id,name,phone',
            'created_by:id,name',
            'updated_by:id,name',
            'primary_photo',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications.specifications'
        ]);

        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%')
                ->orWhere('sku', 'like', '%' . $input['search'] . '%');
        }

        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }

        if ($paginate == 'yes') {
            return $query->paginate($perPage);
        } else {
            return $query->get();
        }
    }

    public function getFindProduct(array $input): Collection|Paginator
    {
        $query = self::query()->with([
            'category:id,name',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'primary_photo',
        ]);

        if (!empty($input['search'])) {
            $search = $input['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('sub_category', function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('child_sub_category', function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('shops', function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->get();
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

        if (!empty($input['child_sub_category_id'])) {
            $query->where('child_sub_category_id', $input['child_sub_category_id']);
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

    public function frequentlyBought():BelongsTo
    {
        return $this->belongsTo(FrequentlyBought::class);
    }

}

