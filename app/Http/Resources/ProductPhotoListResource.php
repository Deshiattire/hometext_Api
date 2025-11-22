<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPhotoListResource extends JsonResource
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
            'url' => ImageUploadManager::prepareImageUrl(ProductPhoto::PHOTO_UPLOAD_PATH, $this->photo),
            'thumbnail' => ImageUploadManager::prepareImageUrl(ProductPhoto::THUMB_PHOTO_UPLOAD_PATH, $this->photo),
            'alt_text' => $this->alt_text ?? '',
            'width' => $this->width ?? null,
            'height' => $this->height ?? null,
            'position' => $this->position ?? 0,
        ];
    }
}
