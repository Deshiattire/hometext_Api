<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\SalesManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesManagerListResource extends JsonResource
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
            'nid'=>$this->nid,
            'bio'=>$this->bio,
            'employee_type'=>$this->employee_type,
            'created_by'=>$this->user?->first_name.' '.$this->user?->last_name,
            'status'=>$this->status == SalesManager::STATUS_ACTIVE ? SalesManager::STATUS_ACTIVE_TEXT: SalesManager::STATUS_INACTIVE_TEXT,
            'photo'=>ImageUploadManager::prepareImageUrl(SalesManager::THUMB_PHOTO_UPLOAD_PATH, $this->photo),
            'photo_full'=>ImageUploadManager::prepareImageUrl(SalesManager::PHOTO_UPLOAD_PATH, $this->photo),
            'nid_photo'=>ImageUploadManager::prepareImageUrl(SalesManager::THUMB_PHOTO_UPLOAD_PATH, $this->nid_photo),
            'nid_photo_full'=>ImageUploadManager::prepareImageUrl(SalesManager::PHOTO_UPLOAD_PATH, $this->nid_photo),
            'created_at' => $this->created_at ? $this->created_at->toDayDateTimeString(): '',
            'updated_at' => $this->updated_at ? $this->updated_at ? $this->updated_at->toDayDateTimeString() : 'Not updated yet': null,
            'address' => new AddressListResource($this->address),
            'shop' => $this->shop?->name,
            ];
    }
}
