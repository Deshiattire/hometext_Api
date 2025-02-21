<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStarRating extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'user_id', 'rating', 'comment', 'accept'];

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
