<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $finalPrice = $this->sale_price ?? $this->regular_price ?? 0;
        
        return [
            'id' => $this->id,
            'parent_id' => $this->product_id,
            'sku' => $this->sku ?? '',
            'name' => $this->name ?? '',
            'slug' => $this->slug ?? '',
            'attributes' => $this->attributes ?? [],
            'pricing' => [
                'regular_price' => (float) ($this->regular_price ?? 0),
                'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
                'final_price' => (float) $finalPrice,
            ],
            'inventory' => [
                'stock_status' => $this->stock_status ?? 'in_stock',
                'stock_quantity' => $this->stock_quantity ?? 0,
            ],
            'media' => [
                'primary_image' => $this->when($this->primary_photo, [
                    'url' => ImageUploadManager::prepareImageUrl(ProductPhoto::PHOTO_UPLOAD_PATH, $this->primary_photo->photo ?? null),
                    'thumbnail' => ImageUploadManager::prepareImageUrl(ProductPhoto::THUMB_PHOTO_UPLOAD_PATH, $this->primary_photo->photo ?? null),
                ]),
            ],
            'weight' => (float) ($this->weight ?? 0),
            'dimensions' => [
                'length' => (float) ($this->length ?? 0),
                'width' => (float) ($this->width ?? 0),
                'height' => (float) ($this->height ?? 0),
            ],
        ];
    }
}
