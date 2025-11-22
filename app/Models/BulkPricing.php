<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPricing extends Model
{
    use HasFactory;
    
    protected $table = 'bulk_pricing';

    protected $fillable = [
        'product_id',
        'min_quantity',
        'max_quantity',
        'price',
        'discount_percentage',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the bulk pricing.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
