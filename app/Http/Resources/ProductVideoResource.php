<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVideoResource extends JsonResource
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
            'type' => $this->type ?? 'youtube',
            'url' => $this->url ?? '',
            'thumbnail' => $this->thumbnail ?? null,
            'title' => $this->title ?? null,
        ];
    }
}
