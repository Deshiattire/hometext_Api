<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductMenuController extends Controller
{
    public function Index(Request $request)
    {

    }

    public function MenuGenerate(Request $request)
    {

    }

    public function EcommerceProductMenu($category, $subcategory = null, $childSubCategorie = null)
    {
        $menu = Product::whereIn('id',[29,379,320])->get();
        $menu2 = Product::whereIn('category_id',[12])->get();
        $marge = $menu->merge($menu2);
        // $menu = [$category, $subcategory, $childSubCategorie];

        return response()->json($marge);
    }
}
