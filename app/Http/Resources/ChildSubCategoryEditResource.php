<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\ChildSubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildSubCategoryEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sub_category_id' => $this->sub_category?->id,
            'slug' => $this->slug,
            'description' => $this->description,
            'serial' => $this->serial,
            'status' => $this->status,
            'photo_preview' => ImageUploadManager::prepareImageUrl(ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH, $this->photo),
        ];
    }
}
