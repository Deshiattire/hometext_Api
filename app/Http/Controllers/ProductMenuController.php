<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Http\Resources\ProductListResource;
use App\Manager\ImageUploadManager;
use App\Models\BannerSlider;
use App\Models\Category;
use App\Models\ChildSubCategory;
use App\Models\Product;
use App\Models\ProductMenu;
use App\Models\ProductPhoto;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isNull;

class ProductMenuController extends Controller
{
    public function Index(Request $request)
    {

    }

    public function MenuGenerate(Request $request)
    {
        try{
            $data = [
                'menu_type'=> $request->menuType,
                'name'=> $request->name,
                'image'=> $request->image,
                'parent_id'=> $request->parentId,
                'child_id'=> $request->childId,
                'link'=> json_encode($request->link),
                'sl'=> $request->sl
            ];

            $pro = ProductMenu::create($data);

            return response()->json(['message' => 'Data inserted successfully']);
        }catch(\Exception $e){
            info("MenuGenerate_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'MenuGenerate_FAILED']);
        }
    }

    public function MenuList(){
        $ProductMenu = ProductMenu::where('menu_type', '!=' ,'Others')
                            ->where('sl', '!=', 0)
                            ->get();

        if($ProductMenu != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $ProductMenu
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function MenuListEdit(ProductMenu $productMenu){

        if($productMenu != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $productMenu
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function MenuListUpdate(Request $request, ProductMenu $productMenu){

        if($productMenu != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $productMenu
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function EcommerceProductMenu(Request $request, $type, $menuId)
    {
        $ProductMenu = null;
        $perPage = $request->input('per_page') ?? 10; // Number of items per page
        if($menuId != null){
            $ProductMenu = ProductMenu::where('menu_type', $type)
                            ->where('id', $menuId)
                            ->first();
        }

        if($ProductMenu == null){
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }

        $productLink = json_decode($ProductMenu->link, true);

        if(count($productLink) === 0){
            return response()->json([
                'messsage' => "No link data found",
                'data' => []
            ]);
        }

        // Initialize variables
        $menu_product = collect();
        $menu_category = collect();
        $menu_sub_category = collect();
        $menu_child_sub_category = collect();
        $product_data = collect();

        if(!empty($productLink['productId'])){
            $product = explode(",", $productLink['productId']);
            $menu_product = Product::whereIn('id', $product)->get();
        }

        if(!empty($productLink['category'])){
            $category_item = explode(",", $productLink['category']);
            $menu_category = Product::whereIn('category_id', $category_item)->get();
        }

        if(!empty($productLink['subCategory'])){
            $sub_category_item = explode(",", $productLink['subCategory']);
            $menu_sub_category = Product::whereIn('sub_category_id', $sub_category_item)->get();
        }

        if(!empty($productLink['chilSubCategory'])){
            $child_sub_category_item = explode(",", $productLink['chilSubCategory']);
            $menu_child_sub_category = Product::whereIn('child_sub_category_id', $child_sub_category_item)->get();
        }

        // Combine results based on available data
        $product_data = $menu_product
            ->merge($menu_category)
            ->merge($menu_sub_category)
            ->merge($menu_child_sub_category);

        if ($product_data->isEmpty()) {
            return response()->json([
                'message' => "No matching products found",
                'data' => []
            ]);
        }

        return response()->json([
            'message' => $product_data != null ? "Successfully data found" : "Data not found",
            'data' => $product_data != null ? ProductListResource::collection(AppHelper::DataPaginate($product_data, $perPage)) : []
        ]);
    }

    public function DynamicProductMenu($menuType)
    {
        $category = ProductMenu::where('menu_type', $menuType)
                ->where('parent_id', 0)
                ->where('child_id', 0)
                ->orderBy('sl')->get();
        $subcategory = ProductMenu::where('menu_type', $menuType)
                ->where('parent_id', '>', 0)
                ->where('child_id', 0)
                ->orderBy('sl')->get();
        $chilsubcategory = ProductMenu::where('menu_type', $menuType)
                ->where('parent_id', '>', 0)
                ->where('child_id','>', 0)
                ->orderBy('sl')->get();
        $data = [];

        foreach ($category as $p) {
            $x = [
                'id' => $p->id,
                'name' => $p->name,
                'image' => ImageUploadManager::prepareImageUrl(Category::THUMB_IMAGE_UPLOAD_PATH, $p->image),
                'sub' => [] // Initialize 'sub' as an empty array
            ];

            foreach ($subcategory as $s) {
                if ($p->id == $s->parent_id) {
                    $subItem = [
                        'id' => $s->id,
                        'name' => $s->name,
                        'image' => ImageUploadManager::prepareImageUrl(SubCategory::THUMB_IMAGE_UPLOAD_PATH, $s->image),
                        'child' => [] // Initialize 'child' as an empty array
                    ];

                    foreach ($chilsubcategory as $c) {
                        if ($s->id == $c->child_id) {
                            $subItem['child'][] = [
                                'id' => $c->id,
                                'name' => $c->name,
                                'image' => ImageUploadManager::prepareImageUrl(ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH, $c->image)
                            ];
                        }
                    }

                    $x['sub'][] = $subItem; // Add each subcategory to the 'sub' array
                }
            }
            $data[] = $x; // Add the category item to the main data array
        }
        return response()->json(['data' => $data]);
    }

    public function EcommerceProductMode($mode){
        $productMode = null;
        switch($mode){
            case "featured":
                $productMode = Product::where('isFeatured', 1)->get();
            case "new":
                $productMode = Product::where('isNew', 1)->get();
            case "trending":
                $productMode = Product::where('isTrending', 1)->get();
        }

        if($productMode == null){
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }

        return response()->json([
            'messsage' => "Successfully data found",
            'data' => $productMode != null ? ProductListResource::collection($productMode) : []
        ]);
    }

    public function EcommerceBannerSlider(){
        $banner = BannerSlider::get()->select('slider');
        $data = [];
        foreach($banner as $item){
            $item['slider']['left']['image'] = $item['slider']['left']['image'] ? ImageUploadManager::prepareImageUrl(BannerSlider::BANNER_UPLOAD_PATH, $item['slider']['left']['image']) : "";
            $item['slider']['right']['image'] = $item['slider']['right']['image'] ? ImageUploadManager::prepareImageUrl(BannerSlider::BANNER_UPLOAD_PATH, $item['slider']['right']['image']): "";

            array_push($data, $item['slider']);
        }

        return response()->json([
            'messsage' => "Successfully data found",
            'data' => $data
        ]);
    }



}
