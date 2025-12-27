<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * All fields are optional to support partial updates
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ? $this->route('product')->id : null;

        return [
            // Basic Information
            'name' => 'sometimes|string|min:3|max:255',
            'slug' => [
                'sometimes',
                'string',
                'min:3',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId)
            ],
            'sku' => [
                'sometimes',
                'string',
                'min:3',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'description' => 'sometimes|string|max:5000',
            'short_description' => 'sometimes|string|max:500',

            // Category & Brand
            'category_id' => 'sometimes|nullable|numeric|exists:categories,id',
            'sub_category_id' => 'sometimes|nullable|numeric|exists:sub_categories,id',
            'child_sub_category_id' => 'sometimes|nullable|numeric|exists:child_sub_categories,id',
            'brand_id' => 'sometimes|nullable|numeric|exists:brands,id',
            'supplier_id' => 'sometimes|nullable|numeric|exists:suppliers,id',
            'country_id' => 'sometimes|nullable|numeric|exists:countries,id',

            // Pricing
            'cost' => 'sometimes|numeric|min:0',
            'price' => 'sometimes|numeric|min:0',
            'price_formula' => 'sometimes|nullable|string|max:255',
            'field_limit' => 'sometimes|nullable|string|max:255',

            // Discounts
            'discount_fixed' => 'sometimes|nullable|numeric|min:0',
            'discount_percent' => 'sometimes|nullable|numeric|min:0|max:100',
            'discount_start' => 'sometimes|nullable|date',
            'discount_end' => 'sometimes|nullable|date|after_or_equal:discount_start',

            // Stock Management
            'stock' => 'sometimes|numeric|min:0',
            'stock_status' => 'sometimes|nullable|string|in:in_stock,out_of_stock,on_backorder,preorder',
            'low_stock_threshold' => 'sometimes|nullable|numeric|min:0',
            'manage_stock' => 'sometimes|boolean',
            'allow_backorders' => 'sometimes|boolean',

            // Status & Visibility
            'status' => 'sometimes|numeric|in:0,1',
            'visibility' => 'sometimes|nullable|string|in:visible,catalog,search,hidden',

            // Featured Flags
            'isFeatured' => 'sometimes|numeric|in:0,1',
            'isNew' => 'sometimes|numeric|in:0,1',
            'isTrending' => 'sometimes|numeric|in:0,1',
            'is_bestseller' => 'sometimes|boolean',
            'is_limited_edition' => 'sometimes|boolean',
            'is_exclusive' => 'sometimes|boolean',
            'is_eco_friendly' => 'sometimes|boolean',

            // Shipping & Dimensions
            'free_shipping' => 'sometimes|boolean',
            'express_available' => 'sometimes|boolean',
            'weight' => 'sometimes|nullable|numeric|min:0',
            'length' => 'sometimes|nullable|numeric|min:0',
            'width' => 'sometimes|nullable|numeric|min:0',
            'height' => 'sometimes|nullable|numeric|min:0',

            // Tax & Warranty
            'tax_rate' => 'sometimes|nullable|numeric|min:0|max:100',
            'tax_included' => 'sometimes|boolean',
            'has_warranty' => 'sometimes|boolean',
            'warranty_duration_months' => 'sometimes|nullable|numeric|min:0',
            'returnable' => 'sometimes|boolean',
            'return_period_days' => 'sometimes|nullable|numeric|min:0',

            // Related Data
            'attributes' => 'sometimes|array',
            'attributes.*.attribute_id' => 'required_with:attributes|numeric|exists:attributes,id',
            'attributes.*.attribute_value_id' => 'required_with:attributes|numeric|exists:attribute_values,id',

            'specifications' => 'sometimes|array',
            'specifications.*.name' => 'required_with:specifications|string|max:255',
            'specifications.*.value' => 'required_with:specifications|string|max:1000',

            'meta' => 'sometimes|array',
            'meta.meta_title' => 'sometimes|nullable|string|max:255',
            'meta.meta_description' => 'sometimes|nullable|string|max:500',
            'meta.meta_keywords' => 'sometimes|nullable|string|max:500',

            'shop_ids' => 'sometimes|array',
            'shop_ids.*' => 'numeric|exists:shops,id',
            'shop_quantities' => 'sometimes|array',
            'shop_quantities.*.shop_id' => 'required_with:shop_quantities|numeric|exists:shops,id',
            'shop_quantities.*.quantity' => 'required_with:shop_quantities|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.min' => 'Product name must be at least 3 characters.',
            'slug.unique' => 'This slug is already taken by another product.',
            'sku.unique' => 'This SKU is already assigned to another product.',
            'discount_end.after_or_equal' => 'Discount end date must be after or equal to start date.',
            'discount_percent.max' => 'Discount percentage cannot exceed 100%.',
        ];
    }
}
