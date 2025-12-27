<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\CategoryMenuResource;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductListResource;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildSubCategory;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductSeoMetaData;
use App\Models\ProductSpecification;
use App\Models\Shop;
use App\Models\SubCategory;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of products
     *
     * @param IndexProductRequest $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function index(IndexProductRequest $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->validated()['per_page'] ?? 20;
            
            $filters = [
                'search' => $request->validated()['search'] ?? null,
                
                // Category filters
                'category_id' => $request->validated()['category_id'] ?? null,
                'sub_category_id' => $request->validated()['sub_category_id'] ?? null,
                'child_sub_category_id' => $request->validated()['child_sub_category_id'] ?? null,
                
                // Brand filter
                'brand_id' => $request->validated()['brand_id'] ?? null,
                
                // Status filter
                'status' => $request->validated()['status'] ?? null,
                
                // Price range filters
                'min_price' => $request->validated()['min_price'] ?? null,
                'max_price' => $request->validated()['max_price'] ?? null,
                
                // Attribute filters
                'color' => $request->validated()['color'] ?? null,
                'attribute_id' => $request->validated()['attribute_id'] ?? null,
                'attribute_value_id' => $request->validated()['attribute_value_id'] ?? null,
                'attribute_value_ids' => $request->validated()['attribute_value_ids'] ?? null,
                
                // Stock filters
                'in_stock' => $request->validated()['in_stock'] ?? null,
                'stock_status' => $request->validated()['stock_status'] ?? null,
                
                // Sorting
                'order_by' => $request->validated()['order_by'] ?? 'created_at',
                'direction' => $request->validated()['direction'] ?? 'desc',
            ];

            // Remove null values from filters (but keep false values for boolean filters)
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $products = $productService->getPaginatedProducts($filters, $perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products', $e->getMessage(), 500);
        }
    }

        /**
     * Display the specified product
     *
     * @param int $id
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function show(int $id, ProductService $productService): JsonResponse
    {
        try {
            $product = $productService->getProductById($id);
            
            return $this->success(
                new ProductDetailsResource($product),
                'Product retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve product', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified product by slug
     *
     * @param string $slug
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function showBySlug(string $slug, ProductService $productService): JsonResponse
    {
        try {
            $product = $productService->getProductBySlug($slug);
            
            return $this->success(
                new ProductDetailsResource($product),
                'Product retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve product', $e->getMessage(), 500);
        }
    }

    /**
     * Get featured products
     *
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function featured(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getFeaturedProducts($perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Featured products retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve featured products', $e->getMessage(), 500);
        }
    }

    /**
     * Get new arrivals
     *
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function newArrivals(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getNewArrivals($perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'New arrivals retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve new arrivals', $e->getMessage(), 500);
        }
    }

    /**
     * Get trending products
     *
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function trending(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getTrendingProducts($perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Trending products retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve trending products', $e->getMessage(), 500);
        }
    }

    /**
     * Get bestsellers
     *
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function bestsellers(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $category = $request->input('category');
            $products = $productService->getBestsellers($perPage, false, $category);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Bestsellers retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve bestsellers', $e->getMessage(), 500);
        }
    }

    /**
     * Get products on sale
     *
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function onSale(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getOnSaleProducts($perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Products on sale retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products on sale', $e->getMessage(), 500);
        }
    }

    /**
     * Get similar products
     *
     * @param int $id
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function similar(int $id, Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getSimilarProducts($id, $perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Similar products retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve similar products', $e->getMessage(), 500);
        }
    }

    /**
     * Get product recommendations
     *
     * @param int $id
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function recommendations(int $id, Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $relationType = $request->input('type', 'frequently_bought_together'); // frequently_bought_together, customers_also_viewed
            $products = $productService->getRecommendations($id, $relationType, $perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Product recommendations retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve recommendations', $e->getMessage(), 500);
        }
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function byCategory(int $categoryId, Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getProductsByCategory($categoryId, $perPage);
            
            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Products by category retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products by category', $e->getMessage(), 500);
        }
    }

    /**
     * Get products by brand
     *
     * @param int $brandId
     * @param Request $request
     * @param ProductService $productService
     * @return JsonResponse
     */
    public function byBrand(int $brandId, Request $request, ProductService $productService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $products = $productService->getProductsByBrand($brandId, $perPage);

            return $this->success([
                'products' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->hasMorePages(),
                ]
            ], 'Products by brand retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products by brand', $e->getMessage(), 500);
        }
    }

/**
     * Get category menu with subcategories and child subcategories
     * Optimized with caching for performance
     *
     * @param CategoryService $categoryService
     * @return JsonResponse
     */
    public function ProductMenu(CategoryService $categoryService): JsonResponse
    {
        try {
            $menu = $categoryService->getMenu();
            
            return $this->success(
                CategoryMenuResource::collection($menu),
                'Menu retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve menu', $e->getMessage(), 500);
        }
    }
    
    /**
     *product details for web (legacy route)
     */
    public function productsdetails($id)
    {
        // dd($id);
        $products = Product::query()->with([
            'category:id,name',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'brand:id,name',
            'country:id,name',
            'supplier:id,name,phone',
            'created_by:id,first_name,last_name',
            'updated_by:id,first_name,last_name',
            'primary_photo',
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications.specifications',
            'seo_meta'
        ])->where('id', $id)->first();
        return response()->json($products);
    }



/** ===============Admin Routes =============== */
    /**
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $product = (new Product())->storeProduct($request->all(), auth()->id = 1);

            if ($request->has('attributes')) {
                (new ProductAttribute())->storeAttribute($request->input('attributes'), $product);
            }

            if ($request->has('specifications')) {
                (new ProductSpecification())->storeProductSpecification($request->input('specifications'), $product);
            }

            if ($request->has('meta')) {
                (new ProductSeoMetaData())->storeSeoMata($request->input('meta'), $product);
            }

            // Attach shops to the product
            $shopsData = array_combine(
                $request->input('shop_ids'),
                $request->input('shop_quantities')
            );

            foreach ($shopsData as $shopId => $quantity) {
                $product->shops()->attach($shopId, ['quantity' => $quantity['quantity']]);
            }

            DB::commit();
            return response()->json(['msg' => 'Product Saved Successfully', 'cls' => 'success', 'product_id' => $product->id]);
        } catch (\Throwable $e) {
            info("PRODUCT_SAVE_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            DB::rollBack();
            return response()->json(['msg' => $e->getMessage(), 'cls' => 'warning']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified product.
     * Supports partial updates - only provided fields will be updated.
     *
     * @param UpdateProductRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get only the validated data that was actually sent
            $validatedData = $request->validated();

            // Prepare update data for basic product fields
            $updateData = [];
            $productFields = [
                'name', 'slug', 'sku', 'description', 'short_description',
                'category_id', 'sub_category_id', 'child_sub_category_id',
                'brand_id', 'supplier_id', 'country_id',
                'cost', 'price', 'price_formula', 'field_limit',
                'discount_fixed', 'discount_percent', 'discount_start', 'discount_end',
                'stock','stock_by_location', 'stock_status', 'low_stock_threshold', 'manage_stock', 'allow_backorders',
                'status', 'visibility',
                'isFeatured', 'isNew', 'isTrending', 'is_bestseller',
                'is_limited_edition', 'is_exclusive', 'is_eco_friendly',
                'free_shipping', 'express_available',
                'weight', 'length', 'width', 'height',
                'tax_rate', 'tax_included',
                'has_warranty', 'warranty_duration_months',
                'returnable', 'return_period_days'
            ];

            // Only include fields that are present in the request
            foreach ($productFields as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $updateData[$field] = $validatedData[$field];
                }
            }

            // Add updated_by_id
            $updateData['updated_by_id'] = auth()->id() ?? 1;

            // Update the product with only the provided fields
            if (!empty($updateData)) {
                $product->update($updateData);
            }

            // Update attributes if provided
            if (array_key_exists('attributes', $validatedData)) {
                (new ProductAttribute())->updateAttribute($validatedData['attributes'], $product);
            }

            // Update specifications if provided
            if (array_key_exists('specifications', $validatedData)) {
                (new ProductSpecification())->updateProductSpecification($validatedData['specifications'], $product);
            }

            // Update SEO metadata if provided
            if (array_key_exists('meta', $validatedData)) {
                (new ProductSeoMetaData())->updateSeoMata($validatedData['meta'], $product);
            }

            // Update shop quantities if provided
            if (array_key_exists('shop_quantities', $validatedData) && !empty($validatedData['shop_quantities'])) {
                $shopQuantityData = [];

                foreach ($validatedData['shop_quantities'] as $shopQuantity) {
                    $shopId = $shopQuantity['shop_id'];
                    $quantity = $shopQuantity['quantity'];
                    $shopQuantityData[$shopId] = ['quantity' => $quantity];
                }

                // Sync shop quantities
                $product->shops()->sync($shopQuantityData);
            }

            // Clear product caches
            Product::clearProductFilterCaches();

            DB::commit();

            // Reload the product with relationships for response
            $product->load([
                'category:id,name',
                'sub_category:id,name',
                'child_sub_category:id,name',
                'brand:id,name',
                'supplier:id,name',
                'country:id,name',
                'product_attributes.attribute:id,name',
                'product_attributes.attributeValue:id,name',
                'product_specifications',
                'seo_meta',
                'shops:id,name'
            ]);

            return $this->success(
                [
                    'product' => new ProductDetailsResource($product),
                    'updated_fields' => array_keys($updateData)
                ],
                'Product updated successfully'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->error(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Throwable $e) {
            Log::error('PRODUCT_UPDATE_FAILED', [
                'product_id' => $product->id,
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            DB::rollBack();
            return $this->error(
                'Failed to update product',
                config('app.debug') ? $e->getMessage() : 'An error occurred while updating the product',
                500
            );
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Delete related data
            // 1. Delete product attributes
            $product->product_attributes()->delete();

            // 2. Delete product specifications
            $product->product_specifications()->delete();

            $product->seo_meta()->delete();

            // 3. Delete product photos (assuming you have a 'photos' relationship)
            $product->photos()->delete();

            // 4. Detach the product from shops
            $product->shops()->detach();

            // Finally, delete the product itself
            $product->delete();

            DB::commit();
            return response()->json(['msg' => 'Product and Related Data Deleted Successfully', 'cls' => 'success']);
        } catch (\Throwable $e) {
            info("PRODUCT_DELETE_FAILED", ['product_id' => $product->id, 'error' => $e->getMessage()]);
            DB::rollBack();
            return response()->json(['msg' => $e->getMessage(), 'cls' => 'warning']);
        }
    }


    /**
     * Get the product list for bar codes with attributes.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get_product_list_for_bar_code(Request $request)
    {
        try {
            // Get the products with attributes
            $products = (new Product())->getProductForBarCode($request->all());

            return response()->json(['data' => $products]);
        } catch (\Throwable $e) {
            // Handle any exceptions
            return response()->json(['msg' => $e->getMessage(), 'cls' => 'warning']);
        }
    }

    /**
     * @return JsonResponse
     */
    public function get_product_columns()
    {
        $columns = Schema::getColumnListing('products');
        $formated_columns = [];
        foreach ($columns as $column) {
            $formated_columns[] = ['id' => $column, 'name' => ucfirst(str_replace('_', ' ', $column))];
        }
        return response()->json($formated_columns);
    }

    /**
     * @return JsonResponse
     */
    final public function get_add_product_data(): JsonResponse
    {
        //        $categories, $brand, $countries, $suppliers, $attributes, $sub_categories, $child_sub_categories, $shop
        return response()->json([
            'categories' => (new Category())->getCategoryIdAndName(),
            'brands' => (new Brand())->getBrandIdAndName(),
            'countries' => (new Country())->getCountryIdAndName(),
            'providers' => (new Supplier())->getProviderIdAndName(),
            'attributes' => (new Attribute())->getAttributeIdAndName(),
            'sub_categories' => (new SubCategory())->getSubCategoryIdAndNameForProduct(),
            'child_sub_categories' => (new ChildSubCategory())->getChildSubCategoryIdAndNameForProduct(),
            'shops' => (new Shop())->getShopIdAndName()
        ]);
    }
    public function duplicate($id)
    {
        // Find the product by ID
        $product = Product::findOrFail($id);

        // Duplicate the product
        $newProduct = $product->duplicateProduct($id);

        // Duplicate product attributes
        foreach ($product->product_attributes as $attribute) {
            $newAttribute = $attribute->replicate();
            $newAttribute->product_id = $newProduct->id;
            $newAttribute->save();
        }

        // Duplicate product specifications
        foreach ($product->product_specifications as $specification) {
            $newSpecification = $specification->replicate();
            $newSpecification->product_id = $newProduct->id;
            $newSpecification->save();
        }

        // Duplicate product photos (assuming you have a 'photos' relationship)
        foreach ($product->photos as $photo) {
            $newPhoto = $photo->replicate();
            $newPhoto->product_id = $newProduct->id;
            $newPhoto->save();
        }

        return response()->json([
            'msg' => 'Product Duplicated Successfully',
            'cls' => 'success',
            'product_id' => $newProduct->id
        ]);
    }
}
