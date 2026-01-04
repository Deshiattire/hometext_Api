<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle null resource
        if (!$this->resource) {
            return [];
        }

        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'email'=>$this->email,
            'phone'=>$this->phone,
            'details'=>$this->details,
            'created_by'=>$this->user?->name,
            'status'=>$this->status == Shop::STATUS_ACTIVE ? Shop::STATUS_ACTIVE_TEXT: Shop::STATUS_INACTIVE_TEXT,
            'logo'=>ImageUploadManager::prepareImageUrl('',''),
            'logo_full'=>ImageUploadManager::prepareImageUrl('',''),
            'created_at' => $this->created_at ? $this->created_at->toDayDateTimeString() : '',
            // 'updated_at' => $this->created_at != $this->updated_at ? $this->updated_at->toDayDateTimeString() : 'Not updated yet',
            'address' => new AddressListResource($this->address),
            ];
    }
}
