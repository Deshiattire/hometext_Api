<?php

namespace App\Http\Controllers\web_api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;


class OrderDetailsController extends Controller
{

    const REGISTER_USER = 1;
    const GUEST_USER = 2;
    const RETURN_USER = 3;

    public function myorder()
    {
        $customer = Customer::where('user_id',Auth::id())->first();
        $order = [];
        if($customer){
            $order = Order::where('customer_id', $customer->id )->get();
            if($order){
                return AppHelper::ResponseFormat(true,"Customer order list found", $order);
            }else{
                return AppHelper::ResponseFormat(true,"Customer order not found", $order);
            }
            
        } else {
            return AppHelper::ResponseFormat(true,"Customer not found", $order);
        }
        
        
    }
}
