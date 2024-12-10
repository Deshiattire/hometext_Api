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
        // dd($request->all());
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

    public function EcommerceProductMenu($type, $category, $subcategory = null, $childSubCategorie = null)
    {
        $ProductMenu = null;
        if($category != null && $subcategory == null && $childSubCategorie == null){
            $ProductMenu = ProductMenu::where('menu_type', $type)
                            ->where('name', $category)
                            ->where('parent_id', 0)
                            ->where('child_id', 0)
                            ->first();
        }elseif($category != null && $subcategory != null && $childSubCategorie == null){
            $ProductMenu = ProductMenu::where('menu_type', $type)
                            ->where('name', $subcategory)
                            ->where('parent_id','>', 0)
                            ->where('child_id', 0)
                            ->first();
        }else{
            $ProductMenu = ProductMenu::where('menu_type', $type)
                            ->where('name', $childSubCategorie)
                            ->where('parent_id','>', 0)
                            ->where('child_id','>', 0)
                            ->first();
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
        // $menu = [$category, $subcategory, $childSubCategorie];

        return response()->json(['data' => $marge]);
    }

    public function DynamicProductMenu($menuType): JsonResponse
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
}
