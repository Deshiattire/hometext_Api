<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierListResource extends JsonResource
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
        'email'=>$this->email,
        'phone'=>$this->phone,
        'details'=>$this->details,
        'created_by'=>$this->user?->first_name.' '.$this->user?->last_name,
        'status'=>$this->status == Supplier::STATUS_ACTIVE ? Supplier::STATUS_ACTIVE_TEXT: Supplier::STATUS_INACTIVE_TEXT,
        'logo'=>ImageUploadManager::prepareImageUrl(Supplier::THUMB_IMAGE_UPLOAD_PATH, $this->logo),
        'logo_full'=>ImageUploadManager::prepareImageUrl(Supplier::IMAGE_UPLOAD_PATH, $this->logo),
        'created_at' => $this->created_at->toDayDateTimeString(),
        'updated_at' => $this->created_at != $this->updated_at ? $this->updated_at->toDayDateTimeString() : 'Not updated yet',
        'address' => new AddressListResource($this->address),
        ];
    }
}
