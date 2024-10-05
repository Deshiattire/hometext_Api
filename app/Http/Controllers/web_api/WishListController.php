<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\WishList;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class WishListController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['signup', 'myorder']]);
    // }

    // E-com user signup
    public function wishlist(Request $request)
    {
        if (Auth::check()) {
            $fields['product_id'] = 'required';
            $validator = Validator::make($request->all(), $fields);
            if ($validator->fails()) {
                return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
            }
            $customer = Customer::where('user_id', '=', Auth::user()->id)->first();
            $customer_id = 1;
            if ($customer)  $customer_id = $customer->id;
            // check product have wishlist
            $is_exist = WishList::where('product_id', '=', $request->product_id)
                ->where('customer_id', '=', $customer_id)->first();

            if (empty($is_exist)) {
                $wish = new WishList();
                $wish->product_id = $request->product_id;
                $wish->customer_id =  $customer_id;
                $wish->is_wish =  1;
                $wish->created_by =  Auth::user()->id;
                $wish->save();
            } else {
                $is_exist->is_wish =  1;
                $is_exist->updated_by =  Auth::user()->id;
                $is_exist->save();
            }
            // get all active wishlist
            $total_wishlist = WishList::where('customer_id', '=', $customer_id)->where('is_wish', '=', 1)->count();
            return response()->json([
                'status' => 'success',
                'total_wishlist' => $total_wishlist,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'total_wishlist' => 0,
            ], 200);
        }
    }

    public function getWishlist(Request $request){
        $customer = Customer::where('user_id', '=', Auth::user()->id)->first();
        $wishlist = WishList::where('customer_id', '=', $customer->id)->where('is_wish', '=', 1)->get();

        if($wishlist != null){
            return response()->json([
                'status' => true,
                'wishlist' => $wishlist,
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'wishlist' => 'No data found',
            ], 200);
        }
    }

    public function deleteWishlist(Request $request){
        $fields['product_id'] = 'required';
        $validator = Validator::make($request->all(), $fields);
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }

        $customer = Customer::where('user_id', '=', Auth::user()->id)->first();
        $wishlist = WishList::where('customer_id', '=', $customer->id)
                    ->where('is_wish', '=', 1)
                    ->where('product_id', '=', $request->product_id)
                    ->first();

        if($wishlist != null){
            $wishlist->update([
                'is_wish' => 2,
            ]);

            return response()->json([
                'status' => true,
                'wishlist' => 'Wish list updated successfully',
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'wishlist' => 'No data found',
            ], 200);
        }
    }
}
