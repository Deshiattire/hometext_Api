<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'from_shop_id',
        'to_shop_id',
        'attribute_id',
        'quantity',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->select(['id', 'name']);
    }

    public function fromShop()
    {
        return $this->belongsTo(Shop::class, 'from_shop_id')->select(['id', 'name']);
    }

    public function toShop()
    {
        return $this->belongsTo(Shop::class, 'to_shop_id')->select(['id', 'name']);
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id')->with('attributes','attribute_value');
    }

    /**
     * Check if the transfer is approved.
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the transfer is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transfer is rejected.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
    public function getProductNameAttribute()
    {
        return $this->product->name;
    }

    public function getShopFromNameAttribute()
    {
        return $this->fromShop->name;
    }

    public function getShopToNameAttribute()
    {
        return $this->toShop->name;
    }

}
