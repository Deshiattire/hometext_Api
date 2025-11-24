<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'store_logo',
        'store_banner',
        'store_description',
        'business_license',
        'tax_certificate',
        'is_verified',
        'verified_at',
        'rating',
        'total_reviews',
        'total_sales',
        'total_products',
        'bank_name',
        'account_number',
        'account_holder_name',
        'routing_number',
        'commission_rate',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
        'total_products' => 'integer',
        'commission_rate' => 'decimal:2',
    ];

    /**
     * Get the user that owns the vendor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verify the vendor.
     */
    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
