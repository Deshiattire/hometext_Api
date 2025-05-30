<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\Gift;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function showGiftCardList(){
        $card = Gift::where('type', 'Card')->get();
        return AppHelper::ResponseFormat(true, 'new', $card);
    }

    public function showGiftProductList(){
        $product = Gift::where('type', 'Product')->get();
        return AppHelper::ResponseFormat(true, 'new',  $product);
    }
}
