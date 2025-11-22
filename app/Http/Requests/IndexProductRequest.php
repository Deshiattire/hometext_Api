<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'status' => ['sometimes', 'integer', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'order_by' => 'sometimes|string|in:id,name,price,created_at,updated_at',
            'direction' => 'sometimes|string|in:asc,desc',
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
            'per_page.max' => 'Maximum 100 products per page allowed',
            'category_id.exists' => 'Selected category does not exist',
            'brand_id.exists' => 'Selected brand does not exist',
            'order_by.in' => 'Invalid order by field',
            'direction.in' => 'Direction must be either asc or desc',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default per_page if not provided
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 20]);
        }

        // Set default order if not provided
        if (!$this->has('order_by')) {
            $this->merge(['order_by' => 'created_at']);
        }

        if (!$this->has('direction')) {
            $this->merge(['direction' => 'desc']);
        }
    }
}
