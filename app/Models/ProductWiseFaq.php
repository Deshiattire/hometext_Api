<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWiseFaq extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'question', 'answer', 'type'];

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFAQList(array $input)
    {
        $query = self::query();

        if (!empty($input['product_id']) && $input['type'] == 'product') {
            $query->where('type', 'product')->where('product_id', $input['product_id']);
        }

        if (empty($input['product_id']) && $input['type'] == 'general') {
            $query->where('type', 'general')->where('product_id', 0);
        }

        return $query->get();
    }
}
