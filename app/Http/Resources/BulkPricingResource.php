<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulkPricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'min_quantity' => $this->min_quantity ?? 0,
            'max_quantity' => $this->max_quantity ?? null,
            'price' => (float) ($this->price ?? 0),
            'discount_percentage' => $this->discount_percentage ? (float) $this->discount_percentage : null,
        ];
    }
}
