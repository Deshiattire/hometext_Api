<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow updates - you can add authorization logic in the controller if needed
        // Admin can update any review, users can update their own
        return true;
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
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_name' => 'sometimes|string|max:255',
            'reviewer_email' => 'sometimes|email|max:255',
            'rating' => 'sometimes|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'nullable|string|max:5000',
            'comment' => 'nullable|string|max:5000', // Alias for review
            'is_verified_purchase' => 'sometimes|boolean',
            'is_recommended' => 'sometimes|boolean',
            'is_approved' => 'sometimes|boolean', // Only admin can change this
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'reviewer_email.email' => 'Please provide a valid email address',
            'rating.min' => 'Rating must be at least 1 star',
            'rating.max' => 'Rating cannot exceed 5 stars',
        ];
    }
}
