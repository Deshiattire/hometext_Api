<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
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
            $products = $productService->getBestsellers($perPage);

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
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            Log::debug('Request');
            Log::debug($request->all());
            DB::beginTransaction();

            // Update the basic product details if they are present in the request
            $productData = $request->all();
            $product->update($productData);

            // Update attributes if provided
            if ($request->has('attributes')) {
                (new ProductAttribute())->updateAttribute($request->input('attributes'), $product);
            }

            // Update specifications if provided
            if ($request->has('specifications')) {
                (new ProductSpecification())->updateProductSpecification($request->input('specifications'), $product);
            }

            if ($request->has('meta')) {
                (new ProductSeoMetaData())->updateSeoMata($request->input('meta'), $product);
            }

            if ($request->has('shop_ids') && $request->has('shop_quantities')) {
                $shopsData = $request->input('shop_quantities');

                $shopQuantityData = [];

                foreach ($shopsData as $shopQuantity) {
                    $shopId = $shopQuantity['shop_id'];
                    $quantity = $shopQuantity['quantity'];

                    $shopQuantityData[$shopId] = ['quantity' => $quantity];
                }

                $product->shops()->sync($shopQuantityData);
            }
            DB::commit();
            return response()->json(['msg' => 'Product Updated Successfully', 'cls' => 'success', 'product_id' => $product->id]);
        } catch (\Throwable $e) {
            info("PRODUCT_UPDATE_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            DB::rollBack();
            return response()->json(['msg' => $e->getMessage(), 'cls' => 'warning']);
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
