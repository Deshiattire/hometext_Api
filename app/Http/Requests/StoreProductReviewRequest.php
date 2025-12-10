<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint - anyone can submit a review
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Map 'comment' to 'review' if 'comment' is provided
        if ($this->has('comment') && !$this->has('review')) {
            $this->merge(['review' => $this->input('comment')]);
        }

        // Round decimal rating to integer
        if ($this->has('rating') && is_numeric($this->input('rating'))) {
            $rating = (float) $this->input('rating');
            $rounded = (int) round($rating);
            // Clamp between 1 and 5
            $rounded = max(1, min(5, $rounded));
            $this->merge(['rating' => $rounded]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'product_id' => 'required|integer|exists:products,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'nullable|string|max:5000',
            'comment' => 'nullable|string|max:5000', // Alias for review
            'is_verified_purchase' => 'sometimes|boolean',
            'is_recommended' => 'sometimes|boolean',
        ];

        // reviewer_name and reviewer_email are required only if user_id is not provided
        if (!$this->has('user_id')) {
            $rules['reviewer_name'] = 'required|string|max:255';
            $rules['reviewer_email'] = 'required|email|max:255';
        } else {
            $rules['reviewer_name'] = 'nullable|string|max:255';
            $rules['reviewer_email'] = 'nullable|email|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Selected product does not exist',
            'reviewer_name.required' => 'Your name is required',
            'reviewer_email.required' => 'Your email is required',
            'reviewer_email.email' => 'Please provide a valid email address',
            'rating.required' => 'Rating is required',
            'rating.min' => 'Rating must be at least 1 star',
            'rating.max' => 'Rating cannot exceed 5 stars',
        ];
    }
}
