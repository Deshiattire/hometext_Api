<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'views_count',
        'clicks_count',
        'add_to_cart_count',
        'purchase_count',
        'wishlist_count',
        'conversion_rate',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'clicks_count' => 'integer',
        'add_to_cart_count' => 'integer',
        'purchase_count' => 'integer',
        'wishlist_count' => 'integer',
        'conversion_rate' => 'decimal:2',
    ];

    /**
     * Get the product that owns the analytics.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
        $this->updateConversionRate();
    }

    /**
     * Increment clicks count.
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
        $this->updateConversionRate();
    }

    /**
     * Increment add to cart count.
     */
    public function incrementAddToCart(): void
    {
        $this->increment('add_to_cart_count');
        $this->updateConversionRate();
    }

    /**
     * Increment purchase count.
     */
    public function incrementPurchases(): void
    {
        $this->increment('purchase_count');
        $this->updateConversionRate();
    }

    /**
     * Update conversion rate based on views and purchases.
     */
    private function updateConversionRate(): void
    {
        if ($this->views_count > 0) {
            $this->conversion_rate = ($this->purchase_count / $this->views_count) * 100;
            $this->save();
        }
    }
}
