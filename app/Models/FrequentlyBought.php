<?php

namespace App\Models;

use App\Http\Resources\ProductListResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrequentlyBought extends Model
{
    use HasFactory;

    protected $fillable =[
        'group_name',
        'details'
    ];

    public function productData($id){
        $res = self::query()->where('id', $id)->select('details')->first();
        $productLink = json_decode($res->details, true);
        $products = collect();
        $data = [] ;

        if(count($productLink) > 0){
            foreach($productLink['items'] as $p){
                $products->push(Product::where('id', $p['productId'])->first());

            }
            $data = ProductListResource::collection($products);
        }
        return $data;
    }
}
