<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function EcommerceProductMenu($type, $menuId)
    {
        $ProductMenu = null;
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

        $productLink = json_decode($ProductMenu->link);

        $product = explode(",", $productLink->productId);
        $category_item = explode(",", $productLink->category);
        $sub_category_item = explode(",", $productLink->subCategory);
        $child_sub_category_item = explode(",", $productLink->chilSubCategory);

        $menu_product = Product::whereIn('id', $product)->get();
        $menu_category = Product::whereIn('category_id', $category_item)->get();
        $menu_sub_category = Product::whereIn('sub_category_id', $sub_category_item)->get();
        $menu_child_sub_category = Product::whereIn('child_sub_category_id', $child_sub_category_item)->get();

        $marge = $menu_product->merge($menu_category);
        $marge_sub = $marge->merge($menu_sub_category);
        $marge_child_sub = $marge_sub->merge($menu_child_sub_category);

        return response()->json([
            'message' => "Successfully data found",
            'data' => $marge_child_sub
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
                'image' => $p->image,
                'sub' => [] // Initialize 'sub' as an empty array
            ];

            foreach ($subcategory as $s) {
                if ($p->id == $s->parent_id) {
                    $subItem = [
                        'id' => $s->id,
                        'name' => $s->name,
                        'image' => $s->image,
                        'child' => [] // Initialize 'child' as an empty array
                    ];

                    foreach ($chilsubcategory as $c) {
                        if ($s->id == $c->child_id) {
                            $subItem['child'][] = [
                                'id' => $c->id,
                                'name' => $c->name,
                                'image' => $c->image
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
        if($mode != null){
            $productMode = ProductMenu::where('name', $mode)
                            ->where('menu_type', 'Others')
                            ->where('sl', '!=', 0)
                            ->first();
        }

        if($productMode == null){
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }

        $productLink = json_decode($productMode->link);
        $product = explode(",", $productLink->productId);
        $menu_product = Product::whereIn('id', $product)->get();
        return response()->json(['data' => $menu_product]);
    }
}
