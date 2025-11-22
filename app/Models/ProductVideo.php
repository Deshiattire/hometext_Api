<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'url',
        'thumbnail',
        'title',
        'description',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Get the product that owns the video.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
