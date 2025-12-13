<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\ChildSubCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
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
            'category_id' => 'sometimes|nullable',
            'sub_category_id' => 'sometimes|nullable',
            'child_sub_category_id' => 'sometimes|nullable',
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
            'category_id.nullable' => 'Category ID must be a valid integer',
            'child_sub_category_id.nullable' => 'Child sub category ID must be a valid integer',
            'brand_id.exists' => 'The selected brand does not exist',
            'order_by.in' => 'Invalid order by field',
            'direction.in' => 'Direction must be either asc or desc',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
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

        // Validate duplicate category_id parameters
        $this->validateDuplicateCategoryIds();

        // Validate hierarchical relationships
        $this->validateHierarchicalRelationships();
    }

    /**
     * Validate that all category_id values in query string are valid
     * Handles duplicate query parameters
     */
    protected function validateDuplicateCategoryIds(): void
    {
        $queryString = $this->server->get('QUERY_STRING', '');
        if (empty($queryString)) {
            return;
        }

        // Manually parse query string to handle duplicate keys
        $pairs = explode('&', $queryString);
        $categoryIds = [];

        foreach ($pairs as $pair) {
            if (strpos($pair, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $pair, 2);
            $key = urldecode($key);
            $value = urldecode($value);

            if ($key === 'category_id') {
                $categoryIds[] = $value;
            }
        }

        // Validate each category_id
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $categoryIdInt = filter_var($categoryId, FILTER_VALIDATE_INT);
                if ($categoryIdInt === false) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'category_id' => ['Category ID must be a valid integer. Invalid value: ' . $categoryId]
                            ]
                        ], 422)
                    );
                }

                $category = Category::find($categoryIdInt);
                if (!$category) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'category_id' => ['Category not found. The category with ID ' . $categoryIdInt . ' does not exist.']
                            ]
                        ], 422)
                    );
                }
            }

            // Use the last category_id value (Laravel's default behavior)
            if (count($categoryIds) > 1) {
                $this->merge(['category_id' => (int)end($categoryIds)]);
            }
        } elseif ($this->has('category_id')) {
            // Validate single category_id if present
            $categoryId = $this->input('category_id');
            $categoryIdInt = filter_var($categoryId, FILTER_VALIDATE_INT);
            
            if ($categoryIdInt === false) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'category_id' => ['Category ID must be a valid integer. Invalid value: ' . $categoryId]
                        ]
                    ], 422)
                );
            }

            $category = Category::find($categoryIdInt);
            if (!$category) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'category_id' => ['Category not found. The category with ID ' . $categoryIdInt . ' does not exist.']
                        ]
                    ], 422)
                );
            }
        }
    }

    /**
     * Validate hierarchical relationships (category -> sub_category -> child_sub_category)
     */
    protected function validateHierarchicalRelationships(): void
    {
        $categoryId = $this->has('category_id') ? $this->input('category_id') : null;
        $subCategoryId = $this->has('sub_category_id') ? $this->input('sub_category_id') : null;
        $childSubCategoryId = $this->has('child_sub_category_id') ? $this->input('child_sub_category_id') : null;

        // Validate sub_category_id
        if ($subCategoryId !== null) {
            $subCategoryIdInt = filter_var($subCategoryId, FILTER_VALIDATE_INT);
            
            if ($subCategoryIdInt === false) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'sub_category_id' => ['Sub category ID must be a valid integer. Invalid value: ' . $subCategoryId]
                        ]
                    ], 422)
                );
            }

            $subCategory = SubCategory::find($subCategoryIdInt);
            if (!$subCategory) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'sub_category_id' => ['Sub category not found. The sub category with ID ' . $subCategoryIdInt . ' does not exist.']
                        ]
                    ], 422)
                );
            }

            // If category_id is provided, validate sub_category belongs to category
            if ($categoryId !== null) {
                if ($subCategory->category_id != $categoryId) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'sub_category_id' => ['Sub category mismatch. The sub category with ID ' . $subCategoryIdInt . ' does not belong to the category with ID ' . $categoryId . '.']
                            ]
                        ], 422)
                    );
                }
            }
        }

        // Validate child_sub_category_id
        if ($childSubCategoryId !== null) {
            $childSubCategoryIdInt = filter_var($childSubCategoryId, FILTER_VALIDATE_INT);

            if ($childSubCategoryIdInt === false) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'child_sub_category_id' => ['Child sub category ID must be a valid integer. Invalid value: ' . $childSubCategoryId]
                        ]
                    ], 422)
                );
            }

            $childSubCategory = ChildSubCategory::with('sub_category')
                ->find($childSubCategoryIdInt);

            if (!$childSubCategory) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'child_sub_category_id' => ['Child sub category not found. The child sub category with ID ' . $childSubCategoryIdInt . ' does not exist.']
                        ]
                    ], 422)
                );
            }

            $actualSubCategoryId = $childSubCategory->sub_category_id ?? null;
            $actualSubCategory = $childSubCategory->sub_category;

            if (!$actualSubCategory) {
                throw new HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'child_sub_category_id' => ['Child sub category is invalid. The sub category for this child sub category does not exist.']
                        ]
                    ], 422)
                );
            }

            // If sub_category_id is provided, validate child_sub_category belongs to sub_category
            if ($subCategoryId !== null) {
                if ($actualSubCategoryId != $subCategoryId) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'child_sub_category_id' => ['Child sub category mismatch. The child sub category with ID ' . $childSubCategoryIdInt . ' does not belong to the sub category with ID ' . $subCategoryId . '.']
                            ]
                        ], 422)
                    );
                }
            }

            // If category_id is provided (but sub_category_id might not be), validate through sub_category
            if ($categoryId !== null) {
                $actualCategoryId = $actualSubCategory->category_id ?? null;
                
                if (!$actualCategoryId) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'child_sub_category_id' => ['Child sub category is invalid. The category for this child sub category does not exist.']
                            ]
                        ], 422)
                    );
                }

                if ($actualCategoryId != $categoryId) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => [
                                'child_sub_category_id' => ['Child sub category mismatch. The child sub category with ID ' . $childSubCategoryIdInt . ' does not belong to the category with ID ' . $categoryId . '.']
                            ]
                        ], 422)
                    );
                }
            }
        }
    }
}
