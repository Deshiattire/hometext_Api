<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductOfferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'priduct_id',
        'user_id',
        'quentity',
        'amount',
        'type'
    ];

    /**
     * @return BelongsTo
     */
    public function User():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function Product():BelongsTo
    {
        return $this->belongsTo(Product::class, 'priduct_id');
    }
}
