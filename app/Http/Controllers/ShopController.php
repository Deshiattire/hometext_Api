<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Http\Requests\StoreShopRequest;
use Illuminate\Support\Str;
use App\Http\Requests\UpdateShopRequest;
use App\Http\Resources\ShopEditResource;
use App\Http\Resources\ShopListResource;
use App\Http\Resources\ProductListResource;
use App\Manager\ImageUploadManager;
use App\Models\Address;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class ShopController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request):AnonymousResourceCollection
    {
        $shop =(new Shop())->getShopList($request->all());
        return ShopListResource::collection($shop);
    }


    /**
 * Store a newly created resource in storage.
 */
public function store(StoreShopRequest $request)
{
    $shopData = $request->all();
    $addressData = $request->all();

    if ($request->has('logo')) {
        $name = Str::slug($shopData['name'] . now());
        $shopData['logo'] = ImageUploadManager::processImageUpload(
            $request->input('logo'),
            $name,
            Shop::IMAGE_UPLOAD_PATH,
            Shop::LOGO_WIDTH,
            Shop::LOGO_HEIGHT,
            Shop::THUMB_IMAGE_UPLOAD_PATH,
            Shop::LOGO_THUMB_WIDTH,
            Shop::LOGO_THUMB_HEIGHT
        );
    }

    try {
        DB::beginTransaction();

        $shop = Shop::create($shopData);
        $address = $shop->address()->create($addressData);

        DB::commit();

        return response()->json(['msg' => 'Shop Added Successfully', 'cls' => 'success']);
    } catch (Throwable $e) {
        if (isset($shopData['logo'])) {
            ImageUploadManager::deletePhoto(Shop::IMAGE_UPLOAD_PATH, $shopData['logo']);
            ImageUploadManager::deletePhoto(Shop::THUMB_IMAGE_UPLOAD_PATH, $shopData['logo']);
        }

        info('Shop', ['Shop' => $shopData, 'address' => $addressData, 'error' => $e]);
        DB::rollBack();

        return response()->json(['msg' => 'Have Validation Error', 'cls' => 'warning', 'flag' => 'true']);
    }
}


     /**
     * @param Shop $shop
     * @return ShopEditResource
     */
    public function show(Shop $shop):ShopEditResource
    {
        $shop->load('address');
        return new ShopEditResource($shop);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShopRequest $request, Shop $shop)
    {
            $shop_data = (new Shop())->prepareData($request->all(), auth());
            $address_data = (new Address())->prepareData($request->all());
            if($request->has('logo')){
                $name=Str::slug($shop_data['name'].now());
                $shop_data['logo'] =
                ImageUploadManager::processImageUpload(
                    $request->input('logo'),
                    $name,
                    Shop::IMAGE_UPLOAD_PATH,
                    Shop::LOGO_WIDTH,
                    Shop::LOGO_HEIGHT,
                    Shop::THUMB_IMAGE_UPLOAD_PATH,
                    Shop::LOGO_THUMB_WIDTH,
                    Shop::LOGO_THUMB_HEIGHT,
                    $shop->logo

                );
            }
            try{
                DB::beginTransaction();
                $shop_data = $shop->update($shop_data);
                $shop->address()->update($address_data);
                DB::commit();
                return response()->json(['msg'=>'Shop Updated Successfully', 'cls' => 'success']);
            }catch(Throwable $e){
                info('SHOP_STORE_FAIL', ['Shop' => $shop_data, 'address'=> $address_data, $e]);
                DB::rollBack();
                return response()->json(['msg'=>'Have Validation Error', 'cls' => 'warning', 'flag' =>'true' ]);
            }
    }

     /**
 * @param Shop $shop
 * @return JsonResponse
 */
public function destroy(Shop $shop): JsonResponse
{
    try {
        DB::beginTransaction();

        if (!empty($shop->logo)) {
            ImageUploadManager::deletePhoto(Shop::IMAGE_UPLOAD_PATH, $shop['logo']);
            ImageUploadManager::deletePhoto(Shop::THUMB_IMAGE_UPLOAD_PATH, $shop['logo']);
        }

        // Delete the associated address
        $shop->address()->delete();

        // Delete the shop itself
        $shop->delete();

        DB::commit();

        return response()->json(['msg' => 'Shop deleted Successfully', 'cls' => 'warning']);
    } catch (Throwable $e) {
        DB::rollBack();

        return response()->json(['msg' => 'Error deleting shop', 'cls' => 'error']);
    }
    }
    /**
     * @return JsonResponse
     */
    final public function get_shop_list():JsonResponse
    {
        $shops = (new Shop())->getShopListIdName();
        return response()->json($shops);
    }

    /**
     * Get list of shops with total product count
     *
     * @return JsonResponse
     */
    public function getShops(): JsonResponse
    {
        try {
            $shops = Shop::query()
                ->select('shops.id as shop_id', 'shops.name as shop_name')
                ->leftJoin('shop_product', 'shops.id', '=', 'shop_product.shop_id')
                ->selectRaw('COUNT(DISTINCT shop_product.product_id) as total')
                ->groupBy('shops.id', 'shops.name')
                ->orderBy('shops.id')
                ->get()
                ->map(function ($shop) {
                    return [
                        'total' => (int) $shop->total,
                        'shop_id' => $shop->shop_id,
                        'shop_name' => $shop->shop_name,
                    ];
                });

            return $this->success($shops, 'Shops retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve shops', $e->getMessage(), 500);
        }
    }

    /**
     * Get products for a specific shop
     *
     * @param Request $request
     * @param int $shop_id
     * @return JsonResponse
     */
    public function getShopProducts(Request $request, int $shop_id): JsonResponse
    {
        try {
            $shop = Shop::findOrFail($shop_id);

            $perPage = $request->input('per_page', 20);
            $perPage = min(max((int) $perPage, 1), 100);

            $products = Product::query()
                ->whereIn('products.id', function($query) use ($shop_id) {
                    $query->select('product_id')
                        ->from('shop_product')
                        ->where('shop_id', $shop_id);
                })
                ->with([
                    'category:id,name',
                    'sub_category:id,name',
                    'child_sub_category:id,name',
                    'brand:id,name',
                    'country:id,name',
                    'supplier:id,name,phone',
                    'created_by' => function($q) {
                        $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
                    },
                    'updated_by' => function($q) {
                        $q->select('id', 'first_name', 'last_name')->withoutGlobalScopes();
                    },
                    'primary_photo',
                    'product_attributes.attributes',
                    'product_attributes.attribute_value',
                    'product_specifications.specifications',
                    'shops' => function($q) {
                        $q->select('shops.id', 'shops.name');
                    }
                ])
                ->orderBy('products.id', 'desc')
                ->paginate($perPage);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Shop not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve products', $e->getMessage(), 500);
        }
    }
}
