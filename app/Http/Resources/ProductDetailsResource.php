<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Manager\PriceManager;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Utility\Date;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ramsey\Uuid\Rfc4122\NilUuid;

class ProductDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price_manager = PriceManager::calculate_sell_price($this->price, $this->discount_percent, $this->discount_fixed, $this->discount_start, $this->discount_end );
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'slug'=>$this->slug,
            'cost'=>$this->cost . PriceManager::CURRENCY_SYMBOL,
            'price'=>number_format($this->price) . PriceManager::CURRENCY_SYMBOL,
            'old_price'=>number_format($this->old_price) . PriceManager::CURRENCY_SYMBOL,
            'original_price'=>$this->price,
            'price_formula'=>$this->price_formula,
            'field_limit'=>$this->field_limit,
            'sell_price'=>$price_manager,
            'sku'=>$this->sku,
            'stock'=>$this->stock,
            'isFeatured'=>$this->isFeatured,
            'isNew'=>$this->isNew,
            'isTrending'=>$this->isTrending,
            'status'=>$this->status == Product::STATUS_ACTIVE ? 'Active':'Inactive',
            'discount_fixed'=>$this->discount_fixed . PriceManager::CURRENCY_SYMBOL,
            'discount_percent'=>$this->discount_percent . '%',
            'description'=>$this->description,
            'meta_title'=>$this->meta_title,
            'meta_keywords'=>$this->meta_keywords,
            'meta_description'=>$this->meta_description,
            'created_at'=>$this->created_at->toDayDateTimeString(),
            'updated_at'=>$this->updated_at == $this->updated_at ? 'Not Updated' : $this->updated_at->toDayDateTimeString(),
            'discount_start'=>$this->discount_start != null ? Carbon::create($this->discount_start) ->toDayDateTimeString(): null,
            'discount_end'=>$this->discount_end != null ? Carbon::create($this->discount_end)->toDayDateTimeString():null,
            'discount_remaining_days'=>Date::calculate_discount_remaining_date($this->discount_remaining_days),
            'profit'=>$price_manager['price'] - $this->cost,
            'profit_percentage' => $price_manager['price'] != 0 ? number_format((($price_manager['price'] - $this->cost) / $price_manager['price'] * 100), NilUuid::RFC_4122) : 0,
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
            'created_by'=>$this->created_by,
            'updated_by'=>$this->updated_by,
            'primary_photo'=>ImageUploadManager::prepareImageUrl(ProductPhoto::THUMB_PHOTO_UPLOAD_PATH, $this->primary_photo?->photo),
            'attributes'=>ProductAttributeListResource::collection($this->product_attributes),
            'specifications'=>PrductSpecificationListResource::collection($this->product_specifications),
            'photos'=>ProductPhotoListResource::collection($this->photos),
            'meta_title'=>$this->meta_title,
            'meta_description'=>$this->meta_description,
            'meta_keywords'=>$this->meta_keywords,
        ];
    }
}
