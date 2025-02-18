<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductListResource;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildSubCategory;
use App\Models\Country;
use App\Models\FrequentlyBought;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductSeoMetaData;
use App\Models\ProductSpecification;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\SubCategory;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use function PHPUnit\Framework\isNull;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, $is_all = 'yes'): AnonymousResourceCollection
    {
        // DB::enableQueryLog();
        $input = [
            'per_page' => $request->input('per_page'),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by'),
            'direction' => $request->input('direction'),
        ];

        $products = (new Product())->getProductList($input, $is_all);
        // dd(DB::getQueryLog());
        return ProductListResource::collection($products);
    }

    /**
     *product details for web
     */

    final public function productsdetails($id)
    {
        $products = Product::query()->with([
            'category:id,name',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'brand:id,name',
            'country:id,name',
            'supplier:id,name,phone',
            'created_by:id,name',
            'updated_by:id,name',
            'primary_photo',
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'product_specifications.specifications',
            'seo_meta.seoMetaData'
        ])->where('id', $id)->first();

        if($products){
            if($products->frequently_bought_id){
                $products['frequentlyBought'] = (new FrequentlyBought())->productData($products->frequently_bought_id);
            }

            if(!empty($products['realted_product'])){
                $productLink = json_decode($products['realted_product'], true);
                $item = explode(",", $productLink['productId']);
                if(count($item) > 0){
                    $prod = Product::whereIn('id', $item)->get();
                    $products['realted_product'] = ProductListResource::collection($prod);
                }else{
                    $products['realted_product'] = [];
                }
            }else{
                $products['realted_product'] = [];
            }
        }

        return response()->json([
            'message' => $products != null ? "Successfully data found" : "Data not found",
            'data' => $products != null ? $products : []
        ]);
    }


    /**
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            Log::debug('=============== store =================');
            Log::debug($request->all());
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
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        Log::info($product);
        $productDetails = $product->load([
            'category:id,name',
            'photos:id,photo,product_id,is_primary',
            'sub_category:id,name',
            'child_sub_category:id,name',
            'brand:id,name',
            'country:id,name',
            'supplier:id,name,phone',
            'created_by:id,name',
            'updated_by:id,name',
            'primary_photo',
            'product_attributes',
            'product_attributes.attributes',
            'product_attributes.attribute_value',
            'seo_meta.seoMetaData'
        ]);

        return new ProductDetailsResource($productDetails);
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
            Log::debug('=============== update =================');
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

            if ($request->has('shop_ids') && $request->has('shops')) {
                (new ShopProduct())->updateShopProduct($request->input('shops'), $product);
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

    public function ProductFilter(Request $request){
        $input = [
            'attributeId' => $request->input('attribute_id'),
            'attributeValueId' => $request->input('attribute_value_id')
        ];

        if(empty($input['attributeId']) || empty($input['attributeValueId'])){
            return response()->json([
                'message' => "Please provide valid data",
            ]);
        }

        $products = collect();

        $products = Product::join('product_attributes', 'product_attributes.product_id', '=', 'products.id')
            ->where('product_attributes.attribute_id', $input['attributeId'])
            ->where('product_attributes.attribute_value_id', $input['attributeValueId'])
            ->get();

        return response()->json([
            'message' => count($products) > 0 ? "Successfully data found" : "Data not found",
            'data' => count($products) > 0  != null ? ProductListResource::collection($products) : []
        ]);
    }

    public function ProductFind(Request $request){
        // DB::enableQueryLog();
        $input = [
            'search' => $request->input('search')
        ];

        $products = (new Product())->getFindProduct($input);
        // dd(DB::getQueryLog());
        return ProductListResource::collection($products);
    }
}
