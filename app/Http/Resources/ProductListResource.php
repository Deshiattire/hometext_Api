<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Manager\PriceManager;
use App\Models\Product;
use App\Models\ProductPhoto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'slug'=>$this->slug,
            'cost'=>$this->cost . PriceManager::CURRENCY_SYMBOL,
            'price'=>number_format($this->price) . PriceManager::CURRENCY_SYMBOL,
            'original_price'=>$this->price,
            'price_formula'=>$this->price_formula,
            'field_limit'=>$this->field_limit,
            'star' => rand(1,5),
            'sell_price'=>PriceManager::calculate_sell_price($this->price, $this->discount_percent, $this->discount_fixed, $this->discount_start, $this->discount_end ),
            'sku'=>$this->sku,
            'stock'=>$this->stock,
            'isFeatured'=>$this->isFeatured,
            'isNew'=>$this->isNew,
            'isTrending'=>$this->isTrending,
            'status'=>$this->status == Product::STATUS_ACTIVE ? 'Active':'Inactive',
            'discount_fixed'=>$this->discount_fixed . PriceManager::CURRENCY_SYMBOL,
            'discount_percent'=>$this->discount_percent . '%',
            'description'=>$this->description,
            'created_at'=>$this->created_at->toDayDateTimeString(),
            'updated_at'=>$this->updated_at == $this->updated_at ? 'Not Updated' : $this->updated_at->toDayDateTimeString(),
            'discount_start'=>$this->discount_start != null ? Carbon::create($this->discount_start) ->toDayDateTimeString(): null,
            'discount_end'=>$this->discount_end != null ? Carbon::create($this->discount_end)->toDayDateTimeString():null,
            'shops' => $this->shops->map(function ($shop) {
                return [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'shop_quantity' => $shop->pivot->quantity,
                ];
            }),
            'brand'=>$this->brand,
            'category'=>$this->category,
            'sub_category'=>$this->sub_category,
            'child_sub_category'=>$this->child_sub_category,
            'supplier'=>$this->supplier,
            'country'=>$this->country,
            'created_by'=>$this->created_by?->first_name.' '.$this->created_by?->last_name,
            'updated_by'=>$this->created_by?->first_name.' '.$this->created_by?->last_name,
            'primary_photo'=>ImageUploadManager::prepareImageUrl(ProductPhoto::THUMB_PHOTO_UPLOAD_PATH, $this->primary_photo?->photo),

            'attributes'=>ProductAttributeListResource::collection($this->product_attributes),
        ];
    }
}
