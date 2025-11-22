<?php

namespace App\Http\Resources;

use App\Manager\ImageUploadManager;
use App\Models\BannerSlider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerSliderResource extends JsonResource
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
            'slider' => ImageUploadManager::prepareImageUrl(BannerSlider::IMAGE_UPLOAD_PATH, $this->slider),
            'sl' => $this->sl,
            'status' => $this->status,
        ];
    }
}

