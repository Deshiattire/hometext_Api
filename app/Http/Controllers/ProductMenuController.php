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


        // $menu = Product::whereIn('id',[29,379,320])->get();
        // $menu2 = Product::whereIn('category_id',[12])->get();
        // $marge = $menu->merge($menu2);
        // $menu = [$category, $subcategory, $childSubCategorie];

        return response()->json($productLink->product_id);
    }

    public function ProductMenu(): JsonResponse
    {
        $category = ProductMenu::where('menu_type', 'Horizontal')
                ->where('parent_id', 0)
                ->where('child_id', 0)
                ->orderBy('sl')->get();
        $subcategory = ProductMenu::where('menu_type', 'Horizontal')
                ->where('parent_id', '>', 0)
                ->where('child_id', 0)
                ->orderBy('sl')->get();
        $chilsubcategory = ProductMenu::where('menu_type', 'Horizontal')
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

        return response()->json($data);
    }

}
