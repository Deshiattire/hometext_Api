<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'slug',
        'regular_price',
        'sale_price',
        'stock_quantity',
        'stock_status',
        'weight',
        'length',
        'width',
        'height',
        'attributes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the primary photo for this variation.
     */
    public function primary_photo(): HasOne
    {
        return $this->hasOne(ProductPhoto::class, 'product_id', 'product_id')
            ->where('is_primary', 1);
    }
}
