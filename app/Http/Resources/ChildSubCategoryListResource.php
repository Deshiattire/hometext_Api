<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\ChildSubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *@property mixed $id
 *@property mixed $name
 *@property mixed $category_name
 *@property mixed $slug
 *@property mixed $serial
 *@property mixed $status
 *@property mixed $user
 *@property mixed $created_at
 *@property mixed $updated_at
 *@property mixed $photo
 *@property mixed $description
 */
class ChildSubCategoryListResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sub_category_name' => $this->sub_category?->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'serial' => $this->serial,
            'status' => $this->status == 1 ? 'Active' : 'Inactive',
            'photo' => ImageUploadManager::prepareImageUrl(ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH, $this->photo),
            'photo_full' => ImageUploadManager::prepareImageUrl(ChildSubCategory::IMAGE_UPLOAD_PATH, $this->photo),
            'created_by' => $this->user?->first_name.' '.$this->user?->last_name,
            'created_at' => $this->created_at->toDayDateTimeString(),
            'updated_at' => $this->created_at != $this->updated_at ? $this->updated_at->toDayDateTimeString() : 'Not updated yet',
        ];
    }
}
