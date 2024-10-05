<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSeoMetaData extends Model
{
    use HasFactory;
    protected $fillable = ['product_id','name','content'];

    final public function storeSeoMata(array $input, Product $product):void
    {
        $meta_data = $this->PrepareSeoMataData($input, $product);
        foreach($meta_data as $meta){
            self::create($meta);
        }
    }

    final public function PrepareSeoMataData(array $input, Product $product):array
    {
        $meta_data = [];
        foreach ($input as  $key =>$value){
            $data['product_id'] = $product->id;
            $data['name'] =$value ['name'];
            $data['content'] =$value ['content'];
            $meta_data[]=$data;
        }
        return $meta_data;
    }

    final public function updateSeoMata(array $input, Product $product)
    {
        $meta_data = $this->PrepareSeoMataData($input, $product);

        // Update existing specifications and add new specifications
        foreach ($meta_data as $meta) {
            $existingMetaData = $this->where('product_id', $product->id)
                ->where('name', $meta['name'])
                ->first();

            if ($existingMetaData) {
                // If it exists, update the value
                $existingMetaData->update([
                    'content' => $meta['content'],
                ]);
            } else {
                // If it doesn't exist, create a new specification record
                $this::create($meta);
            }
        }
    }

    final public function seoMetaData()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
