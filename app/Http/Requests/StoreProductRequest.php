<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' =>'string|required|min:3|max:255',
            'slug' =>'string|required|min:3|max:255|unique:products',
            'sku' =>'string|required|min:3|max:255|unique:products',
            'brand_id' =>'numeric',
            'country_id' =>'numeric',
            'sub_category_id' =>'numeric',
            'child_sub_category_id' =>'numeric',
            'supplier_id' =>'numeric',
            'discount_fixed' =>'numeric',
            'discount_percent' =>'numeric',
            'category_id' =>'required|numeric',
            'cost' =>'required|numeric',
            'price' =>'numeric',
            'price_formula' =>'string',
            'field_limit' =>'string',
            'status' =>'required|numeric',
            'stock' =>'required|numeric',
            'isFeatured' =>'numeric',
            'isNew' =>'numeric',
            'isTrending' =>'numeric',
            'description' =>'required|max:2500|min:10',
            'attributes' =>'array',
            'specifications' =>'array',
            'shop_ids' => 'required|array',
            'shop_ids.*' => 'numeric|exists:shops,id',
            'shop_quantities' => 'required|array',
            'shop_quantities.*.quantity' => 'required|numeric|min:0',
        ];
    }
}
