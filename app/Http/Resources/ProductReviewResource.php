<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => $this->when($this->product, [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
                'slug' => $this->product->slug ?? null,
            ]),
            'user_id' => $this->user_id,
            'user' => $this->when($this->user, [
                'id' => $this->user->id ?? null,
                'name' => ($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''),
            ]),
            'reviewer_name' => $this->reviewer_name ?? '',
            'reviewer_email' => $this->reviewer_email ?? '',
            'rating' => $this->rating ?? 0,
            'title' => $this->title ?? '',
            'review' => $this->review ?? '',
            'is_verified_purchase' => $this->is_verified_purchase ?? false,
            'is_recommended' => $this->is_recommended ?? true,
            'is_approved' => $this->is_approved ?? false,
            'is_helpful_count' => $this->is_helpful_count ?? 0,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
